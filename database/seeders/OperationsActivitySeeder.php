<?php

namespace Database\Seeders;

use App\Actions\Cash\CloseCashSessionAction;
use App\Actions\Cash\OpenCashSessionAction;
use App\Actions\Cash\RequestCashHandoverAction;
use App\Actions\Cash\ResolveCashHandoverAction;
use App\Actions\Inventory\ApplyStockCountAction;
use App\Actions\Inventory\ApproveTransferAction;
use App\Actions\Inventory\CompleteStockCountAction;
use App\Actions\Inventory\CreateTransferAction;
use App\Actions\Inventory\ReceiveTransferAction;
use App\Actions\Inventory\ShipTransferAction;
use App\Actions\Inventory\StartStockCountAction;
use App\Actions\Inventory\SubmitTransferAction;
use App\Actions\Sales\CancelSaleAction;
use App\Actions\Sales\CompleteSaleAction;
use App\Actions\Sales\CreateSaleAction;
use App\Actions\Sales\ProcessSaleReturnAction;
use App\Enums\CashMovementType;
use App\Models\Branch;
use App\Models\CashHandover;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\StockCount;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Cash\CalculateExpectedCashService;
use App\Services\Inventory\InventoryBalanceService;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Generates two weeks of realistic operating history — sales, cash
 * sessions/movements, supervised handovers, a stock transfer, and a stock
 * count — through the same Actions the app itself uses, so every module
 * (Dashboard, Reportes, Ventas, Caja, Entregas pendientes, Kardex,
 * Transferencias, Conteos) has real data to show the moment you log in.
 *
 * Idempotent: skips entirely if this company already has any sales, so
 * re-running `db:seed` without `--fresh` never duplicates history.
 *
 * @phpstan-type ItemPick array{product_id: int, variant_sku: string|null, fraction: bool}
 */
class OperationsActivitySeeder extends Seeder
{
    private PaymentMethod $cash;

    private PaymentMethod $debit;

    private PaymentMethod $credit;

    private PaymentMethod $transfer;

    public function run(): void
    {
        $company = Company::where('tax_id', 'VEN010101ABC')->firstOrFail();

        if (Sale::withoutGlobalScopes()->where('company_id', $company->id)->exists()) {
            return;
        }

        $branchCentro = Branch::where('company_id', $company->id)->where('code', 'SUC-001')->firstOrFail();
        $branchNorte = Branch::where('company_id', $company->id)->where('code', 'SUC-002')->firstOrFail();
        $warehouseCentro = Warehouse::where('branch_id', $branchCentro->id)->where('code', 'ALM-001')->firstOrFail();
        $warehouseNorte = Warehouse::where('branch_id', $branchNorte->id)->where('code', 'ALM-002')->firstOrFail();
        $registerCentro = CashRegister::where('branch_id', $branchCentro->id)->where('code', 'CAJA-001')->firstOrFail();
        $registerNorte = CashRegister::where('branch_id', $branchNorte->id)->where('code', 'CAJA-002')->firstOrFail();

        $admin = User::where('email', 'admin@ventia-demo.test')->firstOrFail();
        $supervisor = User::where('email', 'supervisor@ventia-demo.test')->firstOrFail();
        $cajeroCentro = User::where('email', 'cajero@ventia-demo.test')->firstOrFail();
        $cajeroNorte = User::where('email', 'cajero2@ventia-demo.test')->firstOrFail();

        $this->cash = PaymentMethod::where('company_id', $company->id)->where('code', 'CASH')->firstOrFail();
        $this->debit = PaymentMethod::where('company_id', $company->id)->where('code', 'DEBIT')->firstOrFail();
        $this->credit = PaymentMethod::where('company_id', $company->id)->where('code', 'CREDIT')->firstOrFail();
        $this->transfer = PaymentMethod::where('company_id', $company->id)->where('code', 'TRANSFER')->firstOrFail();

        /** @var array<int, int> $customers */
        $customers = array_values(
            Customer::where('company_id', $company->id)->pluck('id')->map(fn ($id) => (int) $id)->all(),
        );

        $centroItems = $this->itemPool($company, [
            'REF-COLA-600', 'ARROZ-GRANEL', 'HARINA-LOTE', 'PARACETAMOL-500',
            'AGUA-1L', 'GALLETAS-MARIA', 'ACEITE-1L', 'CAFE-SOLUBLE',
            'VITAMINA-C', 'ALCOHOL-GEL', 'PASTEL-CHOCO', 'PAN-DULCE',
        ]);
        $centroItems[] = ['product_id' => (int) Product::where('company_id', $company->id)->where('sku', 'PLAYERA-BASICA')->value('id'), 'variant_sku' => 'PLAYERA-M-NEGRO', 'fraction' => false];
        $centroItems[] = ['product_id' => (int) Product::where('company_id', $company->id)->where('sku', 'PLAYERA-BASICA')->value('id'), 'variant_sku' => 'PLAYERA-G-BLANCO', 'fraction' => false];

        $norteItems = $this->itemPool($company, [
            'AGUA-1L', 'GALLETAS-MARIA', 'ACEITE-1L', 'CAFE-SOLUBLE',
            'VITAMINA-C', 'ALCOHOL-GEL', 'PASTEL-CHOCO', 'PAN-DULCE',
            'FOCO-LED-9W', 'CINTA-AISLAR',
        ]);

        app(SettingsService::class)->set($company->id, 'cash_handover_required', true);

        $today = Carbon::today();
        $lastCancelledSale = null;
        $lastReturnSale = null;

        for ($i = 13; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $isToday = $i === 0;

            $sale = $this->runBranchDay($registerCentro, $cajeroCentro, $customers, $centroItems, $day, $isToday, closeWithHandover: $i === 10, supervisor: $supervisor);
            if ($i === 8) {
                $lastCancelledSale = $sale;
            }

            $sale = $this->runBranchDay($registerNorte, $cajeroNorte, $customers, $norteItems, $day, $isToday, closeWithHandover: $isToday || $i === 6, supervisor: $supervisor);
            if ($i === 4) {
                $lastReturnSale = $sale;
            }
        }

        if ($lastCancelledSale !== null) {
            $this->actingAs($cajeroCentro);
            app(CancelSaleAction::class)->execute($lastCancelledSale->fresh(), 'El cliente ya no quiso el producto', $cajeroCentro);
        }

        if ($lastReturnSale !== null) {
            $this->actingAs($cajeroNorte);
            $fresh = $lastReturnSale->fresh('items');
            $firstItem = $fresh?->items->first();
            if ($firstItem !== null) {
                app(ProcessSaleReturnAction::class)->execute($fresh, [
                    ['sale_item_id' => $firstItem->id, 'quantity' => (string) $firstItem->quantity, 'restock' => true],
                ], 'Producto con empaque dañado', $cajeroNorte);
            }
        }

        $this->seedTransfer($warehouseCentro, $warehouseNorte, $centroItems, $admin, $today);
        $this->seedStockCount($warehouseCentro, $centroItems, $admin, $today);
    }

    /**
     * Sale-related Actions resolve the "active company" from the current
     * request's authenticated user (ActiveCompanyContext), which has no
     * meaning in a console/seeder context — Auth::login() alone updates the
     * guard but console requests don't reliably resolve request()->user()
     * from it, so the user resolver is set directly on the bound Request
     * instance too.
     */
    private function actingAs(User $user): void
    {
        Auth::login($user);
        app(Request::class)->setUserResolver(fn () => $user);
    }

    /**
     * @param  array<int, int>  $customerIds
     * @param  array<int, array{product_id: int, variant_sku: string|null, fraction: bool}>  $items
     */
    private function runBranchDay(
        CashRegister $register,
        User $cashier,
        array $customerIds,
        array $items,
        Carbon $day,
        bool $isToday,
        bool $closeWithHandover,
        User $supervisor,
    ): ?Sale {
        $this->actingAs($cashier);

        $session = app(OpenCashSessionAction::class)->execute($register, $cashier, '500.00', null);
        $openedAt = $day->copy()->setTime(9, 0);
        $this->touchSession($session, ['opened_at' => $openedAt, 'created_at' => $openedAt]);
        $this->touchMovement($session, CashMovementType::Opening, $openedAt);

        $lastSale = null;
        $saleCount = random_int(2, 4);
        $hours = [10, 12, 14, 16, 18];

        for ($n = 0; $n < $saleCount; $n++) {
            $at = $day->copy()->setTime($hours[$n % count($hours)], random_int(0, 59));
            $sale = $this->seedSale($session, $cashier, $customerIds, $items, $at);

            if ($sale !== null) {
                $lastSale = $sale;
            }
        }

        if ($isToday && $closeWithHandover) {
            // Left pending on purpose — this is exactly what "Entregas pendientes" should show.
            $expected = app(CalculateExpectedCashService::class)->calculate($session->fresh());
            app(RequestCashHandoverAction::class)->execute($session->fresh(), $this->denominationsFor($expected), 'Corte de turno, todo en orden.', $cashier);

            return $lastSale;
        }

        if ($isToday) {
            // Left open on purpose — ready to use in the POS immediately.
            return $lastSale;
        }

        $expected = app(CalculateExpectedCashService::class)->calculate($session->fresh());
        $closedAt = $day->copy()->setTime(20, 30);

        if ($closeWithHandover) {
            $session = $session->fresh();
            app(RequestCashHandoverAction::class)->execute($session, $this->denominationsFor($expected), 'Todo cuadrado, sin novedad.', $cashier);
            $handover = CashHandover::where('cash_session_id', $session->id)->latest()->first();

            $this->actingAs($supervisor);
            if ($handover !== null) {
                app(ResolveCashHandoverAction::class)->approve($handover, $supervisor, 'Revisado y aprobado.');
            }
        } else {
            // A couple of days get a small, deliberate difference so Reportes/Dashboard show it.
            $counted = $day->dayOfYear % 4 === 0
                ? bcadd($expected, '-15.00', 4)
                : $expected;

            app(CloseCashSessionAction::class)->execute($session, $counted, 'Cierre de turno.', $cashier);
        }

        $this->touchSession($session, ['closed_at' => $closedAt, 'updated_at' => $closedAt]);
        $this->touchMovement($session, CashMovementType::Closing, $closedAt);

        return $lastSale;
    }

    /**
     * @param  array<int, int>  $customerIds
     * @param  array<int, array{product_id: int, variant_sku: string|null, fraction: bool}>  $items
     */
    private function seedSale(CashSession $session, User $cashier, array $customerIds, array $items, Carbon $at): ?Sale
    {
        $warehouseId = $session->register->warehouse_id
            ?? throw new RuntimeException('La caja no tiene almacén asignado.');
        $balances = app(InventoryBalanceService::class);

        $lineCount = random_int(1, 3);
        $lines = [];
        $pool = $items;
        shuffle($pool);

        foreach ($pool as $pick) {
            if (count($lines) >= $lineCount) {
                break;
            }

            $variantId = $pick['variant_sku'] !== null
                ? ProductVariant::where('sku', $pick['variant_sku'])->value('id')
                : null;

            $quantity = $this->randomQuantity($pick['fraction']);
            $available = (string) $balances->currentQuantity($warehouseId, $pick['product_id'], $variantId);

            // Skip a product that doesn't have enough left in this warehouse
            // rather than crashing the whole run — stock naturally depletes
            // over two weeks of seeded sales.
            if (bccomp($available, $quantity, 4) < 0) {
                continue;
            }

            $lines[] = [
                'product_id' => $pick['product_id'],
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
            ];
        }

        if ($lines === []) {
            return null;
        }

        $data = [
            'branch_id' => $session->branch_id,
            'warehouse_id' => $warehouseId,
            'register_id' => $session->register_id,
            'cash_session_id' => $session->id,
            'customer_id' => $customerIds[array_rand($customerIds)],
            'items' => $lines,
            'general_discount' => random_int(1, 100) <= 20 ? ['type' => 'percentage', 'value' => '5'] : null,
        ];

        $draft = app(CreateSaleAction::class)->execute($data, $cashier);
        $total = (string) $draft->total;
        $draft->items()->delete();
        $draft->delete();

        $roll = random_int(1, 100);
        $payments = match (true) {
            $roll <= 60 => [[
                'payment_method_id' => $this->cash->id,
                'amount' => (string) (((int) ceil((float) $total / 20)) * 20),
            ]],
            $roll <= 85 => [[
                'payment_method_id' => fake()->randomElement([$this->debit->id, $this->credit->id]),
                'amount' => $total,
                'reference' => 'REF-'.fake()->numerify('######'),
                'authorization_number' => fake()->numerify('AUTH-####'),
                'card_last_four' => fake()->numerify('####'),
            ]],
            default => [[
                'payment_method_id' => $this->transfer->id,
                'amount' => $total,
                'reference' => 'SPEI-'.fake()->numerify('##########'),
            ]],
        };

        $sale = app(CompleteSaleAction::class)->execute($data, $payments, $cashier);

        Sale::withoutGlobalScopes()->whereKey($sale->id)->update([
            'completed_at' => $at, 'created_at' => $at, 'updated_at' => $at,
        ]);
        InventoryMovement::withoutGlobalScopes()
            ->where('reference_type', Sale::class)->where('reference_id', $sale->id)
            ->update(['occurred_at' => $at, 'created_at' => $at]);
        CashMovement::withoutGlobalScopes()
            ->where('reference_type', Sale::class)->where('reference_id', $sale->id)
            ->update(['occurred_at' => $at, 'created_at' => $at]);

        return $sale->fresh();
    }

    /** @return numeric-string */
    private function randomQuantity(bool $fraction): string
    {
        if ($fraction) {
            /** @var numeric-string $picked */
            $picked = fake()->randomElement(['1.000', '1.500', '2.000']);

            return $picked;
        }

        return (string) random_int(1, 3);
    }

    /**
     * @param  array<int, string>  $skus
     * @return array<int, array{product_id: int, variant_sku: string|null, fraction: bool}>
     */
    private function itemPool(Company $company, array $skus): array
    {
        $fractionable = ['ARROZ-GRANEL', 'HARINA-LOTE'];
        $pool = [];

        foreach ($skus as $sku) {
            $productId = Product::where('company_id', $company->id)->where('sku', $sku)->value('id');

            if ($productId === null) {
                continue;
            }

            $pool[] = [
                'product_id' => (int) $productId,
                'variant_sku' => null,
                'fraction' => in_array($sku, $fractionable, true),
            ];
        }

        return $pool;
    }

    /** @return list<array{denomination: numeric-string, quantity: int}> */
    private function denominationsFor(string $amount): array
    {
        $denominations = [1000, 500, 200, 100, 50, 20, 10, 5, 2, 0.5];
        $remainingCents = (int) round(((float) $amount) * 100);
        $lines = [];

        foreach ($denominations as $denomination) {
            $valueCents = (int) round($denomination * 100);
            $quantity = intdiv($remainingCents, $valueCents);

            if ($quantity > 0) {
                $lines[] = ['denomination' => (string) $denomination, 'quantity' => $quantity];
                $remainingCents -= $quantity * $valueCents;
            }
        }

        return $lines === [] ? [['denomination' => '0.5', 'quantity' => 0]] : $lines;
    }

    /** @param  array<string, mixed>  $attributes */
    private function touchSession(CashSession $session, array $attributes): void
    {
        CashSession::withoutGlobalScopes()->whereKey($session->id)->update($attributes);
    }

    private function touchMovement(CashSession $session, CashMovementType $type, Carbon $at): void
    {
        CashMovement::withoutGlobalScopes()
            ->where('cash_session_id', $session->id)->where('type', $type)
            ->update(['occurred_at' => $at, 'created_at' => $at]);
    }

    /** @param  array<int, array{product_id: int, variant_sku: string|null, fraction: bool}>  $items */
    private function seedTransfer(Warehouse $origin, Warehouse $destination, array $items, User $admin, Carbon $today): void
    {
        $this->actingAs($admin);

        $balances = app(InventoryBalanceService::class);
        $lines = [];

        foreach (array_slice($items, 0, 6) as $item) {
            if (count($lines) >= 3) {
                break;
            }

            $desired = $item['fraction'] ? '5.000' : '10';
            $available = (string) $balances->currentQuantity($origin->id, $item['product_id']);

            if (bccomp($available, $desired, 4) < 0) {
                continue;
            }

            $lines[] = [
                'product_id' => $item['product_id'],
                'product_variant_id' => null,
                'quantity_requested' => $desired,
            ];
        }

        if ($lines === []) {
            return;
        }

        $transfer = app(CreateTransferAction::class)->execute($origin, $destination, $lines, 'Reabastecimiento programado a Sucursal Norte.', $admin);

        foreach ($transfer->items as $item) {
            $item->update(['unit_cost' => Product::withoutGlobalScopes()->whereKey($item->product_id)->value('cost') ?? '0']);
        }

        app(SubmitTransferAction::class)->execute($transfer);
        app(ApproveTransferAction::class)->execute($transfer->fresh(), $admin);
        app(ShipTransferAction::class)->execute($transfer->fresh(), $admin);

        $received = [];
        foreach ($transfer->fresh('items')->items as $item) {
            $received[$item->id] = (string) $item->quantity_requested;
        }

        app(ReceiveTransferAction::class)->execute($transfer->fresh('items'), $received, $admin);

        $at = $today->copy()->subDays(2)->setTime(11, 0);
        StockTransfer::withoutGlobalScopes()->whereKey($transfer->id)->update([
            'created_at' => $at, 'requested_at' => $at, 'approved_at' => $at,
            'shipped_at' => $at->copy()->addHours(2), 'received_at' => $at->copy()->addHours(5),
        ]);
    }

    /** @param  array<int, array{product_id: int, variant_sku: string|null, fraction: bool}>  $items */
    private function seedStockCount(Warehouse $warehouse, array $items, User $admin, Carbon $today): void
    {
        $this->actingAs($admin);

        $balances = app(InventoryBalanceService::class);
        $products = [];

        foreach (array_slice($items, 0, 4) as $item) {
            $products[] = [
                'product_id' => $item['product_id'],
                'product_variant_id' => null,
            ];
        }

        $count = app(StartStockCountAction::class)->execute($warehouse, $products, 'Conteo cíclico mensual.', $admin);

        $counted = [];
        foreach ($count->items as $index => $item) {
            $expected = (string) $balances->currentQuantity($warehouse->id, $item->product_id);
            // First item comes up short by 2 units — a believable shrinkage
            // variance — but only when there's enough on hand for that to
            // make sense; otherwise leave it matching (difference of 0).
            $counted[$item->id] = $index === 0 && bccomp($expected, '2', 4) >= 0
                ? bcsub($expected, '2', 4)
                : $expected;
        }

        $count = app(CompleteStockCountAction::class)->execute($count, $counted, $admin);
        app(ApplyStockCountAction::class)->execute($count->fresh('items'), $admin);

        $at = $today->copy()->subDay()->setTime(8, 0);
        StockCount::withoutGlobalScopes()->whereKey($count->id)->update([
            'created_at' => $at, 'started_at' => $at,
            'completed_at' => $at->copy()->addHours(1), 'applied_at' => $at->copy()->addHours(2),
        ]);
    }
}

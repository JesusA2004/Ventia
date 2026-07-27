<?php

namespace App\Actions\Sales;

use App\Actions\Cash\RegisterCashMovementAction;
use App\Actions\Inventory\RecordInventoryMovementAction;
use App\Enums\CashMovementType;
use App\Enums\CashSessionStatus;
use App\Enums\InventoryMovementType;
use App\Enums\PaymentMethodType;
use App\Enums\SaleStatus;
use App\Models\CashSession;
use App\Models\PaymentMethod;
use App\Models\ProductLot;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Sales\SalePaymentValidatorService;
use App\Services\SettingsService;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Orchestrates the full POS checkout inside one transaction: build the sale
 * (via CreateSaleAction), validate payments, deduct inventory (selecting a
 * FEFO lot for lot/expiration-tracked products), record the cash movement
 * for the cash-affecting portion of the payment, and mark the sale
 * completed. Any failure rolls back everything — no partial sales.
 */
class CompleteSaleAction
{
    public function __construct(
        private readonly CreateSaleAction $createSale,
        private readonly SalePaymentValidatorService $paymentValidator,
        private readonly RecordInventoryMovementAction $recordMovement,
        private readonly RegisterCashMovementAction $recordCashMovement,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @param  array{
     *     branch_id: int, warehouse_id: int, register_id: int, cash_session_id?: int|null,
     *     customer_id: int, seller_id?: int|null, notes?: string|null, checkout_uuid?: string|null,
     *     general_discount?: array{type: string, value: numeric-string}|null,
     *     items: list<array{
     *         product_id: int, product_variant_id?: int|null, quantity: numeric-string,
     *         discount_type?: string|null, discount_value?: numeric-string|null, notes?: string|null,
     *     }>,
     * }  $data
     * @param  list<array{payment_method_id: int, amount: numeric-string, reference?: string|null, authorization_number?: string|null, card_last_four?: string|null, bank?: string|null, terminal?: string|null}>  $payments
     */
    public function execute(array $data, array $payments, User $cashier): Sale
    {
        return DB::transaction(function () use ($data, $payments, $cashier) {
            $checkoutUuid = $data['checkout_uuid'] ?? null;

            if ($checkoutUuid !== null) {
                $existing = Sale::query()->where('checkout_uuid', $checkoutUuid)->first();

                if ($existing !== null) {
                    return $existing->load(['items', 'payments']);
                }
            }

            $companyId = $cashier->company_id;
            $warehouse = Warehouse::query()->whereKey($data['warehouse_id'])->firstOrFail();

            if ($warehouse->company_id !== $companyId) {
                throw new InvalidArgumentException('El almacén no pertenece a esta empresa.');
            }

            $session = $this->resolveCashSession($data['cash_session_id'] ?? null, $companyId, $data['register_id'], $cashier);

            $sale = $this->createSale->execute([...$data, 'status' => SaleStatus::Draft], $cashier);

            $paymentMethods = PaymentMethod::query()->where('status', 'active')->get();
            $validated = $this->paymentValidator->validate($payments, (string) $sale->total, $paymentMethods);

            foreach ($payments as $payment) {
                $method = $paymentMethods->firstWhere('id', $payment['payment_method_id']);

                $sale->payments()->create([
                    'payment_method_id' => $method->id,
                    'amount' => Decimal::of((string) $payment['amount']),
                    'reference' => $payment['reference'] ?? null,
                    'authorization_number' => $payment['authorization_number'] ?? null,
                    'card_last_four' => $payment['card_last_four'] ?? null,
                    'bank' => $payment['bank'] ?? null,
                    'terminal' => $payment['terminal'] ?? null,
                    'status' => 'captured',
                    'paid_at' => now(),
                ]);
            }

            foreach ($sale->items as $item) {
                $lot = $this->selectLot($item, $warehouse->id);

                if ($lot !== null) {
                    $item->update(['product_lot_id' => $lot->id]);
                }

                $this->recordMovement->execute([
                    'company_id' => $companyId,
                    'branch_id' => $warehouse->branch_id,
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_lot_id' => $lot?->id,
                    'movement_type' => InventoryMovementType::Sale,
                    'quantity' => $item->quantity,
                    'unit_cost' => (string) $item->unit_cost,
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'reason' => "Venta {$sale->folio}",
                    'performed_by' => $cashier->id,
                ]);
            }

            $cashPortion = $payments === [] ? '0.0000' : collect($payments)
                ->filter(fn (array $p) => $paymentMethods->firstWhere('id', $p['payment_method_id'])?->type === PaymentMethodType::Cash)
                ->reduce(fn (string $carry, array $p) => bcadd($carry, Decimal::of((string) $p['amount']), 4), '0.0000');

            if ($session !== null && bccomp($cashPortion, '0', 4) > 0) {
                $netCash = bcsub($cashPortion, $validated['change_amount'], 4);

                if (bccomp($netCash, '0', 4) > 0) {
                    $this->recordCashMovement->execute($session, CashMovementType::Sale, $netCash, "Venta {$sale->folio} en efectivo", $cashier, null, $sale);
                }
            }

            $sale->update([
                'status' => SaleStatus::Completed,
                'amount_received' => $validated['amount_received'],
                'change_amount' => $validated['change_amount'],
                'completed_at' => now(),
            ]);

            return $sale->refresh()->load(['items', 'payments.paymentMethod']);
        });
    }

    private function resolveCashSession(?int $cashSessionId, int $companyId, int $registerId, User $cashier): ?CashSession
    {
        $requiresOpenRegister = (bool) ($this->settings->get($companyId, 'require_open_register') ?? true);

        if ($cashSessionId === null) {
            if ($requiresOpenRegister) {
                throw new InvalidArgumentException('Debes abrir tu caja antes de registrar una venta.');
            }

            return null;
        }

        /** @var CashSession $session */
        $session = CashSession::query()->whereKey($cashSessionId)->firstOrFail();

        if ($session->company_id !== $companyId || $session->register_id !== $registerId) {
            throw new InvalidArgumentException('La sesión de caja no corresponde a esta caja.');
        }

        if ($session->status !== CashSessionStatus::Open) {
            throw new InvalidArgumentException('La sesión de caja no está abierta.');
        }

        if ($session->user_id !== $cashier->id) {
            throw new InvalidArgumentException('Esta sesión de caja pertenece a otro usuario.');
        }

        return $session;
    }

    private function selectLot(SaleItem $item, int $warehouseId): ?ProductLot
    {
        $product = $item->product;

        if (! $product->tracking_type->usesLots()) {
            return null;
        }

        return ProductLot::query()
            ->where('product_id', $item->product_id)
            ->where('product_variant_id', $item->product_variant_id)
            ->where('status', 'active')
            ->whereExists(function ($query) use ($item, $warehouseId) {
                $query->selectRaw('1')
                    ->from('inventory_balances')
                    ->whereColumn('inventory_balances.product_lot_id', 'product_lots.id')
                    ->where('inventory_balances.warehouse_id', $warehouseId)
                    ->where('inventory_balances.quantity', '>=', $item->quantity);
            })
            ->orderByRaw('expiration_date is null')
            ->orderBy('expiration_date')
            ->first();
    }
}

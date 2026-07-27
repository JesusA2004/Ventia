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
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\Sales\SaleFolioService;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Handles full and partial returns. Each sale_item tracks how much of it has
 * already been returned via the sum of its sale_return_items, so a second
 * return against the same item can never exceed what's left to give back.
 */
class ProcessSaleReturnAction
{
    public function __construct(
        private readonly RecordInventoryMovementAction $recordMovement,
        private readonly RegisterCashMovementAction $recordCashMovement,
        private readonly SaleFolioService $folios,
    ) {}

    /**
     * @param  list<array{sale_item_id: int, quantity: numeric-string, restock?: bool}>  $items
     */
    public function execute(Sale $sale, array $items, string $reason, User $user): SaleReturn
    {
        if ($items === []) {
            throw new InvalidArgumentException('Debes seleccionar al menos una partida para devolver.');
        }

        return DB::transaction(function () use ($sale, $items, $reason, $user) {
            /** @var Sale $locked */
            $locked = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [SaleStatus::Completed, SaleStatus::PartiallyReturned], true)) {
                throw new InvalidArgumentException('Solo se pueden devolver ventas completadas.');
            }

            $return = SaleReturn::query()->create([
                'company_id' => $locked->company_id,
                'branch_id' => $locked->branch_id,
                'sale_id' => $locked->id,
                'cash_session_id' => $locked->cash_session_id,
                'user_id' => $user->id,
                'folio' => $this->folios->nextReturnFolio($locked->company_id),
                'status' => 'completed',
                'reason' => $reason,
                'processed_at' => now(),
            ]);

            $totalRefunded = '0.0000';
            $cashRefund = '0.0000';

            foreach ($items as $line) {
                /** @var SaleItem $item */
                $item = $locked->items()->whereKey($line['sale_item_id'])->firstOrFail();

                $alreadyReturned = $item->saleReturnItems()->get(['quantity'])
                    ->reduce(fn (string $carry, $r) => bcadd($carry, $r->quantity, 4), '0.0000');

                $remaining = bcsub($item->quantity, $alreadyReturned, 4);
                $quantity = Decimal::of((string) $line['quantity']);

                if (bccomp($quantity, '0', 4) <= 0) {
                    throw new InvalidArgumentException('La cantidad a devolver debe ser mayor a cero.');
                }

                if (bccomp($quantity, $remaining, 4) > 0) {
                    throw new InvalidArgumentException("La cantidad a devolver de «{$item->product_name_snapshot}» excede lo disponible para devolución.");
                }

                $unitTotal = bccomp($item->quantity, '0', 4) > 0 ? bcdiv($item->total, $item->quantity, 4) : '0.0000';
                $refundAmount = bcmul($unitTotal, $quantity, 4);
                $restock = $line['restock'] ?? true;

                $return->items()->create([
                    'sale_item_id' => $item->id,
                    'quantity' => $quantity,
                    'unit_price' => $item->unit_price,
                    'total_refunded' => $refundAmount,
                    'restocked' => $restock,
                ]);

                $totalRefunded = bcadd($totalRefunded, $refundAmount, 4);

                if ($restock) {
                    $this->recordMovement->execute([
                        'company_id' => $locked->company_id,
                        'branch_id' => $locked->branch_id,
                        'warehouse_id' => $locked->warehouse_id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'product_lot_id' => $item->product_lot_id,
                        'movement_type' => InventoryMovementType::SaleReturn,
                        'quantity' => $quantity,
                        'unit_cost' => (string) $item->unit_cost,
                        'reference_type' => SaleReturn::class,
                        'reference_id' => $return->id,
                        'reason' => "Devolución {$return->folio} de venta {$locked->folio}",
                        'performed_by' => $user->id,
                    ]);
                }
            }

            $return->update(['total_refunded' => $totalRefunded]);

            $cashPaymentTotal = $locked->payments()
                ->whereHas('paymentMethod', fn ($q) => $q->where('type', PaymentMethodType::Cash->value))
                ->get()
                ->reduce(fn (string $carry, $p) => bcadd($carry, Decimal::of((string) $p->amount), 4), '0.0000');

            if ($locked->cash_session_id !== null && bccomp($cashPaymentTotal, '0', 4) > 0) {
                $session = CashSession::query()->whereKey($locked->cash_session_id)->first();

                if ($session !== null && $session->status === CashSessionStatus::Open) {
                    $cashRefund = bccomp($totalRefunded, $cashPaymentTotal, 4) > 0 ? $cashPaymentTotal : $totalRefunded;

                    if (bccomp($cashRefund, '0', 4) > 0) {
                        $this->recordCashMovement->execute($session, CashMovementType::Refund, $cashRefund, "Devolución {$return->folio}", $user, null, $return);
                    }
                }
            }

            $totalOriginal = $locked->items()->get(['quantity'])->reduce(fn (string $c, $i) => bcadd($c, $i->quantity, 4), '0.0000');
            $totalReturnedAllTime = $locked->items->reduce(function (string $carry, SaleItem $item) {
                $returned = $item->saleReturnItems()->get(['quantity'])->reduce(fn (string $c, $r) => bcadd($c, $r->quantity, 4), '0.0000');

                return bcadd($carry, $returned, 4);
            }, '0.0000');

            $locked->update([
                'status' => bccomp($totalReturnedAllTime, $totalOriginal, 4) >= 0 ? SaleStatus::Returned : SaleStatus::PartiallyReturned,
            ]);

            return $return->load('items');
        });
    }
}

<?php

namespace App\Actions\Sales;

use App\Actions\Cash\RegisterCashMovementAction;
use App\Actions\Inventory\RecordInventoryMovementAction;
use App\Enums\CashMovementType;
use App\Enums\CashSessionStatus;
use App\Enums\InventoryMovementType;
use App\Enums\PaymentMethodType;
use App\Enums\SaleStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\CashSession;
use App\Models\Sale;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

/**
 * Cancels a completed sale: reverses every inventory movement (a SaleReturn
 * movement is the exact opposite of a Sale movement) and, if the cash
 * session that received the sale is still open, reverses the cash portion
 * too. Idempotent by construction: locking the sale row and checking its
 * exact current status means a second call always fails cleanly instead of
 * double-reversing.
 */
class CancelSaleAction
{
    public function __construct(
        private readonly RecordInventoryMovementAction $recordMovement,
        private readonly RegisterCashMovementAction $recordCashMovement,
    ) {}

    public function execute(Sale $sale, string $reason, User $canceller): Sale
    {
        return DB::transaction(function () use ($sale, $reason, $canceller) {
            /** @var Sale $locked */
            $locked = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== SaleStatus::Completed) {
                throw new InvalidStateTransitionException('la venta', $locked->status->value, 'cancelar');
            }

            foreach ($locked->items as $item) {
                $this->recordMovement->execute([
                    'company_id' => $locked->company_id,
                    'branch_id' => $locked->branch_id,
                    'warehouse_id' => $locked->warehouse_id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_lot_id' => $item->product_lot_id,
                    'movement_type' => InventoryMovementType::SaleReturn,
                    'quantity' => $item->quantity,
                    'unit_cost' => (string) $item->unit_cost,
                    'reference_type' => Sale::class,
                    'reference_id' => $locked->id,
                    'reason' => "Cancelación de venta {$locked->folio}",
                    'performed_by' => $canceller->id,
                ]);
            }

            if ($locked->cash_session_id !== null) {
                $session = CashSession::query()->whereKey($locked->cash_session_id)->first();

                if ($session !== null && $session->status === CashSessionStatus::Open) {
                    $cashRefund = $locked->payments()
                        ->whereHas('paymentMethod', fn ($q) => $q->where('type', PaymentMethodType::Cash->value))
                        ->get()
                        ->reduce(fn (string $carry, $payment) => bcadd($carry, Decimal::of((string) $payment->amount), 4), '0.0000');

                    if (bccomp($cashRefund, '0', 4) > 0) {
                        $this->recordCashMovement->execute($session, CashMovementType::Refund, $cashRefund, "Cancelación de venta {$locked->folio}", $canceller, null, $locked);
                    }
                }
            }

            $locked->update([
                'status' => SaleStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $canceller->id,
                'cancellation_reason' => $reason,
            ]);

            return $locked->refresh();
        });
    }
}

<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryMovementType;
use App\Enums\StockTransferStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\StockTransfer;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

class ReceiveTransferAction
{
    public function __construct(private readonly RecordInventoryMovementAction $recorder) {}

    /**
     * @param  array<int, string>  $receivedQuantities  stock_transfer_item_id => quantity received
     */
    public function execute(StockTransfer $transfer, array $receivedQuantities, User $user): StockTransfer
    {
        if (! in_array($transfer->status, [StockTransferStatus::InTransit, StockTransferStatus::PartiallyReceived], true)) {
            throw new InvalidStateTransitionException('la transferencia', $transfer->status->label(), 'recibirla');
        }

        return DB::transaction(function () use ($transfer, $receivedQuantities, $user) {
            $fullyReceived = true;

            foreach ($transfer->items as $item) {
                $alreadyReceived = $item->quantity_received ?? '0';
                $shipped = $item->quantity_shipped ?? '0';
                $rawInput = $receivedQuantities[$item->id] ?? null;
                $newlyReceived = $rawInput !== null ? Decimal::of($rawInput) : null;

                if ($newlyReceived === null || bccomp($newlyReceived, '0', 4) <= 0) {
                    $fullyReceived = $fullyReceived && bccomp($alreadyReceived, $shipped, 4) >= 0;

                    continue;
                }

                $this->recorder->execute([
                    'company_id' => $transfer->company_id,
                    'branch_id' => $transfer->destination_branch_id,
                    'warehouse_id' => $transfer->destination_warehouse_id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_lot_id' => $item->product_lot_id,
                    'movement_type' => InventoryMovementType::TransferIn,
                    'quantity' => $newlyReceived,
                    'unit_cost' => $item->unit_cost,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'reason' => "Transferencia {$transfer->folio}",
                    'performed_by' => $user->id,
                ]);

                $totalReceived = bcadd($alreadyReceived, $newlyReceived, 4);
                $item->update(['quantity_received' => $totalReceived]);

                $fullyReceived = $fullyReceived && bccomp($totalReceived, $shipped, 4) >= 0;
            }

            $transfer->update([
                'status' => $fullyReceived ? StockTransferStatus::Received : StockTransferStatus::PartiallyReceived,
                'received_by' => $user->id,
                'received_at' => now(),
            ]);

            return $transfer->fresh('items');
        });
    }
}

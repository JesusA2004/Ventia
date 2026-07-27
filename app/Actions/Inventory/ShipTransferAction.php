<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryMovementType;
use App\Enums\StockTransferStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ShipTransferAction
{
    public function __construct(private readonly RecordInventoryMovementAction $recorder) {}

    public function execute(StockTransfer $transfer, User $user): StockTransfer
    {
        if ($transfer->status !== StockTransferStatus::Approved) {
            throw new InvalidStateTransitionException('la transferencia', $transfer->status->label(), 'marcarla en tránsito');
        }

        return DB::transaction(function () use ($transfer, $user) {
            foreach ($transfer->items as $item) {
                $this->recorder->execute([
                    'company_id' => $transfer->company_id,
                    'branch_id' => $transfer->origin_branch_id,
                    'warehouse_id' => $transfer->origin_warehouse_id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_lot_id' => $item->product_lot_id,
                    'movement_type' => InventoryMovementType::TransferOut,
                    'quantity' => $item->quantity_requested,
                    'unit_cost' => $item->unit_cost,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'reason' => "Transferencia {$transfer->folio}",
                    'performed_by' => $user->id,
                ]);

                $item->update(['quantity_shipped' => $item->quantity_requested]);
            }

            $transfer->update([
                'status' => StockTransferStatus::InTransit,
                'shipped_by' => $user->id,
                'shipped_at' => now(),
            ]);

            return $transfer->fresh('items');
        });
    }
}

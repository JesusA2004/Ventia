<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryMovementType;
use App\Enums\StockCountStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\StockCount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApplyStockCountAction
{
    public function __construct(private readonly RecordInventoryMovementAction $recorder) {}

    public function execute(StockCount $count, User $user): StockCount
    {
        // Guarding on Completed (not Applied) is what makes this idempotent:
        // a second call always fails instead of double-adjusting stock.
        if ($count->status !== StockCountStatus::Completed) {
            throw new InvalidStateTransitionException('el conteo físico', $count->status->label(), 'aplicarlo');
        }

        return DB::transaction(function () use ($count, $user) {
            foreach ($count->items as $item) {
                if ($item->counted_quantity === null || $item->difference === null || bccomp($item->difference, '0', 4) === 0) {
                    continue;
                }

                $difference = $item->difference;
                $isPositive = bccomp($difference, '0', 4) > 0;

                $this->recorder->execute([
                    'company_id' => $count->company_id,
                    'branch_id' => $count->branch_id,
                    'warehouse_id' => $count->warehouse_id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_lot_id' => $item->product_lot_id,
                    'movement_type' => $isPositive ? InventoryMovementType::AdjustmentIn : InventoryMovementType::AdjustmentOut,
                    'quantity' => $isPositive ? $difference : bcmul($difference, '-1', 4),
                    'unit_cost' => $item->unit_cost,
                    'reference_type' => StockCount::class,
                    'reference_id' => $count->id,
                    'reason' => "Conteo físico {$count->folio}",
                    'performed_by' => $user->id,
                ]);
            }

            $count->update([
                'status' => StockCountStatus::Applied,
                'applied_by' => $user->id,
                'applied_at' => now(),
            ]);

            return $count->fresh('items');
        });
    }
}

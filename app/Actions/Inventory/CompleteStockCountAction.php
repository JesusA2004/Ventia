<?php

namespace App\Actions\Inventory;

use App\Enums\StockCountStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\StockCount;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

class CompleteStockCountAction
{
    /**
     * @param  array<int, string>  $countedQuantities  stock_count_item_id => physically counted quantity
     */
    public function execute(StockCount $count, array $countedQuantities, User $user): StockCount
    {
        if ($count->status !== StockCountStatus::Counting) {
            throw new InvalidStateTransitionException('el conteo físico', $count->status->label(), 'completarlo');
        }

        return DB::transaction(function () use ($count, $countedQuantities, $user) {
            foreach ($count->items as $item) {
                if (! array_key_exists($item->id, $countedQuantities)) {
                    continue;
                }

                $counted = Decimal::of($countedQuantities[$item->id]);

                $item->update([
                    'counted_quantity' => $counted,
                    'difference' => bcsub($counted, $item->expected_quantity, 4),
                ]);
            }

            $count->update([
                'status' => StockCountStatus::Completed,
                'completed_by' => $user->id,
                'completed_at' => now(),
            ]);

            return $count->fresh('items');
        });
    }
}

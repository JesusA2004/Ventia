<?php

namespace App\Actions\Inventory;

use App\Enums\StockCountStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\StockCount;

class CancelStockCountAction
{
    public function execute(StockCount $count): StockCount
    {
        if (! in_array($count->status, [StockCountStatus::Draft, StockCountStatus::Counting], true)) {
            throw new InvalidStateTransitionException('el conteo físico', $count->status->label(), 'cancelarlo');
        }

        $count->update(['status' => StockCountStatus::Cancelled, 'cancelled_at' => now()]);

        return $count;
    }
}

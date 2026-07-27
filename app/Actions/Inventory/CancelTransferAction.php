<?php

namespace App\Actions\Inventory;

use App\Enums\StockTransferStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\StockTransfer;

class CancelTransferAction
{
    public function execute(StockTransfer $transfer): StockTransfer
    {
        if (! $transfer->status->isCancellable()) {
            throw new InvalidStateTransitionException('la transferencia', $transfer->status->label(), 'cancelarla');
        }

        $transfer->update([
            'status' => StockTransferStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return $transfer;
    }
}

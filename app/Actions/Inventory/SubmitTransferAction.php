<?php

namespace App\Actions\Inventory;

use App\Enums\StockTransferStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\StockTransfer;

class SubmitTransferAction
{
    public function execute(StockTransfer $transfer): StockTransfer
    {
        if ($transfer->status !== StockTransferStatus::Draft) {
            throw new InvalidStateTransitionException('la transferencia', $transfer->status->label(), 'enviarla a aprobación');
        }

        $transfer->update([
            'status' => StockTransferStatus::Pending,
            'requested_at' => now(),
        ]);

        return $transfer;
    }
}

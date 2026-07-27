<?php

namespace App\Actions\Inventory;

use App\Enums\StockTransferStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\StockTransfer;
use App\Models\User;

class ApproveTransferAction
{
    public function execute(StockTransfer $transfer, User $user): StockTransfer
    {
        if ($transfer->status !== StockTransferStatus::Pending) {
            throw new InvalidStateTransitionException('la transferencia', $transfer->status->label(), 'aprobarla');
        }

        $transfer->update([
            'status' => StockTransferStatus::Approved,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return $transfer;
    }
}

<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.transfer') || $user->can('inventory.view');
    }

    public function view(User $user, StockTransfer $transfer): bool
    {
        return $this->viewAny($user) && $user->company_id === $transfer->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.transfer');
    }

    public function manage(User $user, StockTransfer $transfer): bool
    {
        return $user->can('inventory.transfer') && $user->company_id === $transfer->company_id;
    }
}

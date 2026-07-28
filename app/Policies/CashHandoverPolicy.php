<?php

namespace App\Policies;

use App\Models\CashHandover;
use App\Models\User;

class CashHandoverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cash.approve-close') || $user->can('cash.receive-handover');
    }

    public function view(User $user, CashHandover $handover): bool
    {
        if ($user->company_id !== $handover->company_id) {
            return false;
        }

        return $handover->cashier_id === $user->id
            || $user->can('cash.approve-close')
            || $user->can('cash.receive-handover');
    }
}

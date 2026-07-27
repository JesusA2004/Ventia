<?php

namespace App\Policies;

use App\Models\CashRegister;
use App\Models\User;

class CashRegisterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('registers.manage');
    }

    public function view(User $user, CashRegister $register): bool
    {
        return $user->can('registers.manage') && $user->company_id === $register->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('registers.manage');
    }

    public function update(User $user, CashRegister $register): bool
    {
        return $user->can('registers.manage') && $user->company_id === $register->company_id;
    }

    public function delete(User $user, CashRegister $register): bool
    {
        return $user->can('registers.manage') && $user->company_id === $register->company_id;
    }
}

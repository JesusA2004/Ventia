<?php

namespace App\Policies;

use App\Models\Tax;
use App\Models\User;

class TaxPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('taxes.manage');
    }

    public function view(User $user, Tax $tax): bool
    {
        return $user->can('taxes.manage') && $user->company_id === $tax->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('taxes.manage');
    }

    public function update(User $user, Tax $tax): bool
    {
        return $user->can('taxes.manage') && $user->company_id === $tax->company_id;
    }

    public function delete(User $user, Tax $tax): bool
    {
        return $user->can('taxes.manage') && $user->company_id === $tax->company_id;
    }
}

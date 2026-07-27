<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('warehouses.manage');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.manage') && $user->company_id === $warehouse->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('warehouses.manage');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.manage') && $user->company_id === $warehouse->company_id;
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->can('warehouses.manage') && $user->company_id === $warehouse->company_id;
    }
}

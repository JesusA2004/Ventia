<?php

namespace App\Policies;

use App\Models\ProductLot;
use App\Models\User;

class ProductLotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, ProductLot $lot): bool
    {
        return $user->can('inventory.view') && $user->company_id === $lot->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.adjust');
    }

    public function update(User $user, ProductLot $lot): bool
    {
        return $user->can('inventory.adjust') && $user->company_id === $lot->company_id;
    }

    public function delete(User $user, ProductLot $lot): bool
    {
        return $user->can('inventory.adjust') && $user->company_id === $lot->company_id;
    }
}

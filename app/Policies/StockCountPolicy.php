<?php

namespace App\Policies;

use App\Models\StockCount;
use App\Models\User;

class StockCountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.count') || $user->can('inventory.view');
    }

    public function view(User $user, StockCount $count): bool
    {
        return $this->viewAny($user) && $user->company_id === $count->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.count');
    }

    public function manage(User $user, StockCount $count): bool
    {
        return $user->can('inventory.count') && $user->company_id === $count->company_id;
    }
}

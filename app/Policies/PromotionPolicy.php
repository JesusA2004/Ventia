<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('promotions.manage');
    }

    public function view(User $user, Promotion $promotion): bool
    {
        return $user->can('promotions.manage') && $user->company_id === $promotion->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('promotions.manage');
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return $user->can('promotions.manage') && $user->company_id === $promotion->company_id;
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return $user->can('promotions.manage') && $user->company_id === $promotion->company_id;
    }
}

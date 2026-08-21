<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('coupons.manage');
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.manage') && $user->company_id === $coupon->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('coupons.manage');
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.manage') && $user->company_id === $coupon->company_id;
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $user->can('coupons.manage') && $user->company_id === $coupon->company_id;
    }
}

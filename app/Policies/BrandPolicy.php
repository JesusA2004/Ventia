<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('brands.manage');
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->can('brands.manage') && $user->company_id === $brand->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('brands.manage');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->can('brands.manage') && $user->company_id === $brand->company_id;
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->can('brands.manage') && $user->company_id === $brand->company_id;
    }
}

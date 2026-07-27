<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('categories.manage');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('categories.manage') && $user->company_id === $category->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('categories.manage');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('categories.manage') && $user->company_id === $category->company_id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('categories.manage') && $user->company_id === $category->company_id;
    }
}

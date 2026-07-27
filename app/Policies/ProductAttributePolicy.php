<?php

namespace App\Policies;

use App\Models\ProductAttribute;
use App\Models\User;

class ProductAttributePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    public function view(User $user, ProductAttribute $attribute): bool
    {
        return $user->can('products.view') && $user->company_id === $attribute->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('products.create') || $user->can('products.update');
    }

    public function update(User $user, ProductAttribute $attribute): bool
    {
        return ($user->can('products.create') || $user->can('products.update')) && $user->company_id === $attribute->company_id;
    }

    public function delete(User $user, ProductAttribute $attribute): bool
    {
        return ($user->can('products.create') || $user->can('products.update')) && $user->company_id === $attribute->company_id;
    }
}

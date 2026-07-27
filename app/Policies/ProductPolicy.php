<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('products.view') && $user->company_id === $product->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('products.update') && $user->company_id === $product->company_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('products.delete') && $user->company_id === $product->company_id;
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->can('products.delete') && $user->company_id === $product->company_id;
    }

    public function viewCosts(User $user, Product $product): bool
    {
        return $user->can('products.view-costs') && $user->company_id === $product->company_id;
    }

    public function editPrice(User $user, Product $product): bool
    {
        return $user->can('prices.edit') && $user->company_id === $product->company_id;
    }

    public function editCost(User $user, Product $product): bool
    {
        return $user->can('costs.edit') && $user->company_id === $product->company_id;
    }

    public function viewPriceHistory(User $user, Product $product): bool
    {
        return $user->can('prices.view-history') && $user->company_id === $product->company_id;
    }
}

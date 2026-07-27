<?php

namespace App\Policies;

use App\Models\PriceList;
use App\Models\User;

class PriceListPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('price-lists.manage');
    }

    public function view(User $user, PriceList $priceList): bool
    {
        return $user->can('price-lists.manage') && $user->company_id === $priceList->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('price-lists.manage');
    }

    public function update(User $user, PriceList $priceList): bool
    {
        return $user->can('price-lists.manage') && $user->company_id === $priceList->company_id;
    }

    public function delete(User $user, PriceList $priceList): bool
    {
        return $user->can('price-lists.manage') && $user->company_id === $priceList->company_id;
    }
}

<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.view');
    }

    public function view(User $user, Sale $sale): bool
    {
        if ($user->company_id !== $sale->company_id) {
            return false;
        }

        if (! $user->can('sales.view')) {
            return false;
        }

        return $user->can('cash.view') || $sale->cashier_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('sales.create');
    }

    public function cancel(User $user, Sale $sale): bool
    {
        return $user->can('sales.cancel') && $user->company_id === $sale->company_id;
    }

    public function return(User $user, Sale $sale): bool
    {
        return $user->can('sales.return') && $user->company_id === $sale->company_id;
    }

    public function reprintTicket(User $user, Sale $sale): bool
    {
        return $user->can('sales.reprint-ticket') && $user->company_id === $sale->company_id;
    }
}

<?php

namespace App\Policies;

use App\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payment-methods.manage');
    }

    public function view(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->can('payment-methods.manage') && $user->company_id === $paymentMethod->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('payment-methods.manage');
    }

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->can('payment-methods.manage') && $user->company_id === $paymentMethod->company_id;
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->can('payment-methods.manage') && $user->company_id === $paymentMethod->company_id;
    }
}

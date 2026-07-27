<?php

namespace App\Policies;

use App\Models\CashSession;
use App\Models\User;

class CashSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cash.view') || $user->can('cash.open');
    }

    public function view(User $user, CashSession $session): bool
    {
        if ($user->company_id !== $session->company_id) {
            return false;
        }

        return $user->can('cash.view') || $session->user_id === $user->id;
    }

    public function open(User $user): bool
    {
        return $user->can('cash.open');
    }

    public function close(User $user, CashSession $session): bool
    {
        if ($user->company_id !== $session->company_id) {
            return false;
        }

        if (! $user->can('cash.close')) {
            return false;
        }

        return $session->user_id === $user->id || $user->can('cash.force-close');
    }

    public function forceClose(User $user, CashSession $session): bool
    {
        return $user->company_id === $session->company_id && $user->can('cash.force-close');
    }

    public function registerMovement(User $user, CashSession $session): bool
    {
        return $user->company_id === $session->company_id && $session->user_id === $user->id;
    }
}

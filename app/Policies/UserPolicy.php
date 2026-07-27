<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function view(User $user, User $target): bool
    {
        $this->guardAgainstCrossCompanyAccess($user, $target);

        return $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $target): bool
    {
        $this->guardAgainstCrossCompanyAccess($user, $target);

        return $user->can('users.manage');
    }

    public function delete(User $user, User $target): bool
    {
        $this->guardAgainstCrossCompanyAccess($user, $target);

        return $user->can('users.manage') && $user->id !== $target->id;
    }

    /**
     * Users from another company are treated as non-existent (404) rather
     * than forbidden (403), so we don't leak that a given ID belongs to
     * someone else's tenant.
     */
    private function guardAgainstCrossCompanyAccess(User $user, User $target): void
    {
        if ($user->company_id !== $target->company_id) {
            abort(404);
        }
    }
}

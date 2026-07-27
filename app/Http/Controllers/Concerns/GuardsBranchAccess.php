<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Company isolation (via CompanyScope + Form Request rules) is not enough on its own:
 * a user restricted to specific branches must also be blocked from operating on a
 * warehouse that belongs to a *different branch of their own company*.
 */
trait GuardsBranchAccess
{
    /**
     * @throws AuthorizationException
     */
    protected function assertBranchAccessible(User $user, ?int $branchId): void
    {
        if ($branchId === null) {
            return;
        }

        if ($user->canAccessAllBranches()) {
            return;
        }

        if (! $user->branches->contains('id', $branchId)) {
            throw new AuthorizationException('No tienes acceso a la sucursal de este almacén.');
        }
    }
}

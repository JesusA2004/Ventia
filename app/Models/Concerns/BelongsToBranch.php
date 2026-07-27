<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adds the branch relation plus an explicit "accessibleBy" scope.
 *
 * Branch visibility is intentionally NOT a blanket global scope: roles like
 * Admin/Gerente must see every branch in their company, while Cajero/Encargado
 * are limited to their assigned branches. Callers opt in via ->accessibleBy($user).
 */
trait BelongsToBranch
{
    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->canAccessAllBranches()) {
            return $query;
        }

        return $query->whereIn($query->getModel()->qualifyColumn('branch_id'), $user->branches()->pluck('branches.id'));
    }
}

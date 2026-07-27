<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts every query to the authenticated user's company.
 *
 * Superadministrators (users with no company_id) see every company and are
 * never scoped, since they must be able to manage the whole platform.
 *
 * @implements Scope<Model>
 */
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user || $user->company_id === null) {
            return;
        }

        $builder->where($model->qualifyColumn('company_id'), $user->company_id);
    }
}

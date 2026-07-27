<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Like CompanyScope, but also exposes rows with a NULL company_id: system-wide
 * catalog entries (e.g. base units of measure) that every company can see
 * and use, alongside their own custom ones.
 *
 * @implements Scope<Model>
 */
class CompanyOrGlobalScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user || $user->company_id === null) {
            return;
        }

        $column = $model->qualifyColumn('company_id');

        $builder->where(fn (Builder $query) => $query
            ->where($column, $user->company_id)
            ->orWhereNull($column));
    }
}

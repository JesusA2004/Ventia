<?php

namespace App\Models\Scopes;

use App\Services\ActiveCompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts every query to the authenticated user's company.
 *
 * Superadministrators (users with no company_id) are scoped to whichever
 * company they've selected as active (via the company switcher), if any.
 * With no selection made, they remain deliberately unscoped so they retain
 * cross-company oversight (e.g. Catálogo screens used to audit any tenant).
 *
 * @implements Scope<Model>
 */
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        if ($user->company_id !== null) {
            $builder->where($model->qualifyColumn('company_id'), $user->company_id);

            return;
        }

        $activeCompanyId = App::make(ActiveCompanyContext::class)->companyId();

        if ($activeCompanyId !== null) {
            $builder->where($model->qualifyColumn('company_id'), $activeCompanyId);
        }
    }
}

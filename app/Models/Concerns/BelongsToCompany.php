<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use App\Services\ActiveCompanyContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/**
 * Scopes the model to the current user's company and auto-fills company_id
 * on create. This is the multi-tenant boundary: every tenant-owned model
 * must use this trait so cross-company access is impossible by default.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if ($model->company_id || ! Auth::check()) {
                return;
            }

            // Regular users always resolve to their own company; a
            // superadmin (company_id === null) resolves to whichever
            // company they've selected as active. requireCompanyId() (not
            // the nullable companyId()) is deliberate: a superadmin with no
            // active selection must never silently create a record with
            // company_id = null — they get NoActiveCompanySelectedException
            // instead, which renders as a friendly redirect, not a 500.
            $model->company_id = App::make(ActiveCompanyContext::class)->requireCompanyId();
        });
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

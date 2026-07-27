<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyOrGlobalScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Variant of BelongsToCompany for catalogs that may have system-wide entries
 * (company_id null) alongside per-company ones, e.g. units of measure.
 */
trait BelongsToCompanyOrGlobal
{
    public static function bootBelongsToCompanyOrGlobal(): void
    {
        static::addGlobalScope(new CompanyOrGlobalScope);

        static::creating(function ($model) {
            if (! $model->company_id && Auth::check()) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

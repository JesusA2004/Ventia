<?php

namespace App\Http\Requests\Concerns;

use App\Services\ActiveCompanyContext;
use Illuminate\Support\Facades\App;

/**
 * rules() runs before the model exists, so it cannot rely on
 * $this->user()->company_id for tenant scoping: that's null for a
 * superadministrator, which makes every Rule::exists/unique 'company_id'
 * filter match nothing. Resolve the same active-company context the rest of
 * the write path (BelongsToCompany, controllers) already uses, so a
 * superadmin acting on their selected company validates correctly.
 */
trait ResolvesActiveCompany
{
    protected function activeCompanyId(): ?int
    {
        return App::make(ActiveCompanyContext::class)->companyId();
    }
}

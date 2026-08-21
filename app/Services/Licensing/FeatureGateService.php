<?php

namespace App\Services\Licensing;

use App\Enums\Feature;
use App\Enums\Plan;
use App\Models\Company;
use App\Models\CompanyEntitlement;
use App\Services\ActiveCompanyContext;

/**
 * The single place that answers "does the active company have feature X".
 * Every consumer — the `feature` route middleware, Policies, controllers,
 * the shared Inertia prop the frontend/sidebar reads, reports — goes
 * through here instead of comparing `plan === 'pro'` locally. A company with
 * no CompanyEntitlement row is Basic; that's what "no entitlement yet"
 * means, not an error.
 */
class FeatureGateService
{
    /** @var array<int, Plan> */
    private array $planCache = [];

    public function __construct(private readonly ActiveCompanyContext $activeCompany) {}

    public function plan(): Plan
    {
        $company = $this->activeCompany->company();

        return $company === null ? Plan::Basic : $this->planFor($company);
    }

    public function planFor(Company $company): Plan
    {
        return $this->planCache[$company->id] ??= $this->entitlementFor($company)?->plan ?? Plan::Basic;
    }

    public function entitlementFor(Company $company): ?CompanyEntitlement
    {
        return CompanyEntitlement::query()->where('company_id', $company->id)->first();
    }

    public function allows(Feature $feature): bool
    {
        return $this->plan()->includesFeature($feature);
    }

    public function companyAllows(Company $company, Feature $feature): bool
    {
        return $this->planFor($company)->includesFeature($feature);
    }

    /** @return list<string> feature slugs the active company's plan includes, for the shared Inertia prop */
    public function activeFeatureSlugs(): array
    {
        return array_map(fn (Feature $f) => $f->value, $this->plan()->features());
    }
}

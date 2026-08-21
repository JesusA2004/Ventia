<?php

namespace App\Actions\Licensing;

use App\Enums\Plan;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\Company;
use App\Models\CompanyEntitlement;
use App\Models\User;

/**
 * Downgrades a company back to Basic. Deliberately does not touch the
 * LicenseKey that funded the entitlement — its status stays Redeemed
 * forever, so it can never be handed to a different company. A legitimate
 * transfer of a license from one company to another is a distinct,
 * Superadmin-only, audited action — not implemented as "revoke and
 * re-redeem".
 */
class DeactivateCompanyProAction
{
    public function execute(Company $company, User $actor): CompanyEntitlement
    {
        $entitlement = CompanyEntitlement::query()->where('company_id', $company->id)->first();

        if ($entitlement === null || $entitlement->plan !== Plan::Pro) {
            throw new InvalidStateTransitionException('la empresa', Plan::Basic->label(), 'desactivar Ventia Pro');
        }

        $entitlement->update([
            'plan' => Plan::Basic,
            'deactivated_at' => now(),
            'deactivated_by' => $actor->id,
        ]);

        return $entitlement;
    }
}

<?php

namespace App\Actions\Licensing;

use App\Enums\LicenseKeyStatus;
use App\Exceptions\InvalidLicenseKeyException;
use App\Models\Company;
use App\Models\CompanyEntitlement;
use App\Models\LicenseKey;
use App\Models\User;
use App\Support\LicenseCode;
use Illuminate\Support\Facades\DB;

/**
 * Redeems a serial for a company, activating (or re-activating) Ventia Pro.
 * lockForUpdate() on the license_keys row is what makes this safe under
 * concurrency: two simultaneous requests with the same code will serialize
 * on the row lock, and the loser sees status already Redeemed once its turn
 * comes, so it fails cleanly instead of both succeeding.
 */
class RedeemLicenseKeyAction
{
    /**
     * @throws InvalidLicenseKeyException
     */
    public function execute(string $rawCode, Company $company, User $activatedBy): CompanyEntitlement
    {
        $normalized = LicenseCode::normalize($rawCode);

        if ($normalized === '') {
            throw InvalidLicenseKeyException::notFound();
        }

        $hash = LicenseCode::hash($normalized);

        return DB::transaction(function () use ($hash, $company, $activatedBy) {
            $key = LicenseKey::query()->where('code_hash', $hash)->lockForUpdate()->first();

            if ($key === null) {
                throw InvalidLicenseKeyException::notFound();
            }

            if ($key->status === LicenseKeyStatus::Revoked) {
                throw InvalidLicenseKeyException::revoked();
            }

            if ($key->status === LicenseKeyStatus::Redeemed) {
                throw InvalidLicenseKeyException::alreadyUsed();
            }

            $key->update([
                'status' => LicenseKeyStatus::Redeemed,
                'company_id' => $company->id,
                'redeemed_at' => now(),
                'redeemed_by' => $activatedBy->id,
            ]);

            $entitlement = CompanyEntitlement::query()->lockForUpdate()->firstOrNew(['company_id' => $company->id]);
            $entitlement->fill([
                'plan' => $key->plan,
                'activated_at' => now(),
                'activated_by' => $activatedBy->id,
                'license_key_id' => $key->id,
                'deactivated_at' => null,
                'deactivated_by' => null,
            ]);
            $entitlement->save();

            return $entitlement;
        });
    }
}

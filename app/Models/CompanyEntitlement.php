<?php

namespace App\Models;

use App\Enums\Plan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The commercial plan a single company is entitled to — the row
 * FeatureGateService reads from. Deliberately not scoped by
 * BelongsToCompany: it's read for the active company via explicit
 * `where('company_id', ...)` lookups (see FeatureGateService), and the
 * Superadmin "Licencias Ventia" screen needs to see every company's plan at
 * once, which a company-scoped global scope would break.
 *
 * A company with no row here is Basic — see FeatureGateService::plan().
 * Downgrading (deactivating Pro) updates this row but never touches the
 * LicenseKey that funded it: a burned serial never becomes reusable.
 *
 * @property int $id
 * @property int $company_id
 * @property Plan $plan
 * @property \Illuminate\Support\Carbon|null $activated_at
 * @property int|null $activated_by
 * @property int|null $license_key_id
 * @property \Illuminate\Support\Carbon|null $deactivated_at
 * @property int|null $deactivated_by
 */
#[Fillable([
    'company_id', 'plan', 'activated_at', 'activated_by', 'license_key_id', 'deactivated_at', 'deactivated_by',
])]
class CompanyEntitlement extends Model
{
    protected function casts(): array
    {
        return [
            'plan' => Plan::class,
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function activatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /** @return BelongsTo<LicenseKey, $this> */
    public function licenseKey(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class, 'license_key_id');
    }
}

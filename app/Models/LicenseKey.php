<?php

namespace App\Models;

use App\Enums\LicenseKeyStatus;
use App\Enums\Plan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single Ventia Pro activation serial. Not scoped by BelongsToCompany on
 * purpose — the whole point is that a Superadministrator manages a pool of
 * keys that don't belong to any company until redeemed (company_id is null
 * until then), and needs to see across every company afterward. Only
 * Superadministrator-facing code should ever query this model — see
 * LicenseKeyPolicy.
 *
 * The plaintext code is never stored: code_hash is a keyed SHA-256 (see
 * GenerateLicenseKeysAction) and code_last4 exists only to render a masked
 * value like "****-****-****-7KQ9" after the one-time reveal.
 *
 * @property int $id
 * @property string $code_hash
 * @property string $code_last4
 * @property Plan $plan
 * @property LicenseKeyStatus $status
 * @property int|null $company_id
 * @property int $generated_by
 * @property \Illuminate\Support\Carbon|null $redeemed_at
 * @property int|null $redeemed_by
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property int|null $revoked_by
 * @property int|null $replaces_license_key_id
 * @property string|null $notes
 */
#[Fillable([
    'code_hash', 'code_last4', 'plan', 'status', 'company_id', 'generated_by',
    'redeemed_at', 'redeemed_by', 'revoked_at', 'revoked_by', 'replaces_license_key_id', 'notes',
])]
class LicenseKey extends Model
{
    protected function casts(): array
    {
        return [
            'plan' => Plan::class,
            'status' => LicenseKeyStatus::class,
            'redeemed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** Masked display, e.g. "****-****-****-{$last4}". */
    public function masked(): string
    {
        return "****-****-****-{$this->code_last4}";
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function generatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function redeemedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function revokedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /** @return BelongsTo<LicenseKey, $this> */
    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_license_key_id');
    }
}

<?php

namespace App\Actions\Licensing;

use App\Enums\LicenseKeyStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\LicenseKey;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Burns a serial permanently — from Available (it can never be redeemed) or
 * from Redeemed (the company that used it keeps Pro until a Superadmin
 * separately deactivates it via DeactivateCompanyProAction; revoking the key
 * only prevents it from ever being redeemed again elsewhere). A revoked key
 * never returns to Available: reissue() mints a brand new key instead of
 * reusing this one, so "revoke and hand out again" is never possible by
 * accident.
 */
class RevokeLicenseKeyAction
{
    public function __construct(private readonly GenerateLicenseKeysAction $generate) {}

    public function execute(LicenseKey $key, User $revokedBy, ?string $reason = null): LicenseKey
    {
        if ($key->status === LicenseKeyStatus::Revoked) {
            throw new InvalidStateTransitionException('el serial', $key->status->label(), 'revocarlo de nuevo');
        }

        $key->update([
            'status' => LicenseKeyStatus::Revoked,
            'revoked_at' => now(),
            'revoked_by' => $revokedBy->id,
            'notes' => $reason !== null ? trim(($key->notes ?? '')."\n".$reason) : $key->notes,
        ]);

        return $key;
    }

    /**
     * Revokes $key and mints a replacement in one transaction — the
     * "reemitir de manera controlada" flow: the old serial is unusable and
     * the new one is traceable back to it via replaces_license_key_id.
     *
     * @return array{key: LicenseKey, plain: string}
     */
    public function reissue(LicenseKey $key, User $actor, ?string $reason = null): array
    {
        return DB::transaction(function () use ($key, $actor, $reason) {
            $this->execute($key, $actor, $reason ?? 'Reemitido');

            return $this->generate->execute($key->plan, 1, $actor, 'Reemisión del serial '.$key->masked(), $key)[0];
        });
    }
}

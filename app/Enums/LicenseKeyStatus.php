<?php

namespace App\Enums;

/**
 * Lifecycle of a single license serial (see LicenseKey). A key is generated
 * `Available`, becomes `Redeemed` exactly once (RedeemLicenseKeyAction locks
 * the row so two simultaneous redemptions can't both win), and can be moved
 * to `Revoked` by a Superadministrator from either state. Revoked never
 * reverts to Available — a burned serial stays burned; see
 * RevokeLicenseKeyAction.
 */
enum LicenseKeyStatus: string
{
    case Available = 'available';
    case Redeemed = 'redeemed';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Redeemed => 'Canjeado',
            self::Revoked => 'Revocado',
        };
    }
}

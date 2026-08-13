<?php

namespace App\Support;

/**
 * Small fixed list of dial codes for the customer phone field — deliberately
 * not backed by a full country/phone library (see item #4 in the UX
 * request this shipped with). Mirrored in resources/js/lib/phone-codes.ts
 * for the frontend Select; keep both in sync if this list changes.
 */
final class PhoneCountryCodes
{
    public const DEFAULT = '+52';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            '+52', '+1', '+34', '+54', '+55', '+56', '+57', '+58',
            '+51', '+593', '+591', '+595', '+598', '+502', '+503',
            '+504', '+505', '+506', '+507',
        ];
    }
}

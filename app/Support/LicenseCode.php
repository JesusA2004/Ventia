<?php

namespace App\Support;

/**
 * Generates and hashes Ventia Pro activation serials.
 *
 * Plaintext is never persisted (see LicenseKey): GenerateLicenseKeysAction
 * shows it to the Superadmin exactly once and only `hash()` is stored.
 * Characters come from random_int(), PHP's CSPRNG-backed function, drawn
 * from a 32-symbol alphabet that excludes visually ambiguous characters
 * (0/O, 1/I/L) so a human can transcribe a code without errors. 20 symbols
 * of a 32-symbol alphabet is 100 bits of entropy — far beyond what's
 * guessable, so the code needs no separate secret to be safe to email to a
 * customer.
 */
class LicenseCode
{
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const LENGTH = 20;

    private const GROUP_SIZE = 4;

    /** @return array{plain: string, normalized: string, hash: string, last4: string} */
    public static function generate(): array
    {
        $chars = '';
        $alphabetLength = strlen(self::ALPHABET);

        for ($i = 0; $i < self::LENGTH; $i++) {
            $chars .= self::ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        $plain = implode('-', str_split($chars, self::GROUP_SIZE));

        return [
            'plain' => $plain,
            'normalized' => $chars,
            'hash' => self::hash($chars),
            'last4' => substr($chars, -4),
        ];
    }

    /** Strips formatting/whitespace and uppercases, so "xxxx xxxx-xxxx" and "XXXXXXXX..." hash the same. */
    public static function normalize(string $raw): string
    {
        return strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $raw) ?? '');
    }

    /** Keyed hash (pepper = APP_KEY) so a database leak alone can't be redeemed without also having the app key. */
    public static function hash(string $normalized): string
    {
        return hash_hmac('sha256', $normalized, config('app.key'));
    }
}

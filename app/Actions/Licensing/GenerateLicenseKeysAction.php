<?php

namespace App\Actions\Licensing;

use App\Enums\LicenseKeyStatus;
use App\Enums\Plan;
use App\Models\LicenseKey;
use App\Models\User;
use App\Support\LicenseCode;
use Illuminate\Support\Facades\DB;

/**
 * Mints one or more Pro activation serials. The plaintext is only ever
 * present in the return value of this call — nowhere else — so the caller
 * must show it to the Superadmin immediately; it can't be recovered later.
 */
class GenerateLicenseKeysAction
{
    /**
     * @return list<array{key: LicenseKey, plain: string}>
     */
    public function execute(Plan $plan, int $quantity, User $generatedBy, ?string $notes = null, ?LicenseKey $replaces = null): array
    {
        return DB::transaction(function () use ($plan, $quantity, $generatedBy, $notes, $replaces) {
            $results = [];

            for ($i = 0; $i < $quantity; $i++) {
                $code = LicenseCode::generate();

                $key = LicenseKey::query()->create([
                    'code_hash' => $code['hash'],
                    'code_last4' => $code['last4'],
                    'plan' => $plan,
                    'status' => LicenseKeyStatus::Available,
                    'generated_by' => $generatedBy->id,
                    'replaces_license_key_id' => $replaces?->id,
                    'notes' => $notes,
                ]);

                $results[] = ['key' => $key, 'plain' => $code['plain']];
            }

            return $results;
        });
    }
}

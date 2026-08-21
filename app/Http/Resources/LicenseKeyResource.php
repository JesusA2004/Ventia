<?php

namespace App\Http\Resources;

use App\Models\LicenseKey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LicenseKey
 *
 * Never exposes the plaintext code or code_hash — only the masked form. See
 * LicenseKeyController::store() for the one place the plaintext is returned.
 */
class LicenseKeyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'masked' => $this->masked(),
            'plan' => $this->plan->value,
            'plan_label' => $this->plan->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'company' => $this->whenLoaded('company', fn () => $this->company !== null
                ? ['id' => $this->company->id, 'name' => $this->company->name]
                : null),
            'generated_by' => $this->whenLoaded('generatedByUser', fn () => $this->generatedByUser?->only(['id', 'name'])),
            'redeemed_at' => $this->redeemed_at?->toIso8601String(),
            'redeemed_by' => $this->whenLoaded('redeemedByUser', fn () => $this->redeemedByUser?->only(['id', 'name'])),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'revoked_by' => $this->whenLoaded('revokedByUser', fn () => $this->revokedByUser?->only(['id', 'name'])),
            'replaces_license_key_id' => $this->replaces_license_key_id,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

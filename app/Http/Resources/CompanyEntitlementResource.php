<?php

namespace App\Http\Resources;

use App\Enums\Plan;
use App\Models\CompanyEntitlement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CompanyEntitlement */
class CompanyEntitlementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'plan' => $this->plan->value,
            'plan_label' => $this->plan->label(),
            'is_pro' => $this->plan === Plan::Pro,
            'activated_at' => $this->activated_at?->toIso8601String(),
            'activated_by' => $this->whenLoaded('activatedByUser', fn () => $this->activatedByUser?->only(['id', 'name'])),
            'license_masked' => $this->whenLoaded('licenseKey', fn () => $this->licenseKey?->masked()),
        ];
    }
}

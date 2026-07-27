<?php

namespace App\Http\Resources;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Company */
class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'tax_id' => $this->tax_id,
            'logo_path' => $this->logo_path,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'status' => $this->status->value,
        ];
    }
}

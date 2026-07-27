<?php

namespace App\Http\Resources;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Unit */
class UnitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'is_global' => $this->company_id === null,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'decimal_places' => $this->decimal_places,
            'allows_fraction' => $this->allows_fraction,
            'conversion_factor' => $this->conversion_factor,
            'base_unit_id' => $this->base_unit_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
        ];
    }
}

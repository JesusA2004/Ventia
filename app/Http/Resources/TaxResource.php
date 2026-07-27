<?php

namespace App\Http\Resources;

use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tax */
class TaxResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'rate' => $this->rate,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'included_in_price' => $this->included_in_price,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
        ];
    }
}

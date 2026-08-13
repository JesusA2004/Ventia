<?php

namespace App\Http\Resources;

use App\Models\PriceList;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PriceList */
class PriceListResource extends JsonResource
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
            'currency' => $this->currency,
            'priority' => $this->priority,
            'is_default' => $this->is_default,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
        ];
    }
}

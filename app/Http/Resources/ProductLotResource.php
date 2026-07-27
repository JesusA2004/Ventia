<?php

namespace App\Http\Resources;

use App\Models\ProductLot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductLot */
class ProductLotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product->name),
            'product_variant_id' => $this->product_variant_id,
            'lot_number' => $this->lot_number,
            'manufacture_date' => $this->manufacture_date?->toDateString(),
            'expiration_date' => $this->expiration_date?->toDateString(),
            'received_at' => $this->received_at?->toDateString(),
            'cost' => $this->cost,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_expired' => $this->isExpired(),
        ];
    }
}

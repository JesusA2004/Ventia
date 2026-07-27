<?php

namespace App\Http\Resources;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductVariant */
class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'internal_code' => $this->internal_code,
            'cost' => $this->cost,
            'sale_price' => $this->sale_price,
            'minimum_price' => $this->minimum_price,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'attribute_value_ids' => $this->whenLoaded('attributeValues', fn () => $this->attributeValues->pluck('id')),
            'attribute_values' => ProductAttributeValueResource::collection($this->whenLoaded('attributeValues')),
            'label' => $this->whenLoaded('attributeValues', fn () => $this->attributeValues->pluck('value')->implode(' / ')),
        ];
    }
}

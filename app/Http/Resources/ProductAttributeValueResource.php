<?php

namespace App\Http\Resources;

use App\Models\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductAttributeValue */
class ProductAttributeValueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attribute_id' => $this->product_attribute_id,
            'attribute_name' => $this->whenLoaded('attribute', fn () => $this->attribute->name),
            'value' => $this->value,
        ];
    }
}

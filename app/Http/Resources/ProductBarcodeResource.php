<?php

namespace App\Http\Resources;

use App\Models\ProductBarcode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductBarcode */
class ProductBarcodeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'barcode' => $this->barcode,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'is_primary' => $this->is_primary,
            'quantity_multiplier' => $this->quantity_multiplier,
        ];
    }
}

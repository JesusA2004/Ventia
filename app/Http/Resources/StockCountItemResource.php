<?php

namespace App\Http\Resources;

use App\Models\StockCountItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockCountItem */
class StockCountItemResource extends JsonResource
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
            'product_sku' => $this->whenLoaded('product', fn () => $this->product->sku),
            'product_variant_id' => $this->product_variant_id,
            'variant_label' => $this->whenLoaded('variant', fn () => $this->variant?->sku),
            'product_lot_id' => $this->product_lot_id,
            'expected_quantity' => $this->expected_quantity,
            'counted_quantity' => $this->counted_quantity,
            'difference' => $this->difference,
        ];
    }
}

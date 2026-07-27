<?php

namespace App\Http\Resources;

use App\Models\StockTransferItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockTransferItem */
class StockTransferItemResource extends JsonResource
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
            'quantity_requested' => $this->quantity_requested,
            'quantity_shipped' => $this->quantity_shipped,
            'quantity_received' => $this->quantity_received,
            'unit_cost' => $this->unit_cost,
        ];
    }
}

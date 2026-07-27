<?php

namespace App\Http\Resources;

use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SaleItem */
class SaleItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'product_lot_id' => $this->product_lot_id,
            'product_name' => $this->product_name_snapshot,
            'sku' => $this->sku_snapshot,
            'barcode' => $this->barcode_snapshot,
            'lot_number' => $this->whenLoaded('lot', fn () => $this->lot?->lot_number),
            'quantity' => (string) $this->quantity,
            'unit_price' => (string) $this->unit_price,
            'original_unit_price' => (string) $this->original_unit_price,
            'unit_cost' => $this->when($request->user()?->can('products.view-costs') ?? false, (string) $this->unit_cost),
            'discount_amount' => (string) $this->discount_amount,
            'tax_rate' => (string) $this->tax_rate,
            'tax_amount' => (string) $this->tax_amount,
            'subtotal' => (string) $this->subtotal,
            'total' => (string) $this->total,
            'notes' => $this->notes,
        ];
    }
}

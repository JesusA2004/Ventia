<?php

namespace App\Http\Resources;

use App\Models\InventoryBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InventoryBalance */
class InventoryBalanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'warehouse_name' => $this->whenLoaded('warehouse', fn () => $this->warehouse->name),
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product->name),
            'product_sku' => $this->whenLoaded('product', fn () => $this->product->sku),
            'product_variant_id' => $this->product_variant_id,
            'variant_label' => $this->whenLoaded('variant', fn () => $this->variant?->sku),
            'product_lot_id' => $this->product_lot_id,
            'lot_number' => $this->whenLoaded('lot', fn () => $this->lot?->lot_number),
            'expiration_date' => $this->whenLoaded('lot', fn () => $this->lot?->expiration_date?->toDateString()),
            'quantity' => $this->quantity,
            'unit_symbol' => $this->whenLoaded('product', fn () => $this->product->unit->symbol),
            'unit_allows_fraction' => $this->whenLoaded('product', fn () => $this->product->unit->allows_fraction),
            'average_cost' => $this->when(
                $request->user()?->can('inventory.view-costs') ?? false,
                $this->average_cost,
            ),
            'minimum_stock' => $this->whenLoaded('product', fn () => $this->product->minimum_stock),
            'is_low_stock' => $this->whenLoaded('product', fn () => bccomp($this->quantity, $this->product->minimum_stock, 4) <= 0),
        ];
    }
}

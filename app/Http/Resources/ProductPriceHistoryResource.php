<?php

namespace App\Http\Resources;

use App\Models\ProductPriceHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductPriceHistory */
class ProductPriceHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->whenLoaded('branch', fn () => $this->branch?->name),
            'price_list_id' => $this->price_list_id,
            'price_list_name' => $this->whenLoaded('priceList', fn () => $this->priceList?->name),
            'old_price' => $this->old_price,
            'new_price' => $this->new_price,
            'old_cost' => $this->old_cost,
            'new_cost' => $this->new_cost,
            'percentage_change' => $this->percentage_change,
            'reason' => $this->reason,
            'changed_by_name' => $this->whenLoaded('changedByUser', fn () => $this->changedByUser?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

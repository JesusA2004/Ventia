<?php

namespace App\Http\Resources;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Coupon */
class CouponResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'value' => (string) $this->value,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_active_now' => $this->isActiveNow(),
            'min_purchase_amount' => $this->min_purchase_amount !== null ? (string) $this->min_purchase_amount : null,
            'usage_limit' => $this->usage_limit,
            'usage_limit_per_customer' => $this->usage_limit_per_customer,
            'combinable' => $this->combinable,
            'notes' => $this->notes,
            'times_used' => $this->whenCounted('completedSales'),
            'branch_ids' => $this->whenLoaded('branches', fn () => $this->branches->pluck('id')),
            'branches' => $this->whenLoaded('branches', fn () => $this->branches->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])),
            'product_ids' => $this->whenLoaded('products', fn () => $this->products->pluck('id')),
            'products' => $this->whenLoaded('products', fn () => $this->products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku])),
            'category_ids' => $this->whenLoaded('categories', fn () => $this->categories->pluck('id')),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

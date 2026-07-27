<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canViewCosts = $request->user()?->can('viewCosts', $this->resource) ?? false;

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category?->name),
            'brand_id' => $this->brand_id,
            'brand_name' => $this->whenLoaded('brand', fn () => $this->brand?->name),
            'unit_id' => $this->unit_id,
            'unit_name' => $this->whenLoaded('unit', fn () => $this->unit?->name),
            'unit_symbol' => $this->whenLoaded('unit', fn () => $this->unit?->symbol),
            'tax_id' => $this->tax_id,
            'tax_name' => $this->whenLoaded('tax', fn () => $this->tax?->name),
            'tax_rate' => $this->whenLoaded('tax', fn () => $this->tax?->rate),
            'name' => $this->name,
            'short_name' => $this->short_name,
            'slug' => $this->slug,
            'description' => $this->description,
            'sku' => $this->sku,
            'internal_code' => $this->internal_code,
            'product_type' => $this->product_type->value,
            'product_type_label' => $this->product_type->label(),
            'tracking_type' => $this->tracking_type->value,
            'tracking_type_label' => $this->tracking_type->label(),
            'image_path' => $this->image_path,
            'cost' => $this->when($canViewCosts, fn () => $this->cost),
            'sale_price' => $this->sale_price,
            'minimum_price' => $this->minimum_price,
            'wholesale_price' => $this->wholesale_price,
            'minimum_stock' => $this->minimum_stock,
            'maximum_stock' => $this->maximum_stock,
            'allows_negative_stock' => $this->allows_negative_stock,
            'visible_in_pos' => $this->visible_in_pos,
            'is_favorite' => $this->is_favorite,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'barcodes' => ProductBarcodeResource::collection($this->whenLoaded('barcodes')),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

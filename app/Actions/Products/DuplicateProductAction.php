<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Administrative "clone this product" tool. Copies classification, pricing
 * and variant structure with fresh SKUs; deliberately does not copy
 * barcodes (must stay physically unique), inventory, lots, or price history.
 */
class DuplicateProductAction
{
    public function execute(Product $source): Product
    {
        return DB::transaction(function () use ($source) {
            $newSku = $this->uniqueSku($source);

            $copy = Product::query()->create([
                ...$source->only([
                    'company_id', 'category_id', 'brand_id', 'unit_id', 'tax_id',
                    'short_name', 'description', 'product_type', 'tracking_type',
                    'cost', 'sale_price', 'minimum_price', 'wholesale_price',
                    'minimum_stock', 'maximum_stock', 'allows_negative_stock',
                    'visible_in_pos',
                ]),
                'name' => $source->name.' (copia)',
                'slug' => Str::slug($source->name).'-copia-'.Str::lower(Str::random(6)),
                'sku' => $newSku,
                'internal_code' => null,
                'is_favorite' => false,
                'status' => 'inactive',
            ]);

            foreach ($source->variants()->with('attributeValues')->get() as $variant) {
                $newVariant = ProductVariant::query()->create([
                    'company_id' => $copy->company_id,
                    'product_id' => $copy->id,
                    'sku' => $this->uniqueVariantSku($variant),
                    'cost' => $variant->cost,
                    'sale_price' => $variant->sale_price,
                    'minimum_price' => $variant->minimum_price,
                    'status' => $variant->status,
                ]);

                $newVariant->attributeValues()->sync($variant->attributeValues->pluck('id'));
            }

            return $copy->fresh(['variants.attributeValues.attribute']);
        });
    }

    private function uniqueSku(Product $source): string
    {
        do {
            $candidate = $source->sku.'-COPY-'.Str::upper(Str::random(4));
        } while (Product::withoutGlobalScopes()->where('company_id', $source->company_id)->where('sku', $candidate)->exists());

        return $candidate;
    }

    private function uniqueVariantSku(ProductVariant $source): string
    {
        do {
            $candidate = $source->sku.'-COPY-'.Str::upper(Str::random(4));
        } while (ProductVariant::withoutGlobalScopes()->where('company_id', $source->company_id)->where('sku', $candidate)->exists());

        return $candidate;
    }
}

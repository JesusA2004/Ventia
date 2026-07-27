<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateProductAction
{
    /**
     * @param  array<string, mixed>  $data  Validated StoreProductRequest data.
     *                                      'variants' and 'barcodes' are optional nested arrays.
     */
    public function execute(array $data, int $companyId): Product
    {
        return DB::transaction(function () use ($data, $companyId) {
            $variants = $data['variants'] ?? [];
            $barcodes = $data['barcodes'] ?? [];

            $product = Product::query()->create([
                ...collect($data)->except(['variants', 'barcodes'])->all(),
                'company_id' => $companyId,
                'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
            ]);

            foreach ($variants as $variantData) {
                $this->createVariant($product, $companyId, $variantData);
            }

            foreach ($barcodes as $index => $barcodeData) {
                ProductBarcode::query()->create([
                    'company_id' => $companyId,
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                    'barcode' => $barcodeData['barcode'],
                    'type' => $barcodeData['type'],
                    'is_primary' => $index === 0,
                    'quantity_multiplier' => $barcodeData['quantity_multiplier'] ?? 1,
                ]);
            }

            return $product->fresh(['variants.attributeValues.attribute', 'barcodes']);
        });
    }

    /**
     * @param  array<string, mixed>  $variantData
     */
    private function createVariant(Product $product, int $companyId, array $variantData): ProductVariant
    {
        $variant = ProductVariant::query()->create([
            'company_id' => $companyId,
            'product_id' => $product->id,
            'sku' => $variantData['sku'],
            'internal_code' => $variantData['internal_code'] ?? null,
            'cost' => $variantData['cost'] ?? $product->cost,
            'sale_price' => $variantData['sale_price'] ?? $product->sale_price,
            'minimum_price' => $variantData['minimum_price'] ?? null,
            'status' => $variantData['status'] ?? 'active',
        ]);

        $variant->attributeValues()->sync($variantData['attribute_value_ids'] ?? []);

        return $variant;
    }
}

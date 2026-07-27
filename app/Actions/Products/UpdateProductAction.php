<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

/**
 * Updates a product's general/classification/inventory-policy fields only.
 * Price and cost are deliberately excluded here (even if present in $data)
 * because every change to them must go through ChangeProductPriceAction /
 * ChangeProductCostAction so a history row is always written.
 */
class UpdateProductAction
{
    private const PRICE_FIELDS = ['cost', 'sale_price', 'minimum_price', 'wholesale_price'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $variants = $data['variants'] ?? null;
            $barcodes = $data['barcodes'] ?? null;

            $product->update(
                collect($data)
                    ->except(['variants', 'barcodes', ...self::PRICE_FIELDS])
                    ->all(),
            );

            if ($variants !== null) {
                $this->syncVariants($product, $variants);
            }

            if ($barcodes !== null) {
                $this->syncBarcodes($product, $barcodes);
            }

            return $product->fresh(['variants.attributeValues.attribute', 'barcodes']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $keptIds = [];

        foreach ($variants as $variantData) {
            if (! empty($variantData['id'])) {
                $variant = $product->variants()->whereKey($variantData['id'])->firstOrFail();
                $variant->update(
                    collect($variantData)->except(['id', 'attribute_value_ids', ...self::PRICE_FIELDS])->all(),
                );
                $variant->attributeValues()->sync($variantData['attribute_value_ids'] ?? []);
                $keptIds[] = $variant->id;

                continue;
            }

            $variant = ProductVariant::query()->create([
                'company_id' => $product->company_id,
                'product_id' => $product->id,
                'sku' => $variantData['sku'],
                'internal_code' => $variantData['internal_code'] ?? null,
                'cost' => $variantData['cost'] ?? $product->cost,
                'sale_price' => $variantData['sale_price'] ?? $product->sale_price,
                'minimum_price' => $variantData['minimum_price'] ?? null,
                'status' => $variantData['status'] ?? 'active',
            ]);
            $variant->attributeValues()->sync($variantData['attribute_value_ids'] ?? []);
            $keptIds[] = $variant->id;
        }

        // Variants removed from the form are soft-deactivated rather than
        // deleted: they may already have inventory movements or historic sales.
        $product->variants()->whereNotIn('id', $keptIds)->update(['status' => 'inactive']);
    }

    /**
     * @param  list<array<string, mixed>>  $barcodes
     */
    private function syncBarcodes(Product $product, array $barcodes): void
    {
        $keptIds = [];

        foreach ($barcodes as $index => $barcodeData) {
            $attributes = [
                'company_id' => $product->company_id,
                'product_id' => $product->id,
                'product_variant_id' => $barcodeData['product_variant_id'] ?? null,
                'barcode' => $barcodeData['barcode'],
                'type' => $barcodeData['type'],
                'is_primary' => (bool) ($barcodeData['is_primary'] ?? $index === 0),
                'quantity_multiplier' => $barcodeData['quantity_multiplier'] ?? 1,
            ];

            $barcode = ! empty($barcodeData['id'])
                ? tap($product->barcodes()->whereKey($barcodeData['id'])->firstOrFail())->update($attributes)
                : ProductBarcode::query()->create($attributes);

            $keptIds[] = $barcode->id;
        }

        $product->barcodes()->whereNotIn('id', $keptIds)->delete();
    }
}

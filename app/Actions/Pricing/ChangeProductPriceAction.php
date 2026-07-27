<?php

namespace App\Actions\Pricing;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductPriceHistory;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

/**
 * The only way sale prices may change. Never update products.sale_price or
 * product_variants.sale_price (or a product_prices override) with a plain
 * ->update() — every change must go through here so a history row is
 * always written in the same transaction.
 */
class ChangeProductPriceAction
{
    public function execute(
        Product $product,
        ?ProductVariant $variant,
        string $newPrice,
        string $reason,
        User $user,
        ?int $branchId = null,
        ?int $priceListId = null,
    ): void {
        $newPrice = Decimal::of($newPrice);

        DB::transaction(function () use ($product, $variant, $newPrice, $reason, $user, $branchId, $priceListId) {
            if ($priceListId === null) {
                $this->changeBasePrice($product, $variant, $newPrice, $reason, $user);

                return;
            }

            $this->changeOverridePrice($product, $variant, $newPrice, $reason, $user, $branchId, $priceListId);
        });
    }

    /**
     * @param  numeric-string  $newPrice
     */
    private function changeBasePrice(Product $product, ?ProductVariant $variant, string $newPrice, string $reason, User $user): void
    {
        $target = $variant ?? $product;
        $oldPrice = $target->sale_price;

        $target->update(['sale_price' => $newPrice]);

        ProductPriceHistory::query()->create([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'branch_id' => null,
            'price_list_id' => null,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'percentage_change' => $this->percentageChange($oldPrice, $newPrice),
            'reason' => $reason,
            'changed_by' => $user->id,
        ]);
    }

    /**
     * @param  numeric-string  $newPrice
     */
    private function changeOverridePrice(
        Product $product,
        ?ProductVariant $variant,
        string $newPrice,
        string $reason,
        User $user,
        ?int $branchId,
        int $priceListId,
    ): void {
        $override = ProductPrice::query()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant?->id)
            ->where('price_list_id', $priceListId)
            ->where('branch_id', $branchId)
            ->whereNull('min_quantity')
            ->first();

        $oldPrice = $override?->price;

        if ($override) {
            $override->update(['price' => $newPrice]);
        } else {
            ProductPrice::query()->create([
                'company_id' => $product->company_id,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'price_list_id' => $priceListId,
                'branch_id' => $branchId,
                'price' => $newPrice,
                'status' => 'active',
            ]);
        }

        ProductPriceHistory::query()->create([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'branch_id' => $branchId,
            'price_list_id' => $priceListId,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'percentage_change' => $this->percentageChange($oldPrice, $newPrice),
            'reason' => $reason,
            'changed_by' => $user->id,
        ]);
    }

    /**
     * @param  numeric-string|null  $old
     * @param  numeric-string  $new
     * @return numeric-string|null
     */
    private function percentageChange(?string $old, string $new): ?string
    {
        if ($old === null || bccomp($old, '0', 4) === 0) {
            return null;
        }

        return bcmul(bcdiv(bcsub($new, $old, 6), $old, 6), '100', 4);
    }
}

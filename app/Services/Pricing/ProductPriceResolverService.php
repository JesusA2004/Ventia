<?php

namespace App\Services\Pricing;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Carbon;

/**
 * Single source of truth for "what does this product cost right now".
 *
 * products.sale_price (or product_variants.sale_price for a variant) is the
 * canonical base price. product_prices only holds overrides — a specific
 * price list, branch, quantity tier, or time window — and is optional: a
 * product with no override rows simply sells at its base price. Frontend
 * clients never get to decide the price; every quote goes through here.
 */
class ProductPriceResolverService
{
    public function resolve(
        Product $product,
        ?ProductVariant $variant = null,
        ?int $branchId = null,
        ?int $priceListId = null,
        string $quantity = '1',
        ?Carbon $when = null,
    ): string {
        $basePrice = ($variant ?? $product)->sale_price;

        if ($priceListId === null) {
            return (string) $basePrice;
        }

        $when ??= Carbon::now();

        $override = $product->prices()
            ->where('price_list_id', $priceListId)
            ->where('status', 'active')
            ->where(fn ($query) => $query->where('product_variant_id', $variant?->id)->when(
                $variant === null,
                fn ($q) => $q->orWhereNull('product_variant_id'),
            ))
            ->where(fn ($query) => $query->whereNull('branch_id')->when(
                $branchId !== null,
                fn ($q) => $q->orWhere('branch_id', $branchId),
            ))
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $when))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $when))
            ->where(fn ($query) => $query->whereNull('min_quantity')->orWhere('min_quantity', '<=', $quantity))
            ->orderByRaw('branch_id is null')
            ->orderByRaw('min_quantity is null')
            ->orderByDesc('min_quantity')
            ->first();

        return $override !== null ? (string) $override->price : (string) $basePrice;
    }
}

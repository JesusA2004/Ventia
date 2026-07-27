<?php

namespace App\Actions\Pricing;

use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;

/**
 * The only way product/variant cost may change. Never update the cost
 * column with a plain ->update() — every change must go through here so a
 * history row is always written in the same transaction.
 */
class ChangeProductCostAction
{
    public function execute(Product $product, ?ProductVariant $variant, string $newCost, string $reason, User $user): void
    {
        $newCost = Decimal::of($newCost);

        DB::transaction(function () use ($product, $variant, $newCost, $reason, $user) {
            $target = $variant ?? $product;
            $oldCost = $target->cost;

            $target->update(['cost' => $newCost]);

            ProductPriceHistory::query()->create([
                'company_id' => $product->company_id,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'old_cost' => $oldCost,
                'new_cost' => $newCost,
                'percentage_change' => $this->percentageChange($oldCost, $newCost),
                'reason' => $reason,
                'changed_by' => $user->id,
            ]);
        });
    }

    /**
     * @param  numeric-string  $old
     * @param  numeric-string  $new
     * @return numeric-string|null
     */
    private function percentageChange(string $old, string $new): ?string
    {
        if (bccomp($old, '0', 4) === 0) {
            return null;
        }

        return bcmul(bcdiv(bcsub($new, $old, 6), $old, 6), '100', 4);
    }
}

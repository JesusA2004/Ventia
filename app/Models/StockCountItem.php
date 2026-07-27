<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $stock_count_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int|null $product_lot_id
 * @property numeric-string $expected_quantity
 * @property numeric-string|null $counted_quantity
 * @property numeric-string|null $difference
 * @property numeric-string $unit_cost
 */
#[Fillable(['stock_count_id', 'product_id', 'product_variant_id', 'product_lot_id', 'expected_quantity', 'counted_quantity', 'difference', 'unit_cost'])]
class StockCountItem extends Model
{
    protected function casts(): array
    {
        return [
            'expected_quantity' => 'decimal:4',
            'counted_quantity' => 'decimal:4',
            'difference' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<StockCount, $this> */
    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** @return BelongsTo<ProductLot, $this> */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(ProductLot::class, 'product_lot_id');
    }
}

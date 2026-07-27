<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Query-side projection only. Never written to directly — go through
 * App\Services\Inventory\InventoryBalanceService, which holds a row lock
 * for the duration of the write.
 *
 * @property int $id
 * @property int $company_id
 * @property int $branch_id
 * @property int $warehouse_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int|null $product_lot_id
 * @property numeric-string $quantity
 * @property numeric-string $average_cost
 */
class InventoryBalance extends Model
{
    use BelongsToBranch, BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'average_cost' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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

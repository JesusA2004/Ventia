<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Append-only ledger: rows are created by ChangeProductPriceAction /
 * ChangeProductCostAction and never updated or deleted.
 *
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int|null $branch_id
 * @property int|null $price_list_id
 * @property numeric-string|null $old_price
 * @property numeric-string|null $new_price
 * @property numeric-string|null $old_cost
 * @property numeric-string|null $new_cost
 * @property numeric-string|null $percentage_change
 * @property string|null $reason
 * @property int|null $changed_by
 * @property Carbon|null $created_at
 */
#[Fillable([
    'company_id', 'product_id', 'product_variant_id', 'branch_id', 'price_list_id',
    'old_price', 'new_price', 'old_cost', 'new_cost', 'percentage_change', 'reason', 'changed_by',
])]
class ProductPriceHistory extends Model
{
    use BelongsToCompany;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'old_price' => 'decimal:4',
            'new_price' => 'decimal:4',
            'old_cost' => 'decimal:4',
            'new_cost' => 'decimal:4',
            'percentage_change' => 'decimal:4',
            'created_at' => 'datetime',
        ];
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

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<PriceList, $this> */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    /** @return BelongsTo<User, $this> */
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

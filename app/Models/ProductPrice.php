<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int $price_list_id
 * @property int|null $branch_id
 * @property numeric-string $price
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property numeric-string|null $min_quantity
 * @property Status $status
 */
#[Fillable(['company_id', 'product_id', 'product_variant_id', 'price_list_id', 'branch_id', 'price', 'starts_at', 'ends_at', 'min_quantity', 'status'])]
class ProductPrice extends Model
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
            'min_quantity' => 'decimal:4',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => Status::class,
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

    /** @return BelongsTo<PriceList, $this> */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

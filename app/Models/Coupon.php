<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\SaleStatus;
use App\Enums\Status;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A code a cashier/customer enters at checkout to claim a benefit (see
 * Promotion for the automatic, code-less counterpart). Same "empty pivot =
 * unrestricted" applicability convention as Promotion.
 *
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property DiscountType $type
 * @property string $value
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Status $status
 * @property string|null $min_purchase_amount
 * @property int|null $usage_limit
 * @property int|null $usage_limit_per_customer
 * @property bool $combinable
 * @property string|null $notes
 */
#[Fillable([
    'company_id', 'code', 'name', 'description', 'type', 'value', 'starts_at', 'ends_at', 'status',
    'min_purchase_amount', 'usage_limit', 'usage_limit_per_customer', 'combinable', 'notes',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'status' => Status::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'value' => 'decimal:4',
            'min_purchase_amount' => 'decimal:4',
            'combinable' => 'boolean',
        ];
    }

    /** Codes are matched/stored case- and whitespace-insensitively (VERANO10 === verano10). */
    public static function normalizeCode(string $code): string
    {
        return mb_strtoupper(trim($code));
    }

    /** @return Attribute<string, string> */
    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => self::normalizeCode($value),
        );
    }

    /** @return BelongsToMany<Branch, $this> */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'coupon_branch');
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_product');
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'coupon_category');
    }

    /**
     * Only the sales that actually count toward usage_limit — see PromotionEligibilityService.
     *
     * @return HasMany<Sale, $this>
     */
    public function completedSales(): HasMany
    {
        return $this->hasMany(Sale::class)->where('status', SaleStatus::Completed);
    }

    public function isActiveNow(): bool
    {
        if ($this->status !== Status::Active) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at !== null && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function isScopedToProductsOrCategories(): bool
    {
        $hasProducts = $this->relationLoaded('products') ? $this->products->isNotEmpty() : $this->products()->exists();
        $hasCategories = $this->relationLoaded('categories') ? $this->categories->isNotEmpty() : $this->categories()->exists();

        return $hasProducts || $hasCategories;
    }

    public function isScopedToBranches(): bool
    {
        return $this->relationLoaded('branches') ? $this->branches->isNotEmpty() : $this->branches()->exists();
    }
}

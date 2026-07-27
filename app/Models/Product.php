<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TrackingType;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $category_id
 * @property int|null $brand_id
 * @property int $unit_id
 * @property int|null $tax_id
 * @property string $name
 * @property string|null $short_name
 * @property string $slug
 * @property string|null $description
 * @property string $sku
 * @property string|null $internal_code
 * @property ProductType $product_type
 * @property TrackingType $tracking_type
 * @property string|null $image_path
 * @property numeric-string $cost
 * @property numeric-string $sale_price
 * @property numeric-string|null $minimum_price
 * @property numeric-string|null $wholesale_price
 * @property numeric-string $minimum_stock
 * @property numeric-string|null $maximum_stock
 * @property bool $allows_negative_stock
 * @property bool $visible_in_pos
 * @property bool $is_favorite
 * @property Status $status
 */
#[Fillable([
    'company_id', 'category_id', 'brand_id', 'unit_id', 'tax_id',
    'name', 'short_name', 'slug', 'description', 'sku', 'internal_code',
    'product_type', 'tracking_type', 'image_path',
    'cost', 'sale_price', 'minimum_price', 'wholesale_price',
    'minimum_stock', 'maximum_stock', 'allows_negative_stock',
    'visible_in_pos', 'is_favorite', 'status',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'tracking_type' => TrackingType::class,
            'cost' => 'decimal:4',
            'sale_price' => 'decimal:4',
            'minimum_price' => 'decimal:4',
            'wholesale_price' => 'decimal:4',
            'minimum_stock' => 'decimal:4',
            'maximum_stock' => 'decimal:4',
            'allows_negative_stock' => 'boolean',
            'visible_in_pos' => 'boolean',
            'is_favorite' => 'boolean',
            'status' => Status::class,
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return BelongsTo<Unit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** @return BelongsTo<Tax, $this> */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** @return HasMany<ProductBarcode, $this> */
    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    /** @return HasMany<ProductPrice, $this> */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /** @return HasMany<ProductPriceHistory, $this> */
    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    /** @return HasMany<ProductLot, $this> */
    public function lots(): HasMany
    {
        return $this->hasMany(ProductLot::class);
    }

    /** @return HasMany<InventoryMovement, $this> */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}

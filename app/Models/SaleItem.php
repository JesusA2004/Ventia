<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $sale_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int|null $product_lot_id
 * @property string $product_name_snapshot
 * @property string $sku_snapshot
 * @property string|null $barcode_snapshot
 * @property numeric-string $quantity
 * @property numeric-string $unit_price
 * @property numeric-string $original_unit_price
 * @property numeric-string $unit_cost
 * @property numeric-string $discount_amount
 * @property numeric-string $tax_rate
 * @property numeric-string $tax_amount
 * @property numeric-string $subtotal
 * @property numeric-string $total
 * @property string|null $notes
 */
#[Fillable([
    'sale_id', 'product_id', 'product_variant_id', 'product_lot_id',
    'product_name_snapshot', 'sku_snapshot', 'barcode_snapshot',
    'quantity', 'unit_price', 'original_unit_price', 'unit_cost',
    'discount_amount', 'tax_rate', 'tax_amount', 'subtotal', 'total', 'notes',
])]
class SaleItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'original_unit_price' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'total' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
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

    /** @return HasMany<SaleReturnItem, $this> */
    public function saleReturnItems(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}

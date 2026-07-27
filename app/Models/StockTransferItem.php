<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $stock_transfer_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int|null $product_lot_id
 * @property numeric-string $quantity_requested
 * @property numeric-string|null $quantity_shipped
 * @property numeric-string|null $quantity_received
 * @property numeric-string $unit_cost
 */
#[Fillable(['stock_transfer_id', 'product_id', 'product_variant_id', 'product_lot_id', 'quantity_requested', 'quantity_shipped', 'quantity_received', 'unit_cost'])]
class StockTransferItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:4',
            'quantity_shipped' => 'decimal:4',
            'quantity_received' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<StockTransfer, $this> */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
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

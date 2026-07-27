<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sale_return_id
 * @property int $sale_item_id
 * @property numeric-string $quantity
 * @property numeric-string $unit_price
 * @property numeric-string $total_refunded
 * @property bool $restocked
 */
#[Fillable(['sale_return_id', 'sale_item_id', 'quantity', 'unit_price', 'total_refunded', 'restocked'])]
class SaleReturnItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'total_refunded' => 'decimal:4',
            'restocked' => 'boolean',
        ];
    }

    /** @return BelongsTo<SaleReturn, $this> */
    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    /** @return BelongsTo<SaleItem, $this> */
    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}

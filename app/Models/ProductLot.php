<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ProductLotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int|null $supplier_id
 * @property string $lot_number
 * @property Carbon|null $manufacture_date
 * @property Carbon|null $expiration_date
 * @property Carbon|null $received_at
 * @property numeric-string $cost
 * @property Status $status
 */
#[Fillable(['company_id', 'product_id', 'product_variant_id', 'supplier_id', 'lot_number', 'manufacture_date', 'expiration_date', 'received_at', 'cost', 'status'])]
class ProductLot extends Model
{
    /** @use HasFactory<ProductLotFactory> */
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'manufacture_date' => 'date',
            'expiration_date' => 'date',
            'received_at' => 'date',
            'cost' => 'decimal:4',
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

    public function isExpired(): bool
    {
        return $this->expiration_date !== null && $this->expiration_date->isPast();
    }
}

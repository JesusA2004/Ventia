<?php

namespace App\Models;

use App\Enums\BarcodeType;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property string $barcode
 * @property BarcodeType $type
 * @property bool $is_primary
 * @property numeric-string $quantity_multiplier
 */
#[Fillable(['company_id', 'product_id', 'product_variant_id', 'barcode', 'type', 'is_primary', 'quantity_multiplier'])]
class ProductBarcode extends Model
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return [
            'type' => BarcodeType::class,
            'is_primary' => 'boolean',
            'quantity_multiplier' => 'decimal:4',
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
}

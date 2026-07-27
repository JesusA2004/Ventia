<?php

namespace App\Models;

use App\Enums\InventoryMovementDirection;
use App\Enums\InventoryMovementType;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Append-only: rows are created by RecordInventoryMovementAction and never
 * updated or deleted. A correction is always a new, opposite movement.
 *
 * @property int $id
 * @property int $company_id
 * @property int $branch_id
 * @property int $warehouse_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int|null $product_lot_id
 * @property InventoryMovementType $movement_type
 * @property InventoryMovementDirection $direction
 * @property numeric-string $quantity
 * @property numeric-string $unit_cost
 * @property numeric-string $previous_stock
 * @property numeric-string $resulting_stock
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $reason
 * @property string|null $notes
 * @property int|null $performed_by
 * @property Carbon $occurred_at
 * @property Carbon $created_at
 */
#[Fillable([
    'company_id', 'branch_id', 'warehouse_id', 'product_id', 'product_variant_id', 'product_lot_id',
    'movement_type', 'direction', 'quantity', 'unit_cost', 'previous_stock', 'resulting_stock',
    'reference_type', 'reference_id', 'reason', 'notes', 'performed_by', 'occurred_at',
])]
class InventoryMovement extends Model
{
    use BelongsToBranch, BelongsToCompany;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'movement_type' => InventoryMovementType::class,
            'direction' => InventoryMovementDirection::class,
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'previous_stock' => 'decimal:4',
            'resulting_stock' => 'decimal:4',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
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

    /** @return BelongsTo<User, $this> */
    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /** @return MorphTo<Model, $this> */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}

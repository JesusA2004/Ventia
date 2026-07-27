<?php

namespace App\Models;

use App\Enums\StockTransferStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $origin_branch_id
 * @property int $origin_warehouse_id
 * @property int $destination_branch_id
 * @property int $destination_warehouse_id
 * @property string $folio
 * @property StockTransferStatus $status
 * @property int $requested_by
 * @property int|null $approved_by
 * @property int|null $shipped_by
 * @property int|null $received_by
 * @property string|null $notes
 * @property Carbon|null $requested_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $shipped_at
 * @property Carbon|null $received_at
 * @property Carbon|null $cancelled_at
 */
#[Fillable([
    'company_id', 'origin_branch_id', 'origin_warehouse_id', 'destination_branch_id', 'destination_warehouse_id',
    'folio', 'status', 'requested_by', 'approved_by', 'shipped_by', 'received_by', 'notes',
    'requested_at', 'approved_at', 'shipped_at', 'received_at', 'cancelled_at',
])]
class StockTransfer extends Model
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return [
            'status' => StockTransferStatus::class,
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function originBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'origin_branch_id');
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function originWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'origin_warehouse_id');
    }

    /** @return BelongsTo<Branch, $this> */
    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function shippedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    /** @return BelongsTo<User, $this> */
    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /** @return HasMany<StockTransferItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}

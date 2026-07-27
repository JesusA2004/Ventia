<?php

namespace App\Models;

use App\Enums\StockCountStatus;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $branch_id
 * @property int $warehouse_id
 * @property string $folio
 * @property StockCountStatus $status
 * @property int $started_by
 * @property int|null $completed_by
 * @property int|null $applied_by
 * @property string|null $notes
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $applied_at
 * @property Carbon|null $cancelled_at
 */
#[Fillable([
    'company_id', 'branch_id', 'warehouse_id', 'folio', 'status',
    'started_by', 'completed_by', 'applied_by', 'notes',
    'started_at', 'completed_at', 'applied_at', 'cancelled_at',
])]
class StockCount extends Model
{
    use BelongsToBranch, BelongsToCompany;

    protected function casts(): array
    {
        return [
            'status' => StockCountStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'applied_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<User, $this> */
    public function startedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /** @return BelongsTo<User, $this> */
    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function appliedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    /** @return HasMany<StockCountItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class);
    }
}

<?php

namespace App\Models;

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
 * @property int $sale_id
 * @property int|null $cash_session_id
 * @property int $user_id
 * @property string $folio
 * @property string $status
 * @property numeric-string $total_refunded
 * @property string $reason
 * @property Carbon $processed_at
 */
#[Fillable(['company_id', 'branch_id', 'sale_id', 'cash_session_id', 'user_id', 'folio', 'status', 'total_refunded', 'reason', 'processed_at'])]
class SaleReturn extends Model
{
    use BelongsToBranch, BelongsToCompany;

    protected function casts(): array
    {
        return [
            'total_refunded' => 'decimal:4',
            'processed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<SaleReturnItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}

<?php

namespace App\Models;

use App\Enums\CashSessionStatus;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CashSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $branch_id
 * @property int $register_id
 * @property int $user_id
 * @property CashSessionStatus $status
 * @property Carbon $opened_at
 * @property numeric-string $opening_amount
 * @property numeric-string|null $expected_cash
 * @property numeric-string|null $counted_cash
 * @property numeric-string|null $difference
 * @property Carbon|null $closed_at
 * @property int|null $closed_by
 * @property string|null $opening_notes
 * @property string|null $closing_notes
 */
#[Fillable([
    'company_id', 'branch_id', 'register_id', 'user_id', 'status', 'opened_at',
    'opening_amount', 'expected_cash', 'counted_cash', 'difference', 'closed_at',
    'closed_by', 'opening_notes', 'closing_notes',
])]
class CashSession extends Model
{
    /** @use HasFactory<CashSessionFactory> */
    use BelongsToBranch, BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'status' => CashSessionStatus::class,
            'opened_at' => 'datetime',
            'opening_amount' => 'decimal:4',
            'expected_cash' => 'decimal:4',
            'counted_cash' => 'decimal:4',
            'difference' => 'decimal:4',
            'closed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CashRegister, $this> */
    public function register(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'register_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /** @return HasMany<CashMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    /** @return HasMany<Sale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}

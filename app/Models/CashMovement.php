<?php

namespace App\Models;

use App\Enums\CashMovementType;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Append-only ledger: rows are created by RegisterCashMovementAction and
 * never updated or deleted. Reversals are a new movement with the opposite
 * sign, not an edit.
 *
 * @property int $id
 * @property int $company_id
 * @property int $branch_id
 * @property int $register_id
 * @property int $cash_session_id
 * @property int $user_id
 * @property CashMovementType $type
 * @property numeric-string $amount
 * @property string $reason
 * @property string|null $notes
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property Carbon $occurred_at
 * @property Carbon $created_at
 */
#[Fillable([
    'company_id', 'branch_id', 'register_id', 'cash_session_id', 'user_id', 'type',
    'amount', 'reason', 'notes', 'reference_type', 'reference_id', 'occurred_at',
])]
class CashMovement extends Model
{
    use BelongsToBranch, BelongsToCompany;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'type' => CashMovementType::class,
            'amount' => 'decimal:4',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CashRegister, $this> */
    public function register(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'register_id');
    }

    /** @return BelongsTo<CashSession, $this> */
    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, $this> */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}

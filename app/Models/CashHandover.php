<?php

namespace App\Models;

use App\Enums\CashHandoverStatus;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A supervised cash-close request: a cashier counts denominations, a
 * supervisor authenticates and approves/rejects/requests a recount. Only on
 * approval does CloseCashSessionAction actually run — this model never
 * writes to cash_movements itself. Only created when the company has the
 * cash_handover_required setting enabled (see SettingsService).
 *
 * @property int $id
 * @property int $company_id
 * @property int $branch_id
 * @property int $cash_session_id
 * @property int $cashier_id
 * @property int|null $approved_by
 * @property CashHandoverStatus $status
 * @property numeric-string $expected_cash
 * @property numeric-string $counted_cash
 * @property numeric-string $difference
 * @property array<int, array{denomination: float, quantity: int}> $denominations
 * @property string|null $cashier_notes
 * @property string|null $supervisor_notes
 * @property Carbon $requested_at
 * @property Carbon|null $resolved_at
 */
#[Fillable([
    'company_id', 'branch_id', 'cash_session_id', 'cashier_id', 'approved_by', 'status',
    'expected_cash', 'counted_cash', 'difference', 'denominations',
    'cashier_notes', 'supervisor_notes', 'requested_at', 'resolved_at',
])]
class CashHandover extends Model
{
    use BelongsToBranch, BelongsToCompany;

    protected function casts(): array
    {
        return [
            'status' => CashHandoverStatus::class,
            'expected_cash' => 'decimal:4',
            'counted_cash' => 'decimal:4',
            'difference' => 'decimal:4',
            'denominations' => 'array',
            'requested_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CashSession, $this> */
    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    /** @return BelongsTo<User, $this> */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

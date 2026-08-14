<?php

namespace App\Models;

use App\Enums\CashSessionStatus;
use App\Enums\Status;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CashRegisterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $company_id
 * @property int $branch_id
 * @property int|null $warehouse_id
 * @property string $name
 * @property string $code
 * @property string|null $printer_name
 * @property bool $has_cash_drawer
 * @property int|null $assigned_user_id
 * @property Status $status
 */
#[Fillable(['company_id', 'branch_id', 'warehouse_id', 'name', 'code', 'printer_name', 'has_cash_drawer', 'assigned_user_id', 'status'])]
class CashRegister extends Model
{
    /** @use HasFactory<CashRegisterFactory> */
    use BelongsToBranch, BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'registers';

    protected function casts(): array
    {
        return [
            'has_cash_drawer' => 'boolean',
            'status' => Status::class,
        ];
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * The currently open session for this register, if any. A register can
     * only have one open session at a time (enforced in OpenCashSessionAction).
     *
     * @return HasOne<CashSession, $this>
     */
    public function openSession(): HasOne
    {
        return $this->hasOne(CashSession::class, 'register_id')->where('status', CashSessionStatus::Open);
    }
}

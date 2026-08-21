<?php

namespace App\Models;

use App\Enums\SaleStatus;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\SaleFactory;
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
 * @property int $warehouse_id
 * @property int $register_id
 * @property int|null $cash_session_id
 * @property int $customer_id
 * @property int $cashier_id
 * @property int|null $seller_id
 * @property string $folio
 * @property SaleStatus $status
 * @property numeric-string $subtotal
 * @property numeric-string $discount_total
 * @property int|null $promotion_id
 * @property string|null $promotion_name_snapshot
 * @property numeric-string $promotion_discount_amount
 * @property int|null $coupon_id
 * @property string|null $coupon_code_snapshot
 * @property numeric-string $coupon_discount_amount
 * @property numeric-string $tax_total
 * @property numeric-string $total
 * @property numeric-string $cost_total
 * @property numeric-string $profit_total
 * @property numeric-string $amount_received
 * @property numeric-string $change_amount
 * @property string|null $checkout_uuid
 * @property string|null $notes
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property string|null $cancellation_reason
 * @property Carbon $created_at
 */
#[Fillable([
    'company_id', 'branch_id', 'warehouse_id', 'register_id', 'cash_session_id',
    'customer_id', 'cashier_id', 'seller_id', 'folio', 'status',
    'subtotal', 'discount_total',
    'promotion_id', 'promotion_name_snapshot', 'promotion_discount_amount',
    'coupon_id', 'coupon_code_snapshot', 'coupon_discount_amount',
    'tax_total', 'total', 'cost_total', 'profit_total',
    'amount_received', 'change_amount', 'checkout_uuid', 'notes', 'completed_at',
    'cancelled_at', 'cancelled_by', 'cancellation_reason',
])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use BelongsToBranch, BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SaleStatus::class,
            'subtotal' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'promotion_discount_amount' => 'decimal:4',
            'coupon_discount_amount' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'total' => 'decimal:4',
            'cost_total' => 'decimal:4',
            'profit_total' => 'decimal:4',
            'amount_received' => 'decimal:4',
            'change_amount' => 'decimal:4',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Promotion, $this> */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** @return BelongsTo<User, $this> */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /** @return BelongsTo<User, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /** @return BelongsTo<User, $this> */
    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** @return HasMany<SalePayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    /** @return HasMany<SaleReturn, $this> */
    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }
}

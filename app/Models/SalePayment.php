<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sale_id
 * @property int $payment_method_id
 * @property numeric-string $amount
 * @property string|null $reference
 * @property string|null $authorization_number
 * @property string|null $card_last_four
 * @property string|null $bank
 * @property string|null $terminal
 * @property string $status
 * @property Carbon $paid_at
 */
#[Fillable([
    'sale_id', 'payment_method_id', 'amount', 'reference', 'authorization_number',
    'card_last_four', 'bank', 'terminal', 'status', 'paid_at',
])]
class SalePayment extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<PaymentMethod, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}

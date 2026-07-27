<?php

namespace App\Models;

use App\Enums\PaymentMethodType;
use App\Enums\Status;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $code
 * @property PaymentMethodType $type
 * @property bool $requires_reference
 * @property bool $opens_cash_drawer
 * @property bool $affects_cash
 * @property bool $allows_change
 * @property int $sort_order
 * @property Status $status
 */
#[Fillable([
    'company_id', 'name', 'code', 'type', 'requires_reference', 'opens_cash_drawer',
    'affects_cash', 'allows_change', 'sort_order', 'status',
])]
class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'requires_reference' => 'boolean',
            'opens_cash_drawer' => 'boolean',
            'affects_cash' => 'boolean',
            'allows_change' => 'boolean',
            'status' => Status::class,
        ];
    }
}

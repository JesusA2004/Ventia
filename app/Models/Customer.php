<?php

namespace App\Models;

use App\Enums\CustomerType;
use App\Enums\Status;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $branch_id
 * @property CustomerType $customer_type
 * @property string $name
 * @property string|null $legal_name
 * @property string|null $tax_id
 * @property string|null $phone
 * @property string $phone_country_code
 * @property string|null $email
 * @property string|null $address
 * @property int|null $price_list_id
 * @property numeric-string $credit_limit
 * @property numeric-string $current_balance
 * @property string|null $notes
 * @property Status $status
 */
#[Fillable([
    'company_id', 'branch_id', 'customer_type', 'name', 'legal_name', 'tax_id',
    'phone', 'phone_country_code', 'email', 'address', 'price_list_id', 'credit_limit', 'current_balance',
    'notes', 'status',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'credit_limit' => 'decimal:4',
            'current_balance' => 'decimal:4',
            'status' => Status::class,
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<PriceList, $this> */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    /** @return HasMany<Sale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}

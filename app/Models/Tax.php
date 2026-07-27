<?php

namespace App\Models;

use App\Enums\Status;
use App\Enums\TaxType;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\TaxFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $code
 * @property numeric-string $rate
 * @property TaxType $type
 * @property bool $included_in_price
 * @property Status $status
 */
#[Fillable(['company_id', 'name', 'code', 'rate', 'type', 'included_in_price', 'status'])]
class Tax extends Model
{
    /** @use HasFactory<TaxFactory> */
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'type' => TaxType::class,
            'included_in_price' => 'boolean',
            'status' => Status::class,
        ];
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

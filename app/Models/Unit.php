<?php

namespace App\Models;

use App\Enums\Status;
use App\Enums\UnitType;
use App\Models\Concerns\BelongsToCompanyOrGlobal;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string $symbol
 * @property UnitType $type
 * @property int $decimal_places
 * @property bool $allows_fraction
 * @property numeric-string|null $conversion_factor
 * @property int|null $base_unit_id
 * @property Status $status
 */
#[Fillable(['company_id', 'name', 'symbol', 'type', 'decimal_places', 'allows_fraction', 'conversion_factor', 'base_unit_id', 'status'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use BelongsToCompanyOrGlobal, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => UnitType::class,
            'allows_fraction' => 'boolean',
            'conversion_factor' => 'decimal:6',
            'status' => Status::class,
        ];
    }

    /** @return BelongsTo<Unit, $this> */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(self::class, 'base_unit_id');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

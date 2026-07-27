<?php

namespace App\Models;

use App\Enums\Status;
use App\Enums\WarehouseType;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\WarehouseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $company_id
 * @property int $branch_id
 * @property string $name
 * @property string $code
 * @property WarehouseType $type
 * @property bool $allows_sale
 * @property Status $status
 */
#[Fillable(['company_id', 'branch_id', 'name', 'code', 'type', 'allows_sale', 'status'])]
class Warehouse extends Model
{
    /** @use HasFactory<WarehouseFactory> */
    use BelongsToBranch, BelongsToCompany, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => WarehouseType::class,
            'allows_sale' => 'boolean',
            'status' => Status::class,
        ];
    }

    /** @return HasMany<CashRegister, $this> */
    public function registers(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }
}

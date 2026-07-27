<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property Status $status
 */
#[Fillable(['company_id', 'name', 'status'])]
class ProductAttribute extends Model
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }

    /** @return HasMany<ProductAttributeValue, $this> */
    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $branch_id
 * @property string $key
 * @property mixed $value
 */
#[Fillable(['company_id', 'branch_id', 'key', 'value'])]
class Setting extends Model
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}

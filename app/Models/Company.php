<?php

namespace App\Models;

use App\Enums\Status;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string|null $legal_name
 * @property string|null $tax_id
 * @property string|null $logo_path
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string $currency
 * @property string $timezone
 * @property string|null $primary_color
 * @property string|null $secondary_color
 * @property Status $status
 */
#[Fillable(['name', 'legal_name', 'tax_id', 'logo_path', 'address', 'phone', 'email', 'currency', 'timezone', 'primary_color', 'secondary_color', 'status'])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }

    /** @return HasMany<Branch, $this> */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Setting, $this> */
    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }
}

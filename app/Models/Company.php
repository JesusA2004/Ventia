<?php

namespace App\Models;

use App\Enums\CustomerType;
use App\Enums\Status;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    protected static function booted(): void
    {
        // Every company must have exactly one "Público general" customer,
        // used as the POS default when no specific customer is selected.
        static::created(function (Company $company) {
            Customer::query()->create([
                'company_id' => $company->id,
                'customer_type' => CustomerType::GeneralPublic,
                'name' => 'Público general',
                'status' => Status::Active,
            ]);
        });
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

    /** @return HasOne<CompanyEntitlement, $this> */
    public function entitlement(): HasOne
    {
        return $this->hasOne(CompanyEntitlement::class);
    }
}

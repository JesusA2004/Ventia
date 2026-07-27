<?php

namespace Database\Factories;

use App\Enums\SaleStatus;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'register_id' => CashRegister::factory(),
            'company_id' => fn (array $attributes) => CashRegister::whereKey($attributes['register_id'])->firstOrFail()->company_id,
            'branch_id' => fn (array $attributes) => CashRegister::whereKey($attributes['register_id'])->firstOrFail()->branch_id,
            'warehouse_id' => Warehouse::factory(),
            'customer_id' => Customer::factory(),
            'cashier_id' => User::factory(),
            'folio' => strtoupper(fake()->unique()->bothify('V-#####')),
            'status' => SaleStatus::Completed,
            'subtotal' => 100,
            'total' => 100,
            'checkout_uuid' => (string) Str::uuid(),
            'completed_at' => now(),
        ];
    }
}

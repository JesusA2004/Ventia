<?php

namespace Database\Factories;

use App\Enums\CashSessionStatus;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashSession>
 */
class CashSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'register_id' => CashRegister::factory(),
            'company_id' => fn (array $attributes) => CashRegister::whereKey($attributes['register_id'])->firstOrFail()->company_id,
            'branch_id' => fn (array $attributes) => CashRegister::whereKey($attributes['register_id'])->firstOrFail()->branch_id,
            'user_id' => User::factory(),
            'status' => CashSessionStatus::Open,
            'opened_at' => now(),
            'opening_amount' => 500,
        ];
    }
}

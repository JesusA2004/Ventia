<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Branch;
use App\Models\CashRegister;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashRegister>
 */
class CashRegisterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'company_id' => fn (array $attributes) => Branch::whereKey($attributes['branch_id'])->firstOrFail()->company_id,
            'name' => 'Caja 1',
            'code' => strtoupper(fake()->unique()->bothify('CAJA-###')),
            'has_cash_drawer' => true,
            'status' => Status::Active,
        ];
    }
}

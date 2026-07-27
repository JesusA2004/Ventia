<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Company;
use App\Models\PriceList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceList>
 */
class PriceListFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'General',
            'code' => strtoupper(fake()->unique()->bothify('LP-###')),
            'currency' => 'MXN',
            'priority' => 0,
            'status' => Status::Active,
        ];
    }
}

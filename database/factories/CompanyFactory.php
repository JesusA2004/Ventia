<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'legal_name' => fake()->company().' S.A. de C.V.',
            'tax_id' => strtoupper(fake()->bothify('???######???')),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'currency' => 'MXN',
            'timezone' => 'America/Mexico_City',
            'status' => Status::Active,
        ];
    }
}

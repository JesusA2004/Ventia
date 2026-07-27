<?php

namespace Database\Factories;

use App\Enums\CustomerType;
use App\Enums\Status;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_type' => CustomerType::Individual,
            'name' => fake()->name(),
            'phone' => fake()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'credit_limit' => 0,
            'current_balance' => 0,
            'status' => Status::Active,
        ];
    }
}

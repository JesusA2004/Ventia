<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\Status;
use App\Models\Company;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(2, true),
            'type' => DiscountType::Percentage,
            'value' => '10.0000',
            'status' => Status::Active,
            'priority' => 0,
            'combinable' => false,
        ];
    }
}

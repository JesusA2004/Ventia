<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\Status;
use App\Models\Company;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => strtoupper(fake()->unique()->bothify('CUPON-####')),
            'name' => fake()->words(2, true),
            'type' => DiscountType::Percentage,
            'value' => '10.0000',
            'status' => Status::Active,
            'combinable' => false,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\PaymentMethodType;
use App\Enums\Status;
use App\Models\Company;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Efectivo',
            'code' => strtoupper(fake()->unique()->bothify('PM-####')),
            'type' => PaymentMethodType::Cash,
            'requires_reference' => false,
            'opens_cash_drawer' => true,
            'affects_cash' => true,
            'allows_change' => true,
            'sort_order' => 0,
            'status' => Status::Active,
        ];
    }
}

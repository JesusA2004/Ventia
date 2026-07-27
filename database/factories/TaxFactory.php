<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Enums\TaxType;
use App\Models\Company;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tax>
 */
class TaxFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'IVA 16%',
            'code' => 'IVA16',
            'rate' => 16,
            'type' => TaxType::Percentage,
            'included_in_price' => true,
            'status' => Status::Active,
        ];
    }
}

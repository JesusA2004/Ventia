<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Enums\UnitType;
use App\Models\Company;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Pieza',
            'symbol' => 'PZA',
            'type' => UnitType::Piece,
            'decimal_places' => 0,
            'allows_fraction' => false,
            'status' => Status::Active,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Enums\WarehouseType;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'company_id' => fn (array $attributes) => Branch::whereKey($attributes['branch_id'])->firstOrFail()->company_id,
            'name' => 'Almacén principal',
            'code' => strtoupper(fake()->unique()->bothify('ALM-###')),
            'type' => WarehouseType::General,
            'allows_sale' => true,
            'status' => Status::Active,
        ];
    }
}

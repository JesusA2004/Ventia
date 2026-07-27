<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Product;
use App\Models\ProductLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductLot>
 */
class ProductLotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'company_id' => fn (array $attributes) => Product::whereKey($attributes['product_id'])->firstOrFail()->company_id,
            'lot_number' => strtoupper(fake()->unique()->bothify('LOTE-#####')),
            'received_at' => now(),
            'cost' => fake()->randomFloat(2, 10, 100),
            'status' => Status::Active,
        ];
    }
}

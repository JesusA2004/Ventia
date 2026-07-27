<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'company_id' => fn (array $attributes) => Product::whereKey($attributes['product_id'])->firstOrFail()->company_id,
            'sku' => strtoupper(fake()->unique()->bothify('VAR-#####')),
            'cost' => fake()->randomFloat(2, 10, 100),
            'sale_price' => fake()->randomFloat(2, 15, 150),
            'status' => Status::Active,
        ];
    }
}

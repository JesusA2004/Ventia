<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TrackingType;
use App\Models\Company;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word().' '.fake()->word().' '.fake()->word());

        return [
            'company_id' => Company::factory(),
            'unit_id' => fn (array $attributes) => Unit::factory()->create(['company_id' => $attributes['company_id']])->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-#####')),
            'product_type' => ProductType::Physical,
            'tracking_type' => TrackingType::Simple,
            'cost' => fake()->randomFloat(2, 10, 100),
            'sale_price' => fake()->randomFloat(2, 15, 150),
            'minimum_stock' => 5,
            'allows_negative_stock' => false,
            'visible_in_pos' => true,
            'is_favorite' => false,
            'status' => Status::Active,
        ];
    }
}

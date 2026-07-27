<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word().' '.fake()->word());

        return [
            'company_id' => Company::factory(),
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => 0,
            'status' => Status::Active,
        ];
    }
}

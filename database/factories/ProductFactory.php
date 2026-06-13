<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_category_id' => ProductCategory::factory(),
            'name' => fake()->words(3, true),
            'code' => fake()->unique()->bothify('PRD-####'),
            'description' => fake()->optional()->sentence(),
            'base_rental_price' => fake()->numberBetween(50000, 500000),
            'is_active' => true,
        ];
    }
}

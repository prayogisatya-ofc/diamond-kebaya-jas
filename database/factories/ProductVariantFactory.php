<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'name' => fake()->randomElement(['Size S', 'Size M', 'Size L', 'Hitam', 'Merah']),
            'size' => fake()->optional()->randomElement(['S', 'M', 'L', 'XL']),
            'color' => fake()->optional()->safeColorName(),
            'stock_quantity' => fake()->numberBetween(1, 10),
            'rental_price' => fake()->optional()->numberBetween(50000, 500000),
            'is_active' => true,
        ];
    }
}

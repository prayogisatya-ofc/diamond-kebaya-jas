<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\RentalPackage;
use App\Models\RentalPackageItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalPackageItem>
 */
class RentalPackageItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_package_id' => RentalPackage::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'quantity' => fake()->numberBetween(1, 3),
            'default_item_price' => fake()->optional()->numberBetween(50000, 500000),
            'is_optional' => false,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

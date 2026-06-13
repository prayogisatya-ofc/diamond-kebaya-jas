<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\RentalItem;
use App\Models\Rental;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalItem>
 */
class RentalItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $unitPrice = fake()->numberBetween(100000, 500000);
        $discount = fake()->numberBetween(0, 50000);

        return [
            'rental_id' => Rental::factory(),
            'rental_package_id' => null,
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'item_name_snapshot' => fake()->words(2, true),
            'variant_name_snapshot' => null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $discount,
            'final_price' => max(0, ($quantity * $unitPrice) - $discount),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

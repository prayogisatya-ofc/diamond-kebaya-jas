<?php

namespace Database\Factories;

use App\Models\RentalPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalPackage>
 */
class RentalPackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'package_price' => fake()->numberBetween(150000, 1000000),
            'is_active' => true,
        ];
    }
}

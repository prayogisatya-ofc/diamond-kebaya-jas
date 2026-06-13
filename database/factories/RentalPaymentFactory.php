<?php

namespace Database\Factories;

use App\Models\Rental;
use App\Models\RentalPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalPayment>
 */
class RentalPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_id' => Rental::factory(),
            'payment_type' => 'dp',
            'payment_method' => fake()->randomElement(['cash', 'transfer', 'qris']),
            'amount' => fake()->numberBetween(50000, 500000),
            'paid_at' => now(),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}

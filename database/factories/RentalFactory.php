<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rental>
 */
class RentalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(150000, 1000000);
        $paid = fake()->numberBetween(0, $subtotal);

        return [
            'invoice_number' => 'INV-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'customer_id' => Customer::factory(),
            'status' => 'booked',
            'payment_status' => $paid === 0 ? 'unpaid' : ($paid < $subtotal ? 'dp' : 'paid'),
            'guarantee_type' => fake()->randomElement(['ktp', 'sim']),
            'pickup_at' => now()->addDay(),
            'return_due_at' => now()->addDays(3),
            'subtotal_amount' => $subtotal,
            'discount_amount' => 0,
            'custom_adjustment_amount' => 0,
            'penalty_days' => 0,
            'penalty_amount' => 0,
            'total_amount' => $subtotal,
            'paid_amount' => $paid,
            'remaining_amount' => $subtotal - $paid,
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}

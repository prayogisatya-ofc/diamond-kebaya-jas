<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => fake()->sentence(3),
            'amount' => fake()->numberBetween(5000, 500000),
            'category' => fake()->randomElement(['operasional', 'maintenance', 'laundry', 'supplies', 'other']),
            'expense_date' => fake()->date(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

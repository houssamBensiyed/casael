<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Colocation;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, 5, 500),
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'category_id' => Category::factory(),
            'payer_id' => User::factory(),
            'colocation_id' => Colocation::factory(),
        ];
    }
}

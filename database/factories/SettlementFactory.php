<?php

namespace Database\Factories;

use App\Models\Colocation;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Settlement>
 */
class SettlementFactory extends Factory
{
    protected $model = Settlement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_user_id' => User::factory(),
            'to_user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 5, 200),
            'colocation_id' => Colocation::factory(),
            'is_paid' => false,
        ];
    }

    /**
     * Indicate that the settlement is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => true,
        ]);
    }
}

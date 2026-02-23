<?php

namespace Database\Factories;

use App\Models\Colocation;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'colocation_id' => Colocation::factory(),
            'email' => fake()->unique()->safeEmail(),
            'token' => Str::random(32),
            'status' => 'pending',
        ];
    }

    /**
     * Indicate that the invitation is accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
        ]);
    }

    /**
     * Indicate that the invitation is refused.
     */
    public function refused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refused',
        ]);
    }
}

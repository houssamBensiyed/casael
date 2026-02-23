<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Colocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Loyer', 'Courses', 'Électricité', 'Internet', 'Eau',
                'Ménage', 'Transport', 'Loisirs', 'Autre',
            ]),
            'colocation_id' => Colocation::factory(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubCategory>
 */
class SubCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'emoji' => fake()->randomElement(['💻', '📞', '🏃', '🍽️', '👩‍💻', '🏳️‍🌈', '👋🏽']),
            'name' => fake()->unique()->words(2, true),
        ];
    }
}

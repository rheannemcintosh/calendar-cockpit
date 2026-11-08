<?php

namespace Database\Factories;

use App\Enums\FlagIcon;
use App\Models\Flag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Flag>
 */
class FlagFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(2, true),
            'icon' => fake()->randomElement(FlagIcon::cases()),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = Carbon::instance(fake()->dateTimeBetween('-1 month', '+2 months'))->startOfHour();

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(fake()->randomElement([30, 60, 90, 120])),
        ];
    }

    /**
     * Indicate that the event runs between the given times.
     */
    public function between(string $startsAt, string $endsAt): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => Carbon::parse($startsAt),
            'ends_at' => Carbon::parse($endsAt),
        ]);
    }
}

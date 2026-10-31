<?php

namespace Database\Factories;

use App\Enums\TimeType;
use App\Models\CalendarItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<CalendarItem>
 */
class CalendarItemFactory extends Factory
{
    /**
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
            'time_type' => TimeType::Exact,
        ];
    }

    public function between(string $startsAt, string $endsAt): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => Carbon::parse($startsAt),
            'ends_at' => Carbon::parse($endsAt),
        ]);
    }

    public function window(): static
    {
        return $this->state(fn (array $attributes) => ['time_type' => TimeType::Window]);
    }

    public function block(): static
    {
        return $this->state(fn (array $attributes) => ['time_type' => TimeType::Block]);
    }

    public function approximate(): static
    {
        return $this->state(fn (array $attributes) => ['time_type' => TimeType::Approximate]);
    }

    public function withLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_name' => fake()->company(),
            'location_address' => fake()->address(),
            'location_latitude' => fake()->latitude(),
            'location_longitude' => fake()->longitude(),
        ]);
    }
}

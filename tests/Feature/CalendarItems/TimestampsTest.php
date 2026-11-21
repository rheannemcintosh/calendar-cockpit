<?php

use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// Created at 09:05 UTC on Saturday 21 November 2026, which is 09:05 in London
// and 22:05 in Auckland.
beforeEach(fn () => $this->travelTo('2026-11-21 09:05:00'));

/**
 * A calendar item on Saturday 21 November 2026, made now, for the user.
 */
function timestampedCalendarItem(User $user): CalendarItem
{
    return CalendarItem::factory()->for($user)->create([
        'starts_at' => '2026-11-21 10:00:00',
        'ends_at' => '2026-11-21 11:00:00',
    ]);
}

test('a calendar item\'s details show only when it was created when it has not been changed', function (string $route, string $path) {
    $user = User::factory()->create(['timezone' => 'Europe/London']);
    timestampedCalendarItem($user);

    $this->actingAs($user)
        ->get(route($route, ['date' => '2026-11-21']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where("{$path}.timestamps", ['created' => '21 Nov 2026, 09:05', 'updated' => null]));
})->with([
    'list' => ['calendar-items.index', 'years.0.weeks.0.days.0.items.0'],
    'day' => ['calendar-items.day', 'items.0'],
    'week' => ['calendar-items.week', 'days.6.items.0'],
    'month' => ['calendar-items.month', 'weeks.2.6.items.0'],
]);

test('a calendar item\'s details show when it was created and last updated once it has been changed', function () {
    $user = User::factory()->create(['timezone' => 'Europe/London']);
    $calendarItem = timestampedCalendarItem($user);

    $this->travelTo('2026-11-21 14:30:00');
    $calendarItem->update(['title' => 'Something else']);

    $this->actingAs($user)
        ->get(route('calendar-items.day', ['date' => '2026-11-21']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.timestamps', ['created' => '21 Nov 2026, 09:05', 'updated' => '21 Nov 2026, 14:30']));
});

test('a calendar item\'s dates are in the user\'s timezone', function () {
    $user = User::factory()->create(['timezone' => 'Pacific/Auckland']);
    timestampedCalendarItem($user);

    $this->actingAs($user)
        ->get(route('calendar-items.day', ['date' => '2026-11-21']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.timestamps.created', '21 Nov 2026, 22:05'));
});

test('a category\'s edit page shows when it was created, and when it was last updated once changed', function () {
    $user = User::factory()->create(['timezone' => 'Pacific/Auckland']);
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('categories.edit', $category))
        ->assertInertia(fn (Assert $page) => $page
            ->where('timestamps', ['created' => '21 Nov 2026, 22:05', 'updated' => null]));

    $this->travelTo('2026-11-22 08:00:00');
    $category->update(['name' => 'Something else']);

    $this->actingAs($user)
        ->get(route('categories.edit', $category))
        ->assertInertia(fn (Assert $page) => $page
            ->where('timestamps', ['created' => '21 Nov 2026, 22:05', 'updated' => '22 Nov 2026, 21:00']));
});

test('a sub-category\'s edit page shows when it was created, and when it was last updated once changed', function () {
    $user = User::factory()->create(['timezone' => 'Pacific/Auckland']);
    $subCategory = SubCategory::factory()->for(Category::factory()->for($user))->create();

    $this->actingAs($user)
        ->get(route('sub-categories.edit', $subCategory))
        ->assertInertia(fn (Assert $page) => $page
            ->where('timestamps', ['created' => '21 Nov 2026, 22:05', 'updated' => null]));

    $this->travelTo('2026-11-22 08:00:00');
    $subCategory->update(['name' => 'Something else']);

    $this->actingAs($user)
        ->get(route('sub-categories.edit', $subCategory))
        ->assertInertia(fn (Assert $page) => $page
            ->where('timestamps', ['created' => '21 Nov 2026, 22:05', 'updated' => '22 Nov 2026, 21:00']));
});

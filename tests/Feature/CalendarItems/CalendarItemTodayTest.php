<?php

use App\Models\CalendarItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

// At 23:30 UTC on Tuesday 20 October 2026 it is already Wednesday 21 October
// in Auckland (UTC+13), and at 03:00 UTC on Wednesday 21 October it is still
// Tuesday 20 October in Los Angeles (UTC-7).

test('today is the user\'s date when it is already tomorrow in UTC', function () {
    $this->travelTo('2026-10-21 03:00:00');

    $this->actingAs(User::factory()->create(['timezone' => 'America/Los_Angeles']))
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page->where('date', '2026-10-20'));
});

test('today is the user\'s date when it is still yesterday in UTC', function () {
    $this->travelTo('2026-10-20 23:30:00');

    $this->actingAs(User::factory()->create(['timezone' => 'Pacific/Auckland']))
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('date', '2026-10-21')
            ->where('label', 'Wednesday 21 October 2026'));
});

test('today is the app\'s date for a user with no timezone yet', function () {
    $this->travelTo('2026-10-20 23:30:00');

    $this->actingAs(User::factory()->create(['timezone' => null]))
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page->where('date', '2026-10-20'));
});

test('the day view shows calendar items on the user\'s today', function () {
    $this->travelTo('2026-10-20 23:30:00');
    $user = User::factory()->create(['timezone' => 'Pacific/Auckland']);
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->create(['title' => 'Yesterday there']);
    CalendarItem::factory()->for($user)->between('2026-10-21 09:00', '2026-10-21 10:00')->create(['title' => 'Today there']);

    $this->actingAs($user)
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('items', 1)
            ->where('items.0.title', 'Today there')
            ->where('items.0.startMinute', 9 * 60));
});

test('the list starts at the beginning of the user\'s today', function () {
    $this->travelTo('2026-10-20 23:30:00');
    $user = User::factory()->create(['timezone' => 'Pacific/Auckland']);
    CalendarItem::factory()->for($user)->between('2026-10-20 22:00', '2026-10-20 23:00')->create(['title' => 'Yesterday there']);
    CalendarItem::factory()->for($user)->between('2026-10-21 00:00', '2026-10-21 01:00')->create(['title' => 'Midnight there']);

    $this->actingAs($user)
        ->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('years.0.weeks.0.days', 1)
            ->where('years.0.weeks.0.days.0.date', '2026-10-21')
            ->where('years.0.weeks.0.days.0.items.0.title', 'Midnight there')
            ->where('previousFrom', '2026-10-20'));
});

test('the week view opens on the user\'s week', function () {
    // Saturday 24 October in UTC, and Sunday 25 October, a new week, in Auckland.
    $this->travelTo('2026-10-24 23:30:00');

    $this->actingAs(User::factory()->create(['timezone' => 'Pacific/Auckland']))
        ->get(route('calendar-items.week'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('date', '2026-10-25')
            ->where('days.0.date', '2026-10-25'));

    $this->actingAs(User::factory()->create(['timezone' => 'UTC']))
        ->get(route('calendar-items.week'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('date', '2026-10-24')
            ->where('days.0.date', '2026-10-18'));
});

test('the month view opens on the user\'s month', function () {
    // Saturday 31 October in UTC, and Sunday 1 November in Auckland.
    $this->travelTo('2026-10-31 23:30:00');

    $this->actingAs(User::factory()->create(['timezone' => 'Pacific/Auckland']))
        ->get(route('calendar-items.month'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('date', '2026-11-01')
            ->where('month', '2026-11'));

    $this->actingAs(User::factory()->create(['timezone' => 'Europe/London']))
        ->get(route('calendar-items.month'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('date', '2026-10-31')
            ->where('month', '2026-10'));
});

test('every page is given the timezone today is worked out in', function () {
    $this->actingAs(User::factory()->create(['timezone' => 'Pacific/Auckland']))
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('timezone', 'Pacific/Auckland')
            ->where('auth.user.timezone', 'Pacific/Auckland'));

    $this->actingAs(User::factory()->create(['timezone' => null]))
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('timezone', config('app.timezone'))
            ->where('auth.user.timezone', null));
});

test('a user\'s today is the start of their date, in the timezone calendar items are stored in', function () {
    $this->travelTo('2026-10-20 23:30:00');

    $today = User::factory()->make(['timezone' => 'Pacific/Auckland'])->today();

    expect($today)->toBeInstanceOf(CarbonImmutable::class)
        ->and($today->format('Y-m-d H:i:s'))->toBe('2026-10-21 00:00:00')
        ->and($today->timezoneName)->toBe(config('app.timezone'));

    expect(User::factory()->make(['timezone' => 'Pacific/Auckland'])->now()->format('Y-m-d H:i'))
        ->toBe('2026-10-21 12:30');
});

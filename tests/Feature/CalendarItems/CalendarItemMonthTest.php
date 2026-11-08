<?php

use App\Enums\TimeType;
use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// The month view starts at this month, so pin today to Tuesday 20 October 2026.
// October 2026 runs Thursday 1 to Saturday 31, so its grid is Sunday 27
// September to Saturday 31 October, five weeks.
beforeEach(fn () => $this->travelTo('2026-10-20 12:00:00'));

test('guests are sent to the login page from the month view', function () {
    $this->get(route('calendar-items.month'))->assertRedirect(route('login'));
});

test('the month view shows this month, Sunday-first weeks, when no month is given', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.month'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar-items/Month')
            ->where('date', '2026-10-20')
            ->where('month', '2026-10')
            ->where('label', 'October 2026')
            ->where('previousDate', '2026-09-20')
            ->where('nextDate', '2026-11-20')
            ->has('weeks', 5)
            ->has('weeks.0', 7)
            ->where('weeks.0.0', [
                'date' => '2026-09-27',
                'dayOfMonth' => 27,
                'label' => 'Sunday 27 September 2026',
                'inMonth' => false,
                'items' => [],
            ])
            ->where('weeks.0.4.date', '2026-10-01')
            ->where('weeks.0.4.inMonth', true)
            ->where('weeks.4.6.date', '2026-10-31')
            ->where('weeks.4.6.inMonth', true)
            ->where('timeTypes', TimeType::options()));
});

test('the grid runs from the Sunday on or before the 1st to the Saturday on or after the last day', function (string $month, int $weeks, string $first, string $last) {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.month', ['date' => $month]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('weeks', $weeks)
            ->where('weeks.0.0.date', $first)
            ->missing("weeks.{$weeks}")
            ->where('weeks.'.($weeks - 1).'.6.date', $last));
})->with([
    'starting on a Sunday, four weeks' => ['2026-02', 4, '2026-02-01', '2026-02-28'],
    'with days of the months either side' => ['2026-11', 5, '2026-11-01', '2026-12-05'],
    'six weeks' => ['2026-08', 6, '2026-07-26', '2026-09-05'],
]);

test('a month alone opens on its 1st, and a day is kept so other views open on it', function (string $date, string $kept, string $previous, string $next) {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.month', ['date' => $date]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('date', $kept)
            ->where('month', substr($kept, 0, 7))
            ->where('previousDate', $previous)
            ->where('nextDate', $next));
})->with([
    'a month' => ['2026-12', '2026-12-01', '2026-11-01', '2027-01-01'],
    'a day' => ['2026-12-15', '2026-12-15', '2026-11-15', '2027-01-15'],
    'a day past the end of the next month' => ['2027-01-31', '2027-01-31', '2026-12-31', '2027-02-28'],
]);

test('a month or day that does not exist is not found in the month view', function (string $date) {
    $this->actingAs(User::factory()->create())
        ->get("/calendar-items/month/{$date}")
        ->assertNotFound();
})->with(['month 13' => '2026-13', 'month 0' => '2026-00', '30 February' => '2026-02-30', 'not a date' => 'next-month']);

test('calendar items are on their day with their category colour, sub-category and start time', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Health', 'colour' => '#16A34A']);
    $subCategory = SubCategory::factory()->for($category)->create(['emoji' => '🦷', 'name' => 'Dentist']);
    CalendarItem::factory()->for($user)->for($subCategory)->approximate()
        ->between('2026-10-21 09:07', '2026-10-21 10:43')->create(['title' => 'Check-up']);

    $this->actingAs($user)
        ->get(route('calendar-items.month'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('weeks.3.3.date', '2026-10-21')
            ->has('weeks.3.3.items', 1)
            ->where('weeks.3.3.items.0.title', 'Check-up')
            ->where('weeks.3.3.items.0.time', '09:07–10:43')
            ->where('weeks.3.3.items.0.startTime', '09:07')
            ->where('weeks.3.3.items.0.continued', false)
            ->where('weeks.3.3.items.0.timeType', 'approximate')
            ->where('weeks.3.3.items.0.category', ['name' => 'Health', 'colour' => '#16A34A'])
            ->where('weeks.3.3.items.0.subCategory', ['emoji' => '🦷', 'name' => 'Dentist'])
            ->where('weeks.3.2.items', [])
            ->where('weeks.3.4.items', []));
});

test('each day has its own calendar items in start order, including the days of the months either side', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 14:00', '2026-10-20 15:00')->create(['title' => 'Afternoon']);
    CalendarItem::factory()->for($user)->between('2026-10-20 08:00', '2026-10-20 09:00')->create(['title' => 'Morning']);
    CalendarItem::factory()->for($user)->between('2026-10-20 08:00', '2026-10-20 08:30')->create(['title' => 'Also morning']);
    CalendarItem::factory()->for($user)->between('2026-09-28 10:00', '2026-09-28 11:00')->create(['title' => 'Late September']);
    CalendarItem::factory()->for($user)->between('2026-09-26 10:00', '2026-09-26 11:00')->create(['title' => 'Before the grid']);
    CalendarItem::factory()->for($user)->between('2026-11-01 10:00', '2026-11-01 11:00')->create(['title' => 'After the grid']);
    CalendarItem::factory()->between('2026-10-20 10:00', '2026-10-20 11:00')->create(['title' => 'Someone else\'s']);

    $this->actingAs($user)
        ->get(route('calendar-items.month'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('weeks.3.2.items', 3)
            ->where('weeks.3.2.items.0.title', 'Morning')
            ->where('weeks.3.2.items.1.title', 'Also morning')
            ->where('weeks.3.2.items.2.title', 'Afternoon')
            ->where('weeks.0.1.date', '2026-09-28')
            ->where('weeks.0.1.inMonth', false)
            ->where('weeks.0.1.items.0.title', 'Late September')
            ->where('weeks.0.0.items', [])
            ->where('weeks.4.6.items', []));
});

test('a calendar item that runs past midnight is on each day it covers', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 22:00', '2026-10-22 02:00')->create(['title' => 'Retreat']);
    CalendarItem::factory()->for($user)->between('2026-10-21 09:00', '2026-10-21 10:00')->create(['title' => 'Breakfast']);

    $this->actingAs($user)
        ->get(route('calendar-items.month'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('weeks.3.1.items', [])
            ->where('weeks.3.2.items.0.title', 'Retreat')
            ->where('weeks.3.2.items.0.continued', false)
            ->where('weeks.3.2.items.0.minutes', 120)
            ->where('weeks.3.3.items.0.title', 'Retreat')
            ->where('weeks.3.3.items.0.minutes', 1440)
            ->where('weeks.3.3.items.0.continued', true)
            ->where('weeks.3.3.items.0.startTime', '22:00')
            ->where('weeks.3.3.items.1.title', 'Breakfast')
            ->where('weeks.3.3.items.1.minutes', 60)
            ->where('weeks.3.4.items.0.title', 'Retreat')
            ->where('weeks.3.4.items.0.continued', true)
            ->where('weeks.3.4.items.0.minutes', 120)
            ->where('weeks.3.5.items', []));
});

test('a calendar item that runs into the next month is on both months', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-31 22:00', '2026-11-01 02:00')->create(['title' => 'Halloween party']);

    $this->actingAs($user)
        ->get(route('calendar-items.month'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('weeks.4.6.items.0.title', 'Halloween party')
            ->where('weeks.4.6.items.0.continued', false));

    $this->actingAs($user)
        ->get(route('calendar-items.month', ['date' => '2026-11']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('weeks.0.0.date', '2026-11-01')
            ->where('weeks.0.0.items.0.title', 'Halloween party')
            ->where('weeks.0.0.items.0.continued', true));
});

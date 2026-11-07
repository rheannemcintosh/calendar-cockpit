<?php

use App\Enums\TimeType;
use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// The week view starts at this week, so pin today to Tuesday 20 October 2026,
// in the week of Sunday 18 to Saturday 24 October.
beforeEach(fn () => $this->travelTo('2026-10-20 12:00:00'));

test('guests are sent to the login page from the week view', function () {
    $this->get(route('calendar-items.week'))->assertRedirect(route('login'));
});

test('the week view shows this week, Sunday to Saturday, when no day is given', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.week'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar-items/Week')
            ->where('date', '2026-10-20')
            ->where('label', '18–24 Oct 2026')
            ->where('weekNumber', 43)
            ->where('previousDate', '2026-10-13')
            ->where('nextDate', '2026-10-27')
            ->has('days', 7)
            ->where('days.0', [
                'date' => '2026-10-18',
                'weekday' => 'Sun',
                'dayOfMonth' => 18,
                'label' => 'Sunday 18 October 2026',
                'items' => [],
            ])
            ->where('days.6.date', '2026-10-24')
            ->where('days.6.weekday', 'Sat')
            ->where('timeTypes', TimeType::options()));
});

test('the week view shows the week of the day it is given and keeps that day', function (string $date, string $sunday) {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.week', ['date' => $date]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('date', $date)
            ->where('days.0.date', $sunday));
})->with([
    'a Sunday' => ['2026-11-01', '2026-11-01'],
    'a Wednesday' => ['2026-11-04', '2026-11-01'],
    'a Saturday' => ['2026-11-07', '2026-11-01'],
]);

test('a week across New Year is week 1 of the new year', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.week', ['date' => '2026-12-31']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('label', '27 Dec 2026 – 2 Jan 2027')
            ->where('weekNumber', 1)
            ->where('days.0.date', '2026-12-27')
            ->where('days.6.date', '2027-01-02')
            ->where('previousDate', '2026-12-24')
            ->where('nextDate', '2027-01-07'));
});

test('a day that does not exist is not found in the week view', function (string $date) {
    $this->actingAs(User::factory()->create())
        ->get("/calendar-items/week/{$date}")
        ->assertNotFound();
})->with(['30 February' => '2026-02-30', 'month 13' => '2026-13-01', 'not a date' => 'next-week']);

test('calendar items are placed on their day to the minute', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Health', 'colour' => '#16A34A']);
    $subCategory = SubCategory::factory()->for($category)->create(['emoji' => '🦷', 'name' => 'Dentist']);
    CalendarItem::factory()->for($user)->for($subCategory)->approximate()
        ->between('2026-10-21 09:07', '2026-10-21 10:43')->create(['title' => 'Check-up']);

    $this->actingAs($user)
        ->get(route('calendar-items.week'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.3.date', '2026-10-21')
            ->has('days.3.items', 1)
            ->where('days.3.items.0.title', 'Check-up')
            ->where('days.3.items.0.time', '09:07–10:43')
            ->where('days.3.items.0.timeType', 'approximate')
            ->where('days.3.items.0.startMinute', 9 * 60 + 7)
            ->where('days.3.items.0.endMinute', 10 * 60 + 43)
            ->where('days.3.items.0.category', ['name' => 'Health', 'colour' => '#16A34A'])
            ->where('days.3.items.0.subCategory', ['emoji' => '🦷', 'name' => 'Dentist'])
            ->where('days.3.items.0.column', 0)
            ->where('days.3.items.0.columns', 1)
            ->where('days.2.items', [])
            ->where('days.4.items', []));
});

test('only the week\'s own calendar items are shown, in start order on each day', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-18 14:00', '2026-10-18 15:00')->create(['title' => 'Sunday afternoon']);
    CalendarItem::factory()->for($user)->between('2026-10-18 00:00', '2026-10-18 00:30')->create(['title' => 'Sunday midnight']);
    CalendarItem::factory()->for($user)->between('2026-10-24 23:30', '2026-10-25 00:00')->create(['title' => 'Saturday night']);
    CalendarItem::factory()->for($user)->between('2026-10-17 23:00', '2026-10-18 00:00')->create(['title' => 'Week before']);
    CalendarItem::factory()->for($user)->between('2026-10-25 00:00', '2026-10-25 01:00')->create(['title' => 'Week after']);
    CalendarItem::factory()->between('2026-10-20 10:00', '2026-10-20 11:00')->create(['title' => 'Someone else\'s']);

    $this->actingAs($user)
        ->get(route('calendar-items.week'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('days.0.items', 2)
            ->where('days.0.items.0.title', 'Sunday midnight')
            ->where('days.0.items.0.startMinute', 0)
            ->where('days.0.items.1.title', 'Sunday afternoon')
            ->where('days.2.items', [])
            ->has('days.6.items', 1)
            ->where('days.6.items.0.title', 'Saturday night')
            ->where('days.6.items.0.endMinute', 1440));
});

test('a calendar item that runs past midnight is on each day it covers', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 22:00', '2026-10-22 02:00')->create(['title' => 'Retreat']);

    $this->actingAs($user)
        ->get(route('calendar-items.week'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.1.items', [])
            ->where('days.2.items.0.title', 'Retreat')
            ->where('days.2.items.0.time', '22:00–02:00')
            ->where('days.2.items.0.startMinute', 22 * 60)
            ->where('days.2.items.0.endMinute', 1440)
            ->where('days.3.items.0.title', 'Retreat')
            ->where('days.3.items.0.startMinute', 0)
            ->where('days.3.items.0.endMinute', 1440)
            ->where('days.4.items.0.title', 'Retreat')
            ->where('days.4.items.0.startMinute', 0)
            ->where('days.4.items.0.endMinute', 2 * 60)
            ->where('days.5.items', []));
});

test('a calendar item that runs into the next week is on both weeks', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-24 22:00', '2026-10-25 02:00')->create(['title' => 'Late show']);

    $this->actingAs($user)
        ->get(route('calendar-items.week'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.6.items.0.title', 'Late show')
            ->where('days.6.items.0.endMinute', 1440));

    $this->actingAs($user)
        ->get(route('calendar-items.week', ['date' => '2026-10-25']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.0.items.0.title', 'Late show')
            ->where('days.0.items.0.startMinute', 0)
            ->where('days.0.items.0.endMinute', 2 * 60));
});

test('overlapping calendar items share their day\'s width side by side', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 11:00')->create(['title' => 'Workshop']);
    CalendarItem::factory()->for($user)->between('2026-10-20 09:30', '2026-10-20 10:00')->create(['title' => 'Call']);
    // At the same time on another day, so it does not share the width.
    CalendarItem::factory()->for($user)->between('2026-10-21 09:00', '2026-10-21 10:00')->create(['title' => 'Gym']);

    $this->actingAs($user)
        ->get(route('calendar-items.week'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.2.items.0.title', 'Workshop')
            ->where('days.2.items.0.column', 0)
            ->where('days.2.items.0.columns', 2)
            ->where('days.2.items.1.title', 'Call')
            ->where('days.2.items.1.column', 1)
            ->where('days.2.items.1.columns', 2)
            ->where('days.3.items.0.title', 'Gym')
            ->where('days.3.items.0.column', 0)
            ->where('days.3.items.0.columns', 1));
});

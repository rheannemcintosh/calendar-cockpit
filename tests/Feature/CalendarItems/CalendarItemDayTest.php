<?php

use App\Enums\Firmness;
use App\Enums\TimeType;
use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// The day view starts at today, so pin today to Tuesday 20 October 2026.
beforeEach(fn () => $this->travelTo('2026-10-20 12:00:00'));

test('guests are sent to the login page from the day view', function () {
    $this->get(route('calendar-items.day'))->assertRedirect(route('login'));
});

test('the day view shows today when no day is given', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.day'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar-items/Day')
            ->where('date', '2026-10-20')
            ->where('label', 'Tuesday 20 October 2026')
            ->where('previousDate', '2026-10-19')
            ->where('nextDate', '2026-10-21')
            ->where('items', [])
            ->where('timeTypes', TimeType::options())
            ->where('firmnesses', Firmness::options()));
});

test('the day view shows the day it is given', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.day', ['date' => '2026-12-31']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('date', '2026-12-31')
            ->where('label', 'Thursday 31 December 2026')
            ->where('previousDate', '2026-12-30')
            ->where('nextDate', '2027-01-01'));
});

test('a day that does not exist is not found', function (string $date) {
    $this->actingAs(User::factory()->create())
        ->get("/calendar-items/day/{$date}")
        ->assertNotFound();
})->with(['30 February' => '2026-02-30', 'month 13' => '2026-13-01', 'not a date' => 'tomorrow']);

test('calendar items are placed to the minute they start and end', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Health', 'colour' => '#16A34A']);
    $subCategory = SubCategory::factory()->for($category)->create(['emoji' => '🦷', 'name' => 'Dentist']);
    CalendarItem::factory()->for($user)->for($subCategory)->approximate()
        ->between('2026-10-20 09:07', '2026-10-20 10:43')->create(['title' => 'Check-up']);

    $this->actingAs($user)
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('items', 1)
            ->where('items.0.title', 'Check-up')
            ->where('items.0.time', '09:07–10:43')
            ->where('items.0.timeType', 'approximate')
            ->where('items.0.firmness', 'confirmed')
            ->where('items.0.flags', [])
            ->where('items.0.startMinute', 9 * 60 + 7)
            ->where('items.0.endMinute', 10 * 60 + 43)
            ->where('items.0.category', ['name' => 'Health', 'colour' => '#16A34A'])
            ->where('items.0.subCategory', ['emoji' => '🦷', 'name' => 'Dentist'])
            ->where('items.0.column', 0)
            ->where('items.0.columns', 1));
});

test('calendar items on the day view show their flags in the order of the user\'s flags', function () {
    $user = User::factory()->create();
    [, $nonCancellable, $important] = $user->flags()->orderBy('id')->get()->all();
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->create()
        ->flags()->attach([$important->id, $nonCancellable->id]);

    $this->actingAs($user)
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page->where('items.0.flags', [
            ['name' => 'Non-Cancellable', 'icon' => 'lock'],
            ['name' => 'Important', 'icon' => 'star'],
        ]));
});

test('calendar items on the day view show how firm they are', function (string $firmness) {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->{$firmness}()->between('2026-10-20 09:00', '2026-10-20 10:00')->create();

    $this->actingAs($user)
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page->where('items.0.firmness', $firmness));
})->with(['tentative', 'idea']);

test('only the day\'s own calendar items are shown, in start order', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 14:00', '2026-10-20 15:00')->create(['title' => 'Afternoon']);
    CalendarItem::factory()->for($user)->between('2026-10-20 00:00', '2026-10-20 00:30')->create(['title' => 'Midnight']);
    CalendarItem::factory()->for($user)->between('2026-10-20 23:30', '2026-10-21 00:00')->create(['title' => 'Last thing']);
    CalendarItem::factory()->for($user)->between('2026-10-19 23:00', '2026-10-20 00:00')->create(['title' => 'Day before']);
    CalendarItem::factory()->for($user)->between('2026-10-21 00:00', '2026-10-21 01:00')->create(['title' => 'Day after']);
    CalendarItem::factory()->between('2026-10-20 10:00', '2026-10-20 11:00')->create(['title' => 'Someone else\'s']);

    $this->actingAs($user)
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('items', 3)
            ->where('items.0.title', 'Midnight')
            ->where('items.0.startMinute', 0)
            ->where('items.1.title', 'Afternoon')
            ->where('items.2.title', 'Last thing')
            ->where('items.2.endMinute', 1440));
});

test('a calendar item that runs past midnight is cut off at the end of the day', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 22:00', '2026-10-21 02:00')->create();

    $this->actingAs($user)
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.time', '22:00–02:00')
            ->where('items.0.startMinute', 22 * 60)
            ->where('items.0.endMinute', 1440));

    $this->actingAs($user)
        ->get(route('calendar-items.day', ['date' => '2026-10-21']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.startMinute', 0)
            ->where('items.0.endMinute', 2 * 60));
});

test('overlapping calendar items share the width side by side', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 11:00')->create(['title' => 'Workshop']);
    CalendarItem::factory()->for($user)->between('2026-10-20 09:30', '2026-10-20 10:00')->create(['title' => 'Call']);
    CalendarItem::factory()->for($user)->between('2026-10-20 10:00', '2026-10-20 10:30')->create(['title' => 'Coffee']);
    CalendarItem::factory()->for($user)->between('2026-10-20 10:15', '2026-10-20 10:45')->create(['title' => 'Chat']);
    // Starts as the workshop ends, so it does not overlap anything.
    CalendarItem::factory()->for($user)->between('2026-10-20 11:00', '2026-10-20 12:00')->create(['title' => 'Lunch']);

    $this->actingAs($user)
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.title', 'Workshop')
            ->where('items.0.column', 0)
            ->where('items.0.columns', 3)
            ->where('items.1.title', 'Call')
            ->where('items.1.column', 1)
            ->where('items.1.columns', 3)
            ->where('items.2.title', 'Coffee')
            ->where('items.2.column', 1)
            ->where('items.2.columns', 3)
            ->where('items.3.title', 'Chat')
            ->where('items.3.column', 2)
            ->where('items.3.columns', 3)
            ->where('items.4.title', 'Lunch')
            ->where('items.4.column', 0)
            ->where('items.4.columns', 1));
});

test('a calendar item with seconds still covers the minutes it touches', function (string $startsAt, string $endsAt, int $startMinute, int $endMinute) {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between($startsAt, $endsAt)->create();

    $this->actingAs($user)
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.startMinute', $startMinute)
            ->where('items.0.endMinute', $endMinute));
})->with([
    'under a minute' => ['2026-10-20 09:00:10', '2026-10-20 09:00:20', 540, 541],
    'across a minute' => ['2026-10-20 09:00:40', '2026-10-20 09:01:20', 540, 542],
    'just before midnight' => ['2026-10-20 23:59:50', '2026-10-21 00:00:00', 1439, 1440],
]);

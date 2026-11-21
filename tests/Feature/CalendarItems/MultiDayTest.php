<?php

use App\Models\CalendarItem;
use App\Models\User;
use App\Support\CalendarItemTimeline;
use Inertia\Testing\AssertableInertia as Assert;

// Pin today to Tuesday 20 October 2026, in a week that runs Sunday 18 to Saturday 24 October.
beforeEach(fn () => $this->travelTo('2026-10-20 12:00:00'));

const MULTI_DAY_CALENDAR_ITEM = [
    'title' => 'Night out',
    'date' => '2026-10-25',
    'end_date' => '2026-10-26',
    'start_time' => '15:00',
    'end_time' => '16:00',
    'time_type' => 'exact',
    'firmness' => 'confirmed',
];

test('a calendar item can run from a start date and time to a later end date and time', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), MULTI_DAY_CALENDAR_ITEM)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('calendar-items.index'));

    $calendarItem = CalendarItem::sole();

    expect($calendarItem->all_day)->toBeFalse()
        ->and($calendarItem->starts_at->toDateTimeString())->toBe('2026-10-25 15:00:00')
        ->and($calendarItem->ends_at->toDateTimeString())->toBe('2026-10-26 16:00:00');
});

test('an end time earlier than the start time is fine when the end date is later', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...MULTI_DAY_CALENDAR_ITEM, 'start_time' => '22:00', 'end_time' => '02:00'])
        ->assertSessionHasNoErrors();

    $calendarItem = CalendarItem::sole();

    expect($calendarItem->starts_at->toDateTimeString())->toBe('2026-10-25 22:00:00')
        ->and($calendarItem->ends_at->toDateTimeString())->toBe('2026-10-26 02:00:00');
});

test('a calendar item ends on its start date when no end date is sent', function (array $endDate) {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...MULTI_DAY_CALENDAR_ITEM, ...$endDate])
        ->assertSessionHasNoErrors();

    expect(CalendarItem::sole()->ends_at->toDateTimeString())->toBe('2026-10-25 16:00:00');
})->with([
    'not sent' => [['end_date' => null]],
    'empty' => [['end_date' => '']],
    'the same date' => [['end_date' => '2026-10-25']],
]);

test('a calendar item ending on its start date still needs its end time after its start time', function (string $endTime) {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...MULTI_DAY_CALENDAR_ITEM, 'end_date' => '2026-10-25', 'end_time' => $endTime])
        ->assertSessionHasErrors(['end_time' => 'The end must be after the start.']);

    expect(CalendarItem::count())->toBe(0);
})->with([
    'the same time' => '15:00',
    'earlier' => '14:59',
]);

test('a calendar item cannot end on a date before it starts', function (array $allDay) {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...MULTI_DAY_CALENDAR_ITEM, 'end_date' => '2026-10-24', ...$allDay])
        ->assertSessionHasErrors(['end_date' => 'The end must be after the start.'])
        ->assertSessionDoesntHaveErrors(['end_time']);

    expect(CalendarItem::count())->toBe(0);
})->with([
    'timed' => [[]],
    'all day' => [['all_day' => '1']],
]);

test('the end date must be a date', function (string $endDate) {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...MULTI_DAY_CALENDAR_ITEM, 'end_date' => $endDate])
        ->assertSessionHasErrors(['end_date' => 'Please choose an end date again.']);

    expect(CalendarItem::count())->toBe(0);
})->with([
    'not a date' => 'tomorrow',
    'with a time' => '2026-10-26 16:00',
]);

test('an all-day calendar item covers every day from its start date to its end date', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [
            'title' => 'Holiday',
            'date' => '2026-10-23',
            'end_date' => '2026-10-26',
            'all_day' => '1',
            'firmness' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $calendarItem = CalendarItem::sole();

    expect($calendarItem->all_day)->toBeTrue()
        ->and($calendarItem->starts_at->toDateTimeString())->toBe('2026-10-23 00:00:00')
        ->and($calendarItem->ends_at->toDateTimeString())->toBe('2026-10-27 00:00:00');
});

test('an all-day calendar item can end on the date it starts', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [
            'title' => 'My birthday',
            'date' => '2026-10-20',
            'end_date' => '2026-10-20',
            'all_day' => '1',
            'firmness' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $calendarItem = CalendarItem::sole();

    expect($calendarItem->starts_at->toDateTimeString())->toBe('2026-10-20 00:00:00')
        ->and($calendarItem->ends_at->toDateTimeString())->toBe('2026-10-21 00:00:00');
});

test('a calendar item that runs over several days shows its end day as well as its start', function (array $item, string $time) {
    $calendarItem = CalendarItem::factory()->make($item);

    expect(CalendarItemTimeline::time($calendarItem))->toBe($time);
})->with([
    'the same day' => [['starts_at' => '2026-10-25 15:00', 'ends_at' => '2026-10-25 16:00'], '15:00–16:00'],
    'ending at the next midnight' => [['starts_at' => '2026-10-25 22:00', 'ends_at' => '2026-10-26 00:00'], '22:00–00:00'],
    'past midnight' => [['starts_at' => '2026-10-25 22:00', 'ends_at' => '2026-10-26 02:00'], '22:00 – Mon 02:00'],
    'to the next day' => [['starts_at' => '2026-10-25 15:00', 'ends_at' => '2026-10-26 16:00'], '15:00 – Mon 16:00'],
    'to six days later' => [['starts_at' => '2026-10-25 15:00', 'ends_at' => '2026-10-31 16:00'], '15:00 – Sat 16:00'],
    'to a week later' => [['starts_at' => '2026-10-25 15:00', 'ends_at' => '2026-11-01 16:00'], '15:00 – Sun 1 Nov 16:00'],
    'all day on one day' => [['starts_at' => '2026-10-23 00:00', 'ends_at' => '2026-10-24 00:00', 'all_day' => true], 'All day'],
    'all day over several days' => [['starts_at' => '2026-10-23 00:00', 'ends_at' => '2026-10-27 00:00', 'all_day' => true], 'All day until Mon'],
    'all day for over a week' => [['starts_at' => '2026-10-23 00:00', 'ends_at' => '2026-11-03 00:00', 'all_day' => true], 'All day until Mon 2 Nov'],
]);

test('a calendar item that runs over several days shows once in the list, under its start date, with its end day', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-25 15:00', '2026-10-26 16:00')->create(['title' => 'Night out']);
    CalendarItem::factory()->for($user)->allDay('2026-10-23', '2026-10-26')->create(['title' => 'Holiday']);

    $this->actingAs($user)
        ->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('years.0.weeks', 2)
            ->has('years.0.weeks.0.days', 1)
            ->where('years.0.weeks.0.days.0.date', '2026-10-23')
            ->has('years.0.weeks.0.days.0.items', 1)
            ->where('years.0.weeks.0.days.0.items.0.title', 'Holiday')
            ->where('years.0.weeks.0.days.0.items.0.time', 'All day until Mon')
            ->has('years.0.weeks.1.days', 1)
            ->where('years.0.weeks.1.days.0.date', '2026-10-25')
            ->has('years.0.weeks.1.days.0.items', 1)
            ->where('years.0.weeks.1.days.0.items.0.title', 'Night out')
            ->where('years.0.weeks.1.days.0.items.0.time', '15:00 – Mon 16:00'));
});

test('a timed calendar item that runs over several days is on each day it covers in the day view, cut off at midnight', function (string $date, int $startMinute, int $endMinute) {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 15:00', '2026-10-22 16:00')->create(['title' => 'Conference']);

    $this->actingAs($user)
        ->get(route('calendar-items.day', ['date' => $date]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('allDayItems', [])
            ->has('items', 1)
            ->where('items.0.title', 'Conference')
            ->where('items.0.startMinute', $startMinute)
            ->where('items.0.endMinute', $endMinute));
})->with([
    'the start day' => ['2026-10-20', 15 * 60, 1440],
    'a day in between' => ['2026-10-21', 0, 1440],
    'the end day' => ['2026-10-22', 0, 16 * 60],
]);

test('an all-day calendar item that covers several days is in the day view\'s all-day row on each of them', function (string $date, bool $shown) {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->allDay('2026-10-23', '2026-10-26')->create(['title' => 'Holiday']);

    $this->actingAs($user)
        ->get(route('calendar-items.day', ['date' => $date]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('allDayItems', $shown ? 1 : 0)
            ->where('items', []));
})->with([
    'the day before' => ['2026-10-22', false],
    'the start date' => ['2026-10-23', true],
    'a day in between' => ['2026-10-24', true],
    'the end date' => ['2026-10-26', true],
    'the day after' => ['2026-10-27', false],
]);

test('calendar items that run over several days are on each day they cover in the week view', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 15:00', '2026-10-22 16:00')->create(['title' => 'Conference']);
    CalendarItem::factory()->for($user)->allDay('2026-10-23', '2026-10-26')->create(['title' => 'Holiday']);

    $this->actingAs($user)
        ->get(route('calendar-items.week'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.1.items', [])
            ->where('days.2.items.0.title', 'Conference')
            ->where('days.2.items.0.time', '15:00 – Thu 16:00')
            ->where('days.2.items.0.startMinute', 15 * 60)
            ->where('days.2.items.0.endMinute', 1440)
            ->where('days.3.items.0.startMinute', 0)
            ->where('days.3.items.0.endMinute', 1440)
            ->where('days.4.items.0.startMinute', 0)
            ->where('days.4.items.0.endMinute', 16 * 60)
            ->where('days.4.allDayItems', [])
            ->where('days.5.allDayItems.0.title', 'Holiday')
            ->where('days.5.allDayItems.0.time', 'All day until Mon')
            ->where('days.5.items', [])
            ->where('days.6.allDayItems.0.title', 'Holiday'));

    // The holiday carries on into the next week.
    $this->actingAs($user)
        ->get(route('calendar-items.week', ['date' => '2026-10-25']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.0.allDayItems.0.title', 'Holiday')
            ->where('days.1.allDayItems.0.title', 'Holiday')
            ->where('days.2.allDayItems', []));
});

test('calendar items that run over several days are on each day they cover in the month view', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 15:00', '2026-10-22 16:00')->create(['title' => 'Conference']);
    CalendarItem::factory()->for($user)->allDay('2026-10-23', '2026-10-26')->create(['title' => 'Holiday']);

    // October 2026 starts on a Thursday, so the 20th is the Tuesday of the 4th week.
    $this->actingAs($user)
        ->get(route('calendar-items.month'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('weeks.3.1.items', [])
            ->where('weeks.3.2.items.0.title', 'Conference')
            ->where('weeks.3.2.items.0.continued', false)
            ->where('weeks.3.2.items.0.minutes', 9 * 60)
            ->where('weeks.3.3.items.0.title', 'Conference')
            ->where('weeks.3.3.items.0.continued', true)
            ->where('weeks.3.3.items.0.minutes', 24 * 60)
            ->where('weeks.3.4.items.0.title', 'Conference')
            ->where('weeks.3.4.items.0.minutes', 16 * 60)
            ->where('weeks.3.5.items.0.title', 'Holiday')
            ->where('weeks.3.5.items.0.allDay', true)
            ->where('weeks.3.5.items.0.continued', false)
            ->where('weeks.3.6.items.0.title', 'Holiday')
            ->where('weeks.3.6.items.0.continued', true)
            ->where('weeks.4.0.items.0.title', 'Holiday')
            ->where('weeks.4.1.items.0.title', 'Holiday')
            ->where('weeks.4.1.items.0.allDay', true)
            ->where('weeks.4.2.items', []));
});

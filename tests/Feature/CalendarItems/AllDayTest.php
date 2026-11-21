<?php

use App\Enums\TimeType;
use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// Pin today to Tuesday 20 October 2026, in a week that runs Sunday 18 to Saturday 24 October.
beforeEach(fn () => $this->travelTo('2026-10-20 12:00:00'));

const ALL_DAY_CALENDAR_ITEM = [
    'title' => 'My birthday',
    'date' => '2026-10-20',
    'all_day' => '1',
    'firmness' => 'confirmed',
];

test('an all-day calendar item needs only a date, not times or a time type', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), ALL_DAY_CALENDAR_ITEM)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('calendar-items.index'));

    $calendarItem = CalendarItem::sole();

    expect($calendarItem->all_day)->toBeTrue()
        ->and($calendarItem->starts_at->toDateTimeString())->toBe('2026-10-20 00:00:00')
        ->and($calendarItem->ends_at->toDateTimeString())->toBe('2026-10-21 00:00:00')
        ->and($calendarItem->time_type)->toBe(TimeType::Exact)
        ->and($calendarItem->time_type_overridden)->toBeFalse();
});

test('times and a time type sent with an all-day calendar item are ignored', function () {
    $subCategory = SubCategory::factory()
        ->for(Category::factory()->for($user = User::factory()->create()))
        ->create(['default_time_type' => TimeType::Block]);

    $this->actingAs($user)
        ->post(route('calendar-items.store'), [
            ...ALL_DAY_CALENDAR_ITEM,
            'sub_category_id' => $subCategory->id,
            'start_time' => '10:00',
            'end_time' => '09:00',
            'time_type' => 'not a time type',
        ])
        ->assertSessionHasNoErrors();

    $calendarItem = CalendarItem::sole();

    expect($calendarItem->starts_at->toDateTimeString())->toBe('2026-10-20 00:00:00')
        ->and($calendarItem->ends_at->toDateTimeString())->toBe('2026-10-21 00:00:00')
        ->and($calendarItem->time_type_overridden)->toBeFalse();
});

test('an all-day calendar item still needs a date and a firmness', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), ['title' => 'My birthday', 'all_day' => '1'])
        ->assertSessionHasErrors([
            'date' => 'Please choose a date.',
            'firmness' => 'Please choose how firm it is.',
        ])
        ->assertSessionDoesntHaveErrors(['start_time', 'end_time', 'time_type']);

    expect(CalendarItem::count())->toBe(0);
});

test('a calendar item is not all day unless asked for, so it needs times and a time type', function (array $allDay) {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), ['title' => 'Dentist', 'date' => '2026-10-20', 'firmness' => 'confirmed', ...$allDay])
        ->assertSessionHasErrors(['start_time', 'end_time', 'time_type']);

    expect(CalendarItem::count())->toBe(0);
})->with([
    'not sent' => [[]],
    'off' => [['all_day' => '0']],
]);

test('whether a calendar item is all day must be yes or no', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...ALL_DAY_CALENDAR_ITEM, 'all_day' => 'sometimes'])
        ->assertSessionHasErrors(['all_day' => 'Please choose whether it is all day again.']);

    expect(CalendarItem::count())->toBe(0);
});

test('an all-day calendar item shows "All day" where the time would be in the list, before that day\'s timed items', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 00:00', '2026-10-20 01:00')->create(['title' => 'Midnight swim']);
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->create(['title' => 'Dentist']);
    CalendarItem::factory()->for($user)->allDay('2026-10-20')->create(['title' => 'My birthday']);

    $this->actingAs($user)
        ->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('years.0.weeks.0.days', 1)
            ->where('years.0.weeks.0.days.0.date', '2026-10-20')
            ->where('years.0.weeks.0.days.0.items.0.title', 'My birthday')
            ->where('years.0.weeks.0.days.0.items.0.allDay', true)
            ->where('years.0.weeks.0.days.0.items.0.time', 'All day')
            ->where('years.0.weeks.0.days.0.items.1.title', 'Midnight swim')
            ->where('years.0.weeks.0.days.0.items.1.allDay', false)
            ->where('years.0.weeks.0.days.0.items.1.time', '00:00–01:00')
            ->where('years.0.weeks.0.days.0.items.2.title', 'Dentist'));
});

test('an all-day calendar item is in the day view\'s all-day row, not on the timeline', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->create(['title' => 'Dentist']);
    CalendarItem::factory()->for($user)->allDay('2026-10-20')->create(['title' => 'My birthday']);
    CalendarItem::factory()->for($user)->allDay('2026-10-21')->create(['title' => 'Tomorrow']);

    $this->actingAs($user)
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('allDayItems', 1)
            ->where('allDayItems.0.title', 'My birthday')
            ->where('allDayItems.0.time', 'All day')
            ->missing('allDayItems.0.startMinute')
            ->has('items', 1)
            ->where('items.0.title', 'Dentist')
            ->where('items.0.column', 0)
            ->where('items.0.columns', 1));
});

test('a day with no all-day calendar items has an empty all-day row', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page->where('allDayItems', []));
});

test('an all-day calendar item is in the week view\'s all-day row on its day only', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->create(['title' => 'Dentist']);
    CalendarItem::factory()->for($user)->allDay('2026-10-20')->create(['title' => 'My birthday']);

    $this->actingAs($user)
        ->get(route('calendar-items.week'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('days.1.allDayItems', [])
            ->has('days.2.allDayItems', 1)
            ->where('days.2.allDayItems.0.title', 'My birthday')
            ->has('days.2.items', 1)
            ->where('days.2.items.0.title', 'Dentist')
            ->where('days.3.allDayItems', [])
            ->where('days.3.items', []));
});

test('an all-day calendar item is on its date in the month view, before that day\'s timed items', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-19 22:00', '2026-10-20 02:00')->create(['title' => 'Night out']);
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->create(['title' => 'Dentist']);
    CalendarItem::factory()->for($user)->allDay('2026-10-20')->create(['title' => 'My birthday']);

    // October 2026 starts on a Thursday, so the 20th is the Tuesday of the 4th week.
    $this->actingAs($user)
        ->get(route('calendar-items.month'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('weeks.3.2.date', '2026-10-20')
            ->has('weeks.3.2.items', 3)
            ->where('weeks.3.2.items.0.title', 'My birthday')
            ->where('weeks.3.2.items.0.allDay', true)
            ->where('weeks.3.2.items.1.title', 'Night out')
            ->where('weeks.3.2.items.2.title', 'Dentist')
            ->where('weeks.3.3.items', []));
});

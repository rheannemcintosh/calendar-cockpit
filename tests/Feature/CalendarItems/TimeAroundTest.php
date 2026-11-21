<?php

use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

// Pin today to Tuesday 20 October 2026, in a week that runs Sunday 18 to Saturday 24 October.
beforeEach(fn () => $this->travelTo('2026-10-20 12:00:00'));

const DENTIST_APPOINTMENT = [
    'title' => 'Dentist',
    'date' => '2026-10-20',
    'start_time' => '17:35',
    'end_time' => '17:55',
    'time_type' => 'exact',
    'firmness' => 'confirmed',
];

test('time before a calendar item is saved with it without changing when it starts', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...DENTIST_APPOINTMENT, 'minutes_before' => '20'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('calendar-items.index'));

    $calendarItem = CalendarItem::sole();

    expect($calendarItem->minutes_before)->toBe(20)
        ->and($calendarItem->minutes_after)->toBe(0)
        ->and($calendarItem->starts_at->toDateTimeString())->toBe('2026-10-20 17:35:00')
        ->and($calendarItem->ends_at->toDateTimeString())->toBe('2026-10-20 17:55:00');
});

test('time after a calendar item is saved with it without changing when it ends', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...DENTIST_APPOINTMENT, 'minutes_after' => '25'])
        ->assertSessionHasNoErrors();

    $calendarItem = CalendarItem::sole();

    expect($calendarItem->minutes_before)->toBe(0)
        ->and($calendarItem->minutes_after)->toBe(25)
        ->and($calendarItem->starts_at->toDateTimeString())->toBe('2026-10-20 17:35:00')
        ->and($calendarItem->ends_at->toDateTimeString())->toBe('2026-10-20 17:55:00');
});

test('a calendar item has no time before or after it when they are left empty', function (array $timeAround) {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...DENTIST_APPOINTMENT, ...$timeAround])
        ->assertSessionHasNoErrors();

    expect(CalendarItem::sole())
        ->minutes_before->toBe(0)
        ->minutes_after->toBe(0);
})->with([
    'not sent' => [[]],
    'empty' => [['minutes_before' => '', 'minutes_after' => '']],
    'zero' => [['minutes_before' => '0', 'minutes_after' => '0']],
]);

test('time before and after must be whole minutes, up to 12 hours', function (string $minutes) {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...DENTIST_APPOINTMENT, 'minutes_before' => $minutes, 'minutes_after' => $minutes])
        ->assertSessionHasErrors([
            'minutes_before' => 'Please enter the time before in whole minutes, up to 12 hours.',
            'minutes_after' => 'Please enter the time after in whole minutes, up to 12 hours.',
        ]);

    expect(CalendarItem::count())->toBe(0);
})->with(['-5', '2.5', '721', 'twenty']);

test('12 hours is the most time that can be added before or after', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...DENTIST_APPOINTMENT, 'minutes_before' => '720', 'minutes_after' => '720'])
        ->assertSessionHasNoErrors();

    expect(CalendarItem::sole())
        ->minutes_before->toBe(720)
        ->minutes_after->toBe(720);
});

test('an all-day calendar item can\'t have time before or after it', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [
            'title' => 'My birthday',
            'date' => '2026-10-20',
            'all_day' => '1',
            'firmness' => 'confirmed',
            'minutes_before' => 'not minutes',
            'minutes_after' => '30',
        ])
        ->assertSessionHasNoErrors();

    expect(CalendarItem::sole())
        ->minutes_before->toBe(0)
        ->minutes_after->toBe(0);
});

test('time before and after can be added, changed and removed when editing', function () {
    $user = User::factory()->create();
    $calendarItem = CalendarItem::factory()->for($user)->between('2026-10-20 17:35', '2026-10-20 17:55')->create();

    $this->actingAs($user)
        ->put(route('calendar-items.update', $calendarItem), [...DENTIST_APPOINTMENT, 'minutes_before' => '20', 'minutes_after' => '15'])
        ->assertSessionHasNoErrors();

    expect($calendarItem->fresh())
        ->minutes_before->toBe(20)
        ->minutes_after->toBe(15)
        ->starts_at->toDateTimeString()->toBe('2026-10-20 17:35:00')
        ->ends_at->toDateTimeString()->toBe('2026-10-20 17:55:00');

    $this->put(route('calendar-items.update', $calendarItem), [...DENTIST_APPOINTMENT, 'minutes_before' => '', 'minutes_after' => '45'])
        ->assertSessionHasNoErrors();

    expect($calendarItem->fresh())
        ->minutes_before->toBe(0)
        ->minutes_after->toBe(45);
});

test('making a calendar item all day when editing removes its time before and after', function () {
    $user = User::factory()->create();
    $calendarItem = CalendarItem::factory()->for($user)->between('2026-10-20 17:35', '2026-10-20 17:55')->withTimeAround(20, 15)->create();

    $this->actingAs($user)
        ->put(route('calendar-items.update', $calendarItem), [...DENTIST_APPOINTMENT, 'all_day' => '1', 'minutes_before' => '20', 'minutes_after' => '15'])
        ->assertSessionHasNoErrors();

    expect($calendarItem->fresh())
        ->all_day->toBeTrue()
        ->minutes_before->toBe(0)
        ->minutes_after->toBe(0);
});

test('the day view joins the time before and after to the calendar item', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 17:35', '2026-10-20 17:55')->withTimeAround(20, 15)->create();

    $this->actingAs($user)
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('items', 1)
            ->where('items.0.time', '17:35–17:55')
            ->where('items.0.timeBefore', ['minutes' => 20, 'from' => '17:15'])
            ->where('items.0.timeAfter', ['minutes' => 15, 'until' => '18:10'])
            ->where('items.0.beforeMinute', 17 * 60 + 15)
            ->where('items.0.startMinute', 17 * 60 + 35)
            ->where('items.0.endMinute', 17 * 60 + 55)
            ->where('items.0.afterMinute', 18 * 60 + 10));
});

test('a calendar item without time before or after it shows the same as before', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->create();

    $this->actingAs($user)
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.timeBefore', null)
            ->where('items.0.timeAfter', null)
            ->where('items.0.beforeMinute', 9 * 60)
            ->where('items.0.startMinute', 9 * 60)
            ->where('items.0.endMinute', 10 * 60)
            ->where('items.0.afterMinute', 10 * 60));
});

test('calendar items whose time before or after overlaps share the width side by side', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->withTimeAround(0, 30)->create(['title' => 'Dentist']);
    CalendarItem::factory()->for($user)->between('2026-10-20 10:15', '2026-10-20 11:00')->create(['title' => 'Coffee']);
    CalendarItem::factory()->for($user)->between('2026-10-20 12:00', '2026-10-20 13:00')->create(['title' => 'Lunch']);

    $this->actingAs($user)
        ->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.title', 'Dentist')
            ->where('items.0.column', 0)
            ->where('items.0.columns', 2)
            ->where('items.1.title', 'Coffee')
            ->where('items.1.column', 1)
            ->where('items.1.columns', 2)
            ->where('items.2.title', 'Lunch')
            ->where('items.2.columns', 1));
});

test('time before that starts on the day before shows on that day, cut off at its edge', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-21 00:15', '2026-10-21 01:00')->withTimeAround(30)->create(['title' => 'Early flight']);

    $this->actingAs($user)
        ->get(route('calendar-items.day', '2026-10-20'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('items', 1)
            ->where('items.0.title', 'Early flight')
            ->where('items.0.beforeMinute', 23 * 60 + 45)
            ->where('items.0.startMinute', 1440)
            ->where('items.0.endMinute', 1440)
            ->where('items.0.afterMinute', 1440));

    $this->get(route('calendar-items.day', '2026-10-21'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.beforeMinute', 0)
            ->where('items.0.startMinute', 15));
});

test('the week view joins the time before and after to the calendar item on its day', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-22 17:35', '2026-10-22 17:55')->withTimeAround(20, 15)->create();

    $this->actingAs($user)
        ->get(route('calendar-items.week'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('days.4.items', 1)
            ->where('days.4.items.0.timeBefore', ['minutes' => 20, 'from' => '17:15'])
            ->where('days.4.items.0.timeAfter', ['minutes' => 15, 'until' => '18:10'])
            ->where('days.4.items.0.beforeMinute', 17 * 60 + 15)
            ->where('days.4.items.0.afterMinute', 18 * 60 + 10));
});

test('time after that runs onto the next day shows on that day in the week view', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-22 23:00', '2026-10-22 23:50')->withTimeAround(0, 40)->create();

    $this->actingAs($user)
        ->get(route('calendar-items.week'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('days.4.items', 1)
            ->where('days.4.items.0.afterMinute', 1440)
            ->has('days.5.items', 1)
            ->where('days.5.items.0.startMinute', 0)
            ->where('days.5.items.0.endMinute', 0)
            ->where('days.5.items.0.afterMinute', 30));
});

test('the calendar items list gives the time before and after apart from the item\'s own times', function () {
    $user = User::factory()->create();
    $subCategory = SubCategory::factory()->for(Category::factory()->for($user))->create();
    CalendarItem::factory()->for($user)->for($subCategory)->between('2026-10-20 17:35', '2026-10-20 17:55')->withTimeAround(20, 15)->create();

    $this->actingAs($user)
        ->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('years.0.weeks.0.days.0.items.0.time', '17:35–17:55')
            ->where('years.0.weeks.0.days.0.items.0.timeBefore', ['minutes' => 20, 'from' => '17:15'])
            ->where('years.0.weeks.0.days.0.items.0.timeAfter', ['minutes' => 15, 'until' => '18:10']));
});

test('the edit form is filled in with the calendar item\'s time before and after', function () {
    $user = User::factory()->create();
    $calendarItem = CalendarItem::factory()->for($user)->between('2026-10-20 17:35', '2026-10-20 17:55')->withTimeAround(20, 15)->create();

    $this->actingAs($user)
        ->get(route('calendar-items.edit', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->where('calendarItem.minutesBefore', 20)
            ->where('calendarItem.minutesAfter', 15));
});

test('changes to the time before and after are logged', function () {
    $user = User::factory()->create();
    $calendarItem = CalendarItem::factory()->for($user)->between('2026-10-20 17:35', '2026-10-20 17:55')->create();

    $this->actingAs($user)
        ->put(route('calendar-items.update', $calendarItem), [...DENTIST_APPOINTMENT, 'minutes_before' => '20'])
        ->assertSessionHasNoErrors();

    expect(Activity::query()->whereMorphedTo('subject', $calendarItem)->latest('id')->firstOrFail()->attribute_changes['attributes'])
        ->toMatchArray(['minutes_before' => 20])
        ->not->toHaveKey('minutes_after');
});

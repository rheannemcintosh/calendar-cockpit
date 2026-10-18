<?php

use App\Models\CalendarItem;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

const VALID_CALENDAR_ITEM = [
    'title' => 'Dentist',
    'date' => '2026-10-20',
    'start_time' => '09:00:00',
    'end_time' => '10:30:00',
];

test('guests are sent to the login page from the create calendar item form', function () {
    $this->get(route('calendar-items.create'))->assertRedirect(route('login'));
});

test('guests cannot create a calendar item', function () {
    $this->post(route('calendar-items.store'), VALID_CALENDAR_ITEM)
        ->assertRedirect(route('login'));

    expect(CalendarItem::count())->toBe(0);
});

test('signed-in users see the create calendar item form', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('calendar-items/Create'));
});

test('a user can create a calendar item with a title, date, start time and end time', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('calendar-items.store'), VALID_CALENDAR_ITEM)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('calendar-items.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Calendar item created.']);

    $calendarItem = CalendarItem::sole();

    expect($calendarItem->user_id)->toBe($user->id)
        ->and($calendarItem->title)->toBe('Dentist')
        ->and($calendarItem->starts_at->toDateTimeString())->toBe('2026-10-20 09:00:00')
        ->and($calendarItem->ends_at->toDateTimeString())->toBe('2026-10-20 10:30:00');
});

test('times can be sent with or without seconds', function (string $startTime, string $endTime) {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...VALID_CALENDAR_ITEM, 'start_time' => $startTime, 'end_time' => $endTime])
        ->assertSessionHasNoErrors();

    expect(CalendarItem::sole()->starts_at->toDateTimeString())->toBe('2026-10-20 09:15:00');
})->with([
    'with seconds' => ['09:15:00', '10:00:00'],
    'without seconds' => ['09:15', '10:00'],
]);

test('a calendar item needs a title', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...VALID_CALENDAR_ITEM, 'title' => ''])
        ->assertSessionHasErrors(['title' => 'Please enter a title.']);

    expect(CalendarItem::count())->toBe(0);
});

test('a calendar item title cannot be longer than 255 characters', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...VALID_CALENDAR_ITEM, 'title' => str_repeat('a', 256)])
        ->assertSessionHasErrors('title');

    expect(CalendarItem::count())->toBe(0);
});

test('a calendar item needs a date, a start time and an end time', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), ['title' => 'Dentist'])
        ->assertSessionHasErrors([
            'date' => 'Please choose a date.',
            'start_time' => 'Please choose a start time.',
            'end_time' => 'Please choose an end time.',
        ]);

    expect(CalendarItem::count())->toBe(0);
});

test('the end time must be after the start time', function (string $endTime) {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...VALID_CALENDAR_ITEM, 'end_time' => $endTime])
        ->assertSessionHasErrors(['end_time' => 'The end time must be after the start time.']);

    expect(CalendarItem::count())->toBe(0);
})->with([
    'the same time' => '09:00:00',
    'earlier' => '08:59:00',
]);

test('a calendar item cannot be given a date and time in one field to span several days', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...VALID_CALENDAR_ITEM, 'date' => '2026-10-20T09:00', 'end_time' => '2026-10-22 17:00'])
        ->assertSessionHasErrors(['date', 'end_time']);

    expect(CalendarItem::count())->toBe(0);
});

<?php

use App\Models\CalendarItem;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are sent to the login page from the create event form', function () {
    $this->get(route('calendar-items.create'))->assertRedirect(route('login'));
});

test('guests cannot create a calendar item', function () {
    $this->post(route('calendar-items.store'), [
        'title' => 'Dentist',
        'starts_at' => '2026-10-20T09:00',
        'ends_at' => '2026-10-20T10:30',
    ])->assertRedirect(route('login'));

    expect(CalendarItem::count())->toBe(0);
});

test('signed-in users see the create event form', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('calendar-items/Create'));
});

test('a user can create a calendar item with a title, start and end', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('calendar-items.store'), [
            'title' => 'Dentist',
            'starts_at' => '2026-10-20T09:00',
            'ends_at' => '2026-10-20T10:30',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('calendar-items.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Event created.']);

    $calendarItem = CalendarItem::sole();

    expect($calendarItem->user_id)->toBe($user->id)
        ->and($calendarItem->title)->toBe('Dentist')
        ->and($calendarItem->starts_at->toDateTimeString())->toBe('2026-10-20 09:00:00')
        ->and($calendarItem->ends_at->toDateTimeString())->toBe('2026-10-20 10:30:00');
});

test('a calendar item can span more than one day', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [
            'title' => 'Conference',
            'starts_at' => '2026-10-20T09:00',
            'ends_at' => '2026-10-22T17:00',
        ])
        ->assertSessionHasNoErrors();

    expect(CalendarItem::sole()->ends_at->toDateTimeString())->toBe('2026-10-22 17:00:00');
});

test('a calendar item needs a title', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [
            'title' => '',
            'starts_at' => '2026-10-20T09:00',
            'ends_at' => '2026-10-20T10:30',
        ])
        ->assertSessionHasErrors(['title' => 'Please enter a title.']);

    expect(CalendarItem::count())->toBe(0);
});

test('a calendar item title cannot be longer than 255 characters', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [
            'title' => str_repeat('a', 256),
            'starts_at' => '2026-10-20T09:00',
            'ends_at' => '2026-10-20T10:30',
        ])
        ->assertSessionHasErrors('title');

    expect(CalendarItem::count())->toBe(0);
});

test('a calendar item must end after it starts', function (string $endsAt) {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [
            'title' => 'Dentist',
            'starts_at' => '2026-10-20T09:00',
            'ends_at' => $endsAt,
        ])
        ->assertSessionHasErrors(['ends_at' => 'The end must be after the start.']);

    expect(CalendarItem::count())->toBe(0);
})->with([
    'the same time' => '2026-10-20T09:00',
    'earlier' => '2026-10-20T08:59',
]);

test('a calendar item needs a start and an end', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), ['title' => 'Dentist'])
        ->assertSessionHasErrors([
            'starts_at' => 'Please choose when the event starts.',
            'ends_at' => 'Please choose when the event ends.',
        ]);

    expect(CalendarItem::count())->toBe(0);
});

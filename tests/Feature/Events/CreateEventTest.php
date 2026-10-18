<?php

use App\Models\Event;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are sent to the login page from the create event form', function () {
    $this->get(route('events.create'))->assertRedirect(route('login'));
});

test('guests cannot create an event', function () {
    $this->post(route('events.store'), [
        'title' => 'Dentist',
        'starts_at' => '2026-10-20T09:00',
        'ends_at' => '2026-10-20T10:30',
    ])->assertRedirect(route('login'));

    expect(Event::count())->toBe(0);
});

test('signed-in users see the create event form', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('events.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('events/Create'));
});

test('a user can create an event with a title, start and end', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('events.store'), [
            'title' => 'Dentist',
            'starts_at' => '2026-10-20T09:00',
            'ends_at' => '2026-10-20T10:30',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('events.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Event created.']);

    $event = Event::sole();

    expect($event->user_id)->toBe($user->id)
        ->and($event->title)->toBe('Dentist')
        ->and($event->starts_at->toDateTimeString())->toBe('2026-10-20 09:00:00')
        ->and($event->ends_at->toDateTimeString())->toBe('2026-10-20 10:30:00');
});

test('an event can span more than one day', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('events.store'), [
            'title' => 'Conference',
            'starts_at' => '2026-10-20T09:00',
            'ends_at' => '2026-10-22T17:00',
        ])
        ->assertSessionHasNoErrors();

    expect(Event::sole()->ends_at->toDateTimeString())->toBe('2026-10-22 17:00:00');
});

test('an event needs a title', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('events.store'), [
            'title' => '',
            'starts_at' => '2026-10-20T09:00',
            'ends_at' => '2026-10-20T10:30',
        ])
        ->assertSessionHasErrors(['title' => 'Please enter a title.']);

    expect(Event::count())->toBe(0);
});

test('an event title cannot be longer than 255 characters', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('events.store'), [
            'title' => str_repeat('a', 256),
            'starts_at' => '2026-10-20T09:00',
            'ends_at' => '2026-10-20T10:30',
        ])
        ->assertSessionHasErrors('title');

    expect(Event::count())->toBe(0);
});

test('an event must end after it starts', function (string $endsAt) {
    $this->actingAs(User::factory()->create())
        ->post(route('events.store'), [
            'title' => 'Dentist',
            'starts_at' => '2026-10-20T09:00',
            'ends_at' => $endsAt,
        ])
        ->assertSessionHasErrors(['ends_at' => 'The end must be after the start.']);

    expect(Event::count())->toBe(0);
})->with([
    'the same time' => '2026-10-20T09:00',
    'earlier' => '2026-10-20T08:59',
]);

test('an event needs a start and an end', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('events.store'), ['title' => 'Dentist'])
        ->assertSessionHasErrors([
            'starts_at' => 'Please choose when the event starts.',
            'ends_at' => 'Please choose when the event ends.',
        ]);

    expect(Event::count())->toBe(0);
});

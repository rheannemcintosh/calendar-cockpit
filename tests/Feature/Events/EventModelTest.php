<?php

use App\Models\Event;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;

test('the events table has the expected columns', function () {
    expect(Schema::hasColumns('events', ['id', 'user_id', 'title', 'starts_at', 'ends_at', 'created_at', 'updated_at']))->toBeTrue();
});

test('an event is saved and read back with its title, start and end intact', function () {
    $user = User::factory()->create();

    $event = $user->events()->create([
        'title' => 'Dentist',
        'starts_at' => '2026-10-20 09:00:00',
        'ends_at' => '2026-10-20 10:30:00',
    ]);

    $fresh = $event->fresh();

    expect($fresh->title)->toBe('Dentist')
        ->and($fresh->starts_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($fresh->starts_at->toDateTimeString())->toBe('2026-10-20 09:00:00')
        ->and($fresh->ends_at->toDateTimeString())->toBe('2026-10-20 10:30:00');
});

test('a user has many events and each event belongs to its user', function () {
    $user = User::factory()->create();
    $events = Event::factory()->count(2)->for($user)->create();
    Event::factory()->create();

    expect($user->events)->toHaveCount(2)
        ->and($user->events->pluck('id')->all())->toEqualCanonicalizing($events->pluck('id')->all())
        ->and($events->first()->user->is($user))->toBeTrue();
});

test('deleting a user also deletes their events', function () {
    $user = User::factory()->create();
    Event::factory()->count(2)->for($user)->create();
    $other = Event::factory()->create();

    $user->delete();

    expect(Event::count())->toBe(1)
        ->and(Event::first()->is($other))->toBeTrue();
});

test('the factory makes events that end after they start', function () {
    $event = Event::factory()->create();

    expect($event->ends_at->greaterThan($event->starts_at))->toBeTrue();
});

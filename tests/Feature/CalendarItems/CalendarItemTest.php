<?php

use App\Models\CalendarItem;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;

test('the calendar items table has the expected columns', function () {
    expect(Schema::hasColumns('calendar_items', ['id', 'user_id', 'title', 'starts_at', 'ends_at', 'created_at', 'updated_at']))->toBeTrue();
});

test('a calendar item is saved and read back with its title, start and end intact', function () {
    $user = User::factory()->create();

    $calendarItem = $user->calendarItems()->create([
        'title' => 'Dentist',
        'starts_at' => '2026-10-20 09:00:00',
        'ends_at' => '2026-10-20 10:30:00',
    ]);

    $fresh = $calendarItem->fresh();

    expect($fresh->title)->toBe('Dentist')
        ->and($fresh->starts_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($fresh->starts_at->toDateTimeString())->toBe('2026-10-20 09:00:00')
        ->and($fresh->ends_at->toDateTimeString())->toBe('2026-10-20 10:30:00');
});

test('a user has many calendar items and each calendar item belongs to its user', function () {
    $user = User::factory()->create();
    $calendarItems = CalendarItem::factory()->count(2)->for($user)->create();
    CalendarItem::factory()->create();

    expect($user->calendarItems)->toHaveCount(2)
        ->and($user->calendarItems->pluck('id')->all())->toEqualCanonicalizing($calendarItems->pluck('id')->all())
        ->and($calendarItems->first()->user->is($user))->toBeTrue();
});

test('deleting a user also deletes their calendar items', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->count(2)->for($user)->create();
    $other = CalendarItem::factory()->create();

    $user->delete();

    expect(CalendarItem::count())->toBe(1)
        ->and(CalendarItem::first()->is($other))->toBeTrue();
});

test('the factory makes calendar items that end after they start', function () {
    $calendarItem = CalendarItem::factory()->create();

    expect($calendarItem->ends_at->greaterThan($calendarItem->starts_at))->toBeTrue();
});

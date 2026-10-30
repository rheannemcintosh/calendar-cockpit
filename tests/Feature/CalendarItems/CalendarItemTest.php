<?php

use App\Enums\TimeType;
use App\Models\CalendarItem;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the calendar items table has the expected columns', function () {
    expect(Schema::hasColumns('calendar_items', ['id', 'user_id', 'title', 'starts_at', 'ends_at', 'time_type', 'created_at', 'updated_at']))->toBeTrue();
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

test('a calendar item created without a time type is exact', function () {
    $calendarItem = User::factory()->create()->calendarItems()->create([
        'title' => 'Dentist',
        'starts_at' => '2026-10-20 09:00:00',
        'ends_at' => '2026-10-20 10:30:00',
    ]);

    expect($calendarItem->time_type)->toBe(TimeType::Exact)
        ->and($calendarItem->fresh()->time_type)->toBe(TimeType::Exact);
});

test('a calendar item stored without going through the model is exact', function () {
    $id = DB::table('calendar_items')->insertGetId([
        'user_id' => User::factory()->create()->id,
        'title' => 'Dentist',
        'starts_at' => '2026-10-20 09:00:00',
        'ends_at' => '2026-10-20 10:30:00',
    ]);

    expect(CalendarItem::find($id)->time_type)->toBe(TimeType::Exact);
});

test('a calendar item is saved and read back with its time type', function (TimeType $timeType) {
    $calendarItem = CalendarItem::factory()->create(['time_type' => $timeType]);

    expect($calendarItem->fresh()->time_type)->toBe($timeType);
})->with(TimeType::cases());

test('the factory makes exact calendar items by default', function () {
    expect(CalendarItem::factory()->create()->time_type)->toBe(TimeType::Exact);
});

test('the factory has a state for each of the other time types', function (string $state, TimeType $timeType) {
    expect(CalendarItem::factory()->{$state}()->create()->fresh()->time_type)->toBe($timeType);
})->with([
    'window' => ['window', TimeType::Window],
    'block' => ['block', TimeType::Block],
    'approximate' => ['approximate', TimeType::Approximate],
]);

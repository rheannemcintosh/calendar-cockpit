<?php

use App\Enums\TimeType;
use App\Models\CalendarItem;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the calendar items table has the expected columns', function () {
    expect(Schema::hasColumns('calendar_items', ['id', 'user_id', 'title', 'starts_at', 'ends_at', 'time_type', 'time_type_overridden', 'location_name', 'location_address', 'location_latitude', 'location_longitude', 'created_at', 'updated_at']))->toBeTrue();
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

test('the database refuses a time type that is not one of the four', function () {
    $insert = fn () => DB::table('calendar_items')->insert([
        'user_id' => User::factory()->create()->id,
        'title' => 'Dentist',
        'starts_at' => '2026-10-20 09:00:00',
        'ends_at' => '2026-10-20 10:30:00',
        'time_type' => 'sometime',
    ]);

    expect($insert)->toThrow(QueryException::class, 'calendar_items_time_type_check')
        ->and(CalendarItem::count())->toBe(0);
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

test('a calendar item is not overridden unless told otherwise', function () {
    $calendarItem = User::factory()->create()->calendarItems()->create([
        'title' => 'Dentist',
        'starts_at' => '2026-10-20 09:00:00',
        'ends_at' => '2026-10-20 10:30:00',
    ]);

    expect($calendarItem->time_type_overridden)->toBeFalse()
        ->and($calendarItem->fresh()->time_type_overridden)->toBeFalse();
});

test('a calendar item stored without going through the model is not overridden', function () {
    $id = DB::table('calendar_items')->insertGetId([
        'user_id' => User::factory()->create()->id,
        'title' => 'Dentist',
        'starts_at' => '2026-10-20 09:00:00',
        'ends_at' => '2026-10-20 10:30:00',
    ]);

    expect(CalendarItem::find($id)->time_type_overridden)->toBeFalse();
});

test('a calendar item has no location unless given one', function () {
    $calendarItem = CalendarItem::factory()->create()->fresh();

    expect($calendarItem->location_name)->toBeNull()
        ->and($calendarItem->location_address)->toBeNull()
        ->and($calendarItem->location_latitude)->toBeNull()
        ->and($calendarItem->location_longitude)->toBeNull();
});

test('a calendar item is saved and read back with its location', function () {
    $calendarItem = User::factory()->create()->calendarItems()->create([
        'title' => 'Dentist',
        'starts_at' => '2026-10-20 09:00:00',
        'ends_at' => '2026-10-20 10:30:00',
        'location_name' => 'Smile Dental Practice',
        'location_address' => '12 High Street, Edinburgh EH1 1SR, United Kingdom',
        'location_latitude' => 55.9500812,
        'location_longitude' => -3.1883611,
    ])->fresh();

    expect($calendarItem->location_name)->toBe('Smile Dental Practice')
        ->and($calendarItem->location_address)->toBe('12 High Street, Edinburgh EH1 1SR, United Kingdom')
        ->and($calendarItem->location_latitude)->toBe(55.9500812)
        ->and($calendarItem->location_longitude)->toBe(-3.1883611);
});

test('a calendar item location does not need an address', function () {
    $calendarItem = CalendarItem::factory()->create([
        'location_name' => 'Arthur\'s Seat',
        'location_latitude' => 55.9440833,
        'location_longitude' => -3.1618333,
    ])->fresh();

    expect($calendarItem->location_name)->toBe('Arthur\'s Seat')
        ->and($calendarItem->location_address)->toBeNull();
});

test('the factory has a state for a calendar item with a location', function () {
    $calendarItem = CalendarItem::factory()->withLocation()->create()->fresh();

    expect($calendarItem->location_name)->toBeString()->not->toBeEmpty()
        ->and($calendarItem->location_address)->toBeString()->not->toBeEmpty()
        ->and($calendarItem->location_latitude)->toBeFloat()
        ->and($calendarItem->location_longitude)->toBeFloat();
});

test('the database refuses a location that is only partly filled in', function (array $location) {
    $insert = fn () => DB::table('calendar_items')->insert([
        'user_id' => User::factory()->create()->id,
        'title' => 'Dentist',
        'starts_at' => '2026-10-20 09:00:00',
        'ends_at' => '2026-10-20 10:30:00',
        ...$location,
    ]);

    expect($insert)->toThrow(QueryException::class, 'calendar_items_location_check')
        ->and(CalendarItem::count())->toBe(0);
})->with([
    'an address only' => [['location_address' => '12 High Street, Edinburgh']],
    'a name without a point' => [['location_name' => 'Smile Dental Practice']],
    'a point without a name' => [['location_latitude' => 55.95, 'location_longitude' => -3.19]],
    'a latitude without a longitude' => [['location_name' => 'Smile Dental Practice', 'location_latitude' => 55.95]],
    'a longitude without a latitude' => [['location_name' => 'Smile Dental Practice', 'location_longitude' => -3.19]],
]);

test('the database refuses a point that is not on the map', function (float $latitude, float $longitude) {
    $insert = fn () => DB::table('calendar_items')->insert([
        'user_id' => User::factory()->create()->id,
        'title' => 'Dentist',
        'starts_at' => '2026-10-20 09:00:00',
        'ends_at' => '2026-10-20 10:30:00',
        'location_name' => 'Nowhere',
        'location_latitude' => $latitude,
        'location_longitude' => $longitude,
    ]);

    expect($insert)->toThrow(QueryException::class, 'calendar_items_location_check');
})->with([
    'latitude above 90' => [90.5, 0],
    'latitude below -90' => [-90.5, 0],
    'longitude above 180' => [0, 180.5],
    'longitude below -180' => [0, -180.5],
]);

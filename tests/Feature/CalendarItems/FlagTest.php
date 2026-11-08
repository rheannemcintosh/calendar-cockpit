<?php

use App\Enums\FlagIcon;
use App\Models\CalendarItem;
use App\Models\Flag;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the flags table has the expected columns', function () {
    expect(Schema::hasColumns('flags', ['id', 'user_id', 'name', 'icon', 'created_at', 'updated_at']))->toBeTrue()
        ->and(Schema::hasColumns('calendar_item_flag', ['calendar_item_id', 'flag_id']))->toBeTrue();
});

test('the flag icons start with pound sterling, lock and star, the icons of the default flags', function () {
    expect(array_slice(array_map(fn (FlagIcon $icon) => $icon->value, FlagIcon::cases()), 0, 3))
        ->toBe(['pound-sterling', 'lock', 'star']);
});

test('the database accepts every flag icon and nothing else', function () {
    $user = User::factory()->create();

    foreach (FlagIcon::cases() as $icon) {
        $user->flags()->create(['name' => "Flag {$icon->value}", 'icon' => $icon]);
    }

    expect($user->flags()->count())->toBe(count(FlagIcon::cases()) + 3);
});

test('a new user starts with the Pre-Paid, Non-Cancellable and Important flags in that order', function () {
    $user = User::factory()->create();

    expect($user->flags()->orderBy('id')->get()->map(fn (Flag $flag) => [$flag->name, $flag->icon])->all())->toBe([
        ['Pre-Paid', FlagIcon::PoundSterling],
        ['Non-Cancellable', FlagIcon::Lock],
        ['Important', FlagIcon::Star],
    ]);
});

test('a user who registers starts with the default flags', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::sole()->flags()->orderBy('id')->pluck('name')->all())
        ->toBe(['Pre-Paid', 'Non-Cancellable', 'Important']);
});

test('existing users are given the default flags when flags are added', function () {
    $users = User::factory()->count(2)->create();
    $migration = require database_path('migrations/2026_11_08_000001_create_flags_table.php');

    $migration->down();
    $migration->up();

    foreach ($users as $user) {
        expect($user->flags()->orderBy('id')->get()->map(fn (Flag $flag) => [$flag->name, $flag->icon])->all())->toBe([
            ['Pre-Paid', FlagIcon::PoundSterling],
            ['Non-Cancellable', FlagIcon::Lock],
            ['Important', FlagIcon::Star],
        ]);
    }
});

test('the database refuses an icon that is not one of the set list', function () {
    $insert = fn () => DB::table('flags')->insert([
        'user_id' => User::factory()->create()->id,
        'name' => 'Birthday',
        'icon' => 'cake',
    ]);

    expect($insert)->toThrow(QueryException::class, 'flags_icon_check');
});

test('a user cannot have two flags with the same name', function () {
    $user = User::factory()->create();

    expect(fn () => $user->flags()->create(['name' => 'Important', 'icon' => FlagIcon::Star]))
        ->toThrow(QueryException::class);
});

test('different users can have flags with the same name', function () {
    User::factory()->count(2)->create();

    expect(Flag::where('name', 'Important')->count())->toBe(2);
});

test('a calendar item can have any number of flags, always in the order of the user\'s flags', function () {
    $user = User::factory()->create();
    [$prePaid, , $important] = $user->flags()->orderBy('id')->get()->all();
    $calendarItem = CalendarItem::factory()->for($user)->create();

    $calendarItem->flags()->attach([$important->id, $prePaid->id]);

    expect($calendarItem->fresh()->flags->pluck('name')->all())->toBe(['Pre-Paid', 'Important'])
        ->and(CalendarItem::factory()->create()->flags)->toBeEmpty();
});

test('deleting a flag takes it off its calendar items', function () {
    $user = User::factory()->create();
    $flag = $user->flags()->firstOrFail();
    $calendarItem = CalendarItem::factory()->for($user)->create();
    $calendarItem->flags()->attach($flag);

    $flag->delete();

    expect($calendarItem->fresh()->flags)->toBeEmpty()
        ->and(Flag::find($flag->id))->toBeNull();
});

test('deleting a calendar item takes its flags off it but keeps the flags', function () {
    $user = User::factory()->create();
    $calendarItem = CalendarItem::factory()->for($user)->create();
    $calendarItem->flags()->attach($user->flags()->pluck('id'));

    $calendarItem->delete();

    expect(DB::table('calendar_item_flag')->count())->toBe(0)
        ->and($user->flags()->count())->toBe(3);
});

test('deleting a user deletes their flags and the flags on their calendar items', function () {
    $user = User::factory()->create();
    $calendarItem = CalendarItem::factory()->for($user)->create();
    $calendarItem->flags()->attach($user->flags()->pluck('id'));

    $user->delete();

    expect(Flag::count())->toBe(0)
        ->and(DB::table('calendar_item_flag')->count())->toBe(0);
});

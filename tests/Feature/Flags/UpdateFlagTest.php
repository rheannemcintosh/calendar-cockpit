<?php

use App\Enums\FlagIcon;
use App\Models\CalendarItem;
use App\Models\Flag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->travelTo('2026-10-20 12:00:00'));

const UPDATED_FLAG = [
    'name' => 'Paid in full',
    'icon' => 'credit-card',
];

test('guests are sent to the login page from the edit flag form', function () {
    $this->get(route('flags.edit', Flag::factory()->create()))->assertRedirect(route('login'));
});

test('guests cannot change a flag', function () {
    $flag = Flag::factory()->create(['name' => 'Birthday']);

    $this->put(route('flags.update', $flag), UPDATED_FLAG)->assertRedirect(route('login'));

    expect($flag->fresh()->name)->toBe('Birthday');
});

test('the edit form is filled in with the flag\'s current name and icon and offers every icon', function () {
    $user = User::factory()->create();
    $flag = $user->flags()->where('name', 'Pre-Paid')->firstOrFail();

    $this->actingAs($user)
        ->get(route('flags.edit', $flag))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('flags/Edit')
            ->where('flag', ['id' => $flag->id, 'name' => 'Pre-Paid', 'icon' => 'pound-sterling'])
            ->has('icons', count(FlagIcon::cases())));
});

test('a user can change any of their flags\' name and icon, including the ones they started with', function (string $name) {
    $user = User::factory()->create();
    $flag = $user->flags()->where('name', $name)->firstOrFail();

    $this->actingAs($user)
        ->put(route('flags.update', $flag), UPDATED_FLAG)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Flag updated.'])
        ->assertRedirect(route('flags.index'));

    expect($flag->fresh())
        ->name->toBe('Paid in full')
        ->icon->toBe(FlagIcon::CreditCard)
        ->user_id->toBe($user->id);
})->with(['Pre-Paid', 'Non-Cancellable', 'Important']);

test('every calendar item with the flag shows its new name and icon', function () {
    $user = User::factory()->create();
    $flag = $user->flags()->where('name', 'Pre-Paid')->firstOrFail();
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->create()->flags()->attach($flag);
    CalendarItem::factory()->for($user)->between('2026-10-20 14:00', '2026-10-20 15:00')->create()->flags()->attach($flag);

    $this->actingAs($user)->put(route('flags.update', $flag), UPDATED_FLAG);

    $this->get(route('calendar-items.day'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.flags', [['name' => 'Paid in full', 'icon' => 'credit-card']])
            ->where('items.1.flags', [['name' => 'Paid in full', 'icon' => 'credit-card']]));

    $this->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('years.0.weeks.0.days.0.items.0.flags', [['name' => 'Paid in full', 'icon' => 'credit-card']]));
});

test('a flag can keep its own name, in any case, while its icon changes', function (string $name) {
    $user = User::factory()->create();
    $flag = $user->flags()->where('name', 'Important')->firstOrFail();

    $this->actingAs($user)
        ->put(route('flags.update', $flag), ['name' => $name, 'icon' => 'heart'])
        ->assertSessionHasNoErrors();

    expect($flag->fresh())
        ->name->toBe(trim($name))
        ->icon->toBe(FlagIcon::Heart);
})->with([
    'the same' => 'Important',
    'lower case' => 'important',
    'spaces at either end' => '  Important ',
]);

test('a flag cannot be renamed to the name of another of the user\'s flags', function (string $name) {
    $user = User::factory()->create();
    $flag = $user->flags()->where('name', 'Important')->firstOrFail();

    $this->actingAs($user)
        ->put(route('flags.update', $flag), [...UPDATED_FLAG, 'name' => $name])
        ->assertSessionHasErrors(['name' => 'You already have a flag with that name.']);

    expect($flag->fresh()->name)->toBe('Important');
})->with([
    'the same' => 'Pre-Paid',
    'upper case' => 'PRE-PAID',
]);

test('the same checks apply as when adding a flag', function (array $data, array $errors) {
    $user = User::factory()->create();
    $flag = $user->flags()->where('name', 'Important')->firstOrFail();

    $this->actingAs($user)
        ->put(route('flags.update', $flag), $data)
        ->assertSessionHasErrors($errors);

    expect($flag->fresh())
        ->name->toBe('Important')
        ->icon->toBe(FlagIcon::Star);
})->with([
    'no name or icon' => [
        ['name' => '', 'icon' => ''],
        ['name' => 'Please enter a name.', 'icon' => 'Please choose an icon.'],
    ],
    'a name that is too long' => [
        ['name' => str_repeat('a', 51), 'icon' => 'star'],
        ['name' => 'The name cannot be longer than 50 characters.'],
    ],
    'an icon that is not in the set list' => [
        ['name' => 'Important', 'icon' => 'not-an-icon'],
        ['icon' => 'Please choose an icon again.'],
    ],
]);

test('a user gets a not found page for another user\'s flag and nothing is saved', function () {
    $flag = Flag::factory()->create(['name' => 'Birthday', 'icon' => 'gift']);

    $this->actingAs(User::factory()->create());

    $this->get(route('flags.edit', $flag))->assertNotFound();
    $this->put(route('flags.update', $flag), UPDATED_FLAG)->assertNotFound();

    expect($flag->fresh())
        ->name->toBe('Birthday')
        ->icon->toBe(FlagIcon::Gift);
});

test('a user gets a not found page for a flag that does not exist', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('flags.edit', 999999))
        ->assertNotFound();
});

<?php

use App\Enums\FlagIcon;
use App\Models\Flag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

const VALID_FLAG = [
    'name' => 'Birthday',
    'icon' => 'gift',
];

test('guests are sent to the login page from the new flag form', function () {
    $this->get(route('flags.create'))->assertRedirect(route('login'));
});

test('guests cannot add a flag', function () {
    $this->post(route('flags.store'), VALID_FLAG)->assertRedirect(route('login'));

    expect(Flag::count())->toBe(0);
});

test('the new flag form offers every icon in the set list, each with a label', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('flags.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('flags/Create')
            ->has('icons', count(FlagIcon::cases()))
            ->where('icons.0', ['value' => 'pound-sterling', 'label' => 'Pound sign'])
            ->where('icons.11', ['value' => 'gift', 'label' => 'Gift']));
});

test('a user can add a flag with a name and an icon and goes back to the flags page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('flags.store'), VALID_FLAG)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Flag added.'])
        ->assertRedirect(route('flags.index'));

    $flag = $user->flags()->latest('id')->firstOrFail();

    expect($flag)
        ->name->toBe('Birthday')
        ->icon->toBe(FlagIcon::Gift);
});

test('a new flag can be chosen when creating a calendar item', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('flags.store'), VALID_FLAG);

    $flag = $user->flags()->where('name', 'Birthday')->firstOrFail();

    $this->get(route('calendar-items.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('flags', 4)
            ->where('flags.3', ['id' => $flag->id, 'name' => 'Birthday', 'icon' => 'gift']));
});

test('a flag needs a name and an icon', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('flags.store'), ['name' => '', 'icon' => ''])
        ->assertSessionHasErrors([
            'name' => 'Please enter a name.',
            'icon' => 'Please choose an icon.',
        ]);

    expect($user->flags()->count())->toBe(3);
});

test('a flag icon must be one of the set list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('flags.store'), [...VALID_FLAG, 'icon' => 'not-an-icon'])
        ->assertSessionHasErrors(['icon' => 'Please choose an icon again.']);

    expect($user->flags()->count())->toBe(3);
});

test('a flag name cannot be longer than 50 characters', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('flags.store'), [...VALID_FLAG, 'name' => str_repeat('a', 51)])
        ->assertSessionHasErrors(['name' => 'The name cannot be longer than 50 characters.']);
});

test('a flag name can be 50 characters long and spaces at either end are trimmed', function () {
    $user = User::factory()->create();
    $name = str_repeat('a', 50);

    $this->actingAs($user)
        ->post(route('flags.store'), [...VALID_FLAG, 'name' => "  {$name} "])
        ->assertSessionHasNoErrors();

    expect($user->flags()->where('name', $name)->exists())->toBeTrue();
});

test('a flag name must be different from the user\'s other flags\' names', function (string $name) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('flags.store'), [...VALID_FLAG, 'name' => $name])
        ->assertSessionHasErrors(['name' => 'You already have a flag with that name.']);

    expect($user->flags()->count())->toBe(3);
})->with([
    'the same' => 'Important',
    'upper case' => 'IMPORTANT',
    'spaces at either end' => ' Important  ',
]);

test('a flag can have the same name as another user\'s flag', function () {
    Flag::factory()->create(['name' => 'Birthday']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('flags.store'), VALID_FLAG)
        ->assertSessionHasNoErrors();

    expect($user->flags()->where('name', 'Birthday')->exists())->toBeTrue();
});

test('a flag can use any icon in the set list', function (FlagIcon $icon) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('flags.store'), [...VALID_FLAG, 'icon' => $icon->value])
        ->assertSessionHasNoErrors();

    expect($user->flags()->where('name', 'Birthday')->firstOrFail()->icon)->toBe($icon);
})->with(FlagIcon::cases());

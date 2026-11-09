<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// An older name for a timezone that PHP still knows, such as America/Montreal.
// Which ones it knows depends on the timezone database it is built with.
function olderTimezoneName(): string
{
    $older = array_diff(DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), DateTimeZone::listIdentifiers());

    if ($older === []) {
        test()->markTestSkipped('PHP knows no older names for timezones.');
    }

    return reset($older);
}

test('guests cannot set a timezone', function () {
    $this->post(route('profile.timezone.store'), ['timezone' => 'Europe/London'])
        ->assertRedirect(route('login'));
});

test('a user with no timezone gets the one their browser gives', function () {
    $user = User::factory()->create(['timezone' => null]);

    $this->actingAs($user)
        ->from(route('calendar-items.day'))
        ->post(route('profile.timezone.store'), ['timezone' => 'Europe/London'])
        ->assertRedirect(route('calendar-items.day'));

    expect($user->refresh()->timezone)->toBe('Europe/London');
});

test('the browser\'s timezone does not replace one the user already has', function () {
    $user = User::factory()->create(['timezone' => 'Pacific/Auckland']);

    $this->actingAs($user)
        ->from(route('calendar-items.day'))
        ->post(route('profile.timezone.store'), ['timezone' => 'Europe/London'])
        ->assertRedirect(route('calendar-items.day'));

    expect($user->refresh()->timezone)->toBe('Pacific/Auckland');
});

test('a timezone the browser gives that is not a real one is ignored', function (mixed $timezone) {
    $user = User::factory()->create(['timezone' => null]);

    $this->actingAs($user)
        ->from(route('calendar-items.day'))
        ->post(route('profile.timezone.store'), ['timezone' => $timezone])
        ->assertRedirect(route('calendar-items.day'));

    expect($user->refresh()->timezone)->toBeNull();
})->with([
    'not a timezone' => ['Mars/Olympus_Mons'],
    'empty' => [''],
    'not a string' => [['Europe/London']],
]);

test('an older name for a timezone that some browsers give is kept', function () {
    $timezone = olderTimezoneName();
    $user = User::factory()->create(['timezone' => null]);

    $this->actingAs($user)->post(route('profile.timezone.store'), ['timezone' => $timezone]);

    expect($user->refresh()->timezone)->toBe($timezone);
});

test('the profile page lists every timezone, in alphabetical order', function () {
    $this->actingAs(User::factory()->create(['timezone' => 'Europe/London']))
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Profile')
            ->where('timezones', DateTimeZone::listIdentifiers()));
});

test('the profile page keeps an older name for a timezone the user has', function () {
    $timezone = olderTimezoneName();

    $this->actingAs(User::factory()->create(['timezone' => $timezone]))
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('timezones', fn ($timezones) => collect($timezones)->contains($timezone)
                && collect($timezones)->sort()->values()->all() === collect($timezones)->values()->all()));
});

test('a user can change their timezone in their profile', function () {
    $user = User::factory()->create(['timezone' => 'Europe/London']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'timezone' => 'America/New_York',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->timezone)->toBe('America/New_York');
});

test('a timezone is needed to update a profile, and it must be a real one', function (mixed $timezone) {
    $user = User::factory()->create(['timezone' => 'Europe/London']);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'timezone' => $timezone,
        ])
        ->assertSessionHasErrors('timezone')
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->timezone)->toBe('Europe/London');
})->with([
    'missing' => [null],
    'not a timezone' => ['Mars/Olympus_Mons'],
]);

test('a new user gets the timezone their browser gives', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'timezone' => 'Europe/London',
    ]);

    $this->assertAuthenticated();
    expect(User::firstWhere('email', 'test@example.com')->timezone)->toBe('Europe/London');
});

test('a new user whose browser gives no usable timezone still registers, with none yet', function (?string $timezone) {
    $this->post(route('register.store'), array_filter([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'timezone' => $timezone,
    ]));

    $this->assertAuthenticated();
    expect(User::firstWhere('email', 'test@example.com')->timezone)->toBeNull();
})->with([
    'none' => [null],
    'not a timezone' => ['Mars/Olympus_Mons'],
]);

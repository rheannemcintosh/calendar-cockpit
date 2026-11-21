<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// Created at 09:05 UTC on Saturday 21 November 2026, which is 09:05 in London
// and 22:05 in Auckland.
beforeEach(fn () => $this->travelTo('2026-11-21 09:05:00'));

test('the profile page shows only when the account was created when it has not been changed', function () {
    $user = User::factory()->create(['timezone' => 'Europe/London']);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('timestamps', ['created' => '21 Nov 2026, 09:05', 'updated' => null]));
});

test('the profile page shows when the account was created and last updated once it has been changed', function () {
    $user = User::factory()->create(['timezone' => 'Europe/London']);

    $this->travelTo('2026-11-21 14:30:00');
    $user->update(['name' => 'Someone Else']);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('timestamps', ['created' => '21 Nov 2026, 09:05', 'updated' => '21 Nov 2026, 14:30']));
});

test('the account\'s dates are in the user\'s timezone', function () {
    $user = User::factory()->create(['timezone' => 'Pacific/Auckland']);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('timestamps.created', '21 Nov 2026, 22:05'));
});

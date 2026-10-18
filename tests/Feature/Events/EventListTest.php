<?php

use App\Models\Event;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are sent to the login page from the events list', function () {
    $this->get(route('events.index'))->assertRedirect(route('login'));
});

test('a user with no events gets an empty list', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('events/Index')
            ->where('years', []));
});

test('events are grouped by year, week and day', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:30')->create(['title' => 'Dentist']);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('events/Index')
            ->has('years', 1)
            ->where('years.0.year', 2026)
            ->has('years.0.weeks', 1)
            ->where('years.0.weeks.0.number', 43)
            ->where('years.0.weeks.0.range', '18–24 Oct')
            ->has('years.0.weeks.0.days', 1)
            ->where('years.0.weeks.0.days.0.date', '2026-10-20')
            ->where('years.0.weeks.0.days.0.label', 'Tuesday 20 October')
            ->has('years.0.weeks.0.days.0.events', 1)
            ->where('years.0.weeks.0.days.0.events.0.title', 'Dentist')
            ->where('years.0.weeks.0.days.0.events.0.time', '09:00–10:30'));
});

test('years, weeks, days and events are in date order, earliest first', function () {
    $user = User::factory()->create();
    // Created out of order on purpose.
    Event::factory()->for($user)->between('2027-02-03 12:00', '2027-02-03 13:00')->create(['title' => 'Next year']);
    Event::factory()->for($user)->between('2026-10-24 15:00', '2026-10-24 16:00')->create(['title' => 'Saturday']);
    Event::factory()->for($user)->between('2026-10-18 14:00', '2026-10-18 15:00')->create(['title' => 'Sunday afternoon']);
    Event::factory()->for($user)->between('2026-10-18 09:00', '2026-10-18 10:00')->create(['title' => 'Sunday morning']);
    Event::factory()->for($user)->between('2026-11-02 09:00', '2026-11-02 10:00')->create(['title' => 'Later week']);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('years.0.year', 2026)
            ->where('years.1.year', 2027)
            ->where('years.0.weeks.0.number', 43)
            ->where('years.0.weeks.1.number', 45)
            ->where('years.0.weeks.0.days.0.label', 'Sunday 18 October')
            ->where('years.0.weeks.0.days.0.events.0.title', 'Sunday morning')
            ->where('years.0.weeks.0.days.0.events.1.title', 'Sunday afternoon')
            ->where('years.0.weeks.0.days.1.label', 'Saturday 24 October')
            ->where('years.0.weeks.1.days.0.events.0.title', 'Later week')
            ->where('years.1.weeks.0.days.0.events.0.title', 'Next year'));
});

test('an event that spans several days is listed once, under the day it starts', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->between('2026-10-20 09:00', '2026-10-22 17:00')->create(['title' => 'Conference']);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('years.0.weeks.0.days', 1)
            ->where('years.0.weeks.0.days.0.date', '2026-10-20')
            ->where('years.0.weeks.0.days.0.events.0.time', '09:00 – Thu 22 Oct, 17:00'));
});

test('a week that crosses New Year appears once, as week 1 of the new year', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->between('2026-12-28 09:00', '2026-12-28 10:00')->create(['title' => 'Before New Year']);
    Event::factory()->for($user)->between('2027-01-01 09:00', '2027-01-01 10:00')->create(['title' => 'New Year’s Day']);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('years', 1)
            ->where('years.0.year', 2027)
            ->has('years.0.weeks', 1)
            ->where('years.0.weeks.0.number', 1)
            ->where('years.0.weeks.0.range', '27 Dec 2026 – 2 Jan 2027')
            ->where('years.0.weeks.0.days.0.label', 'Monday 28 December')
            ->where('years.0.weeks.0.days.1.label', 'Friday 1 January'));
});

test('users only see their own events', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->create(['title' => 'Mine']);
    Event::factory()->between('2026-10-20 11:00', '2026-10-20 12:00')->create(['title' => 'Someone else’s']);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('years.0.weeks.0.days.0.events', 1)
            ->where('years.0.weeks.0.days.0.events.0.title', 'Mine'));
});

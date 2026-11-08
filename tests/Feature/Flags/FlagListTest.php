<?php

use App\Models\CalendarItem;
use App\Models\Flag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are sent to the login page from the flags page', function () {
    $this->get(route('flags.index'))->assertRedirect(route('login'));
});

test('a user sees each of their flags with its icon and name, in the order they are shown', function () {
    $user = User::factory()->create();
    $birthday = $user->flags()->create(['name' => 'Birthday', 'icon' => 'gift']);
    $flags = $user->flags()->orderBy('id')->get();

    $this->actingAs($user)
        ->get(route('flags.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('flags/Index')
            ->where('flags', [
                ['id' => $flags[0]->id, 'name' => 'Pre-Paid', 'icon' => 'pound-sterling', 'calendarItemsCount' => 0],
                ['id' => $flags[1]->id, 'name' => 'Non-Cancellable', 'icon' => 'lock', 'calendarItemsCount' => 0],
                ['id' => $flags[2]->id, 'name' => 'Important', 'icon' => 'star', 'calendarItemsCount' => 0],
                ['id' => $birthday->id, 'name' => 'Birthday', 'icon' => 'gift', 'calendarItemsCount' => 0],
            ]));
});

test('each flag says how many calendar items have it', function () {
    $user = User::factory()->create();
    [$prePaid, , $important] = $user->flags()->orderBy('id')->get()->all();
    CalendarItem::factory()->count(2)->for($user)->create()
        ->each(fn (CalendarItem $calendarItem) => $calendarItem->flags()->attach($prePaid));
    CalendarItem::factory()->for($user)->create()->flags()->attach([$prePaid->id, $important->id]);

    $this->actingAs($user)
        ->get(route('flags.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('flags.0.calendarItemsCount', 3)
            ->where('flags.1.calendarItemsCount', 0)
            ->where('flags.2.calendarItemsCount', 1));
});

test('a user does not see other users\' flags', function () {
    Flag::factory()->create(['name' => 'Someone else\'s']);
    $user = User::factory()->create();
    $user->flags()->delete();

    $this->actingAs($user)
        ->get(route('flags.index'))
        ->assertInertia(fn (Assert $page) => $page->has('flags', 0));
});

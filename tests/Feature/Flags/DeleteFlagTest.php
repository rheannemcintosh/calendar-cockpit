<?php

use App\Models\CalendarItem;
use App\Models\Flag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot delete a flag', function () {
    $flag = Flag::factory()->create();

    $this->delete(route('flags.destroy', $flag))->assertRedirect(route('login'));

    expect($flag->fresh())->not->toBeNull();
});

test('a user can delete any of their flags, including the ones they started with, and goes back to the flags page', function (string $name) {
    $user = User::factory()->create();
    $flag = $user->flags()->where('name', $name)->firstOrFail();

    $this->actingAs($user)
        ->delete(route('flags.destroy', $flag))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Flag deleted.'])
        ->assertRedirect(route('flags.index'));

    expect($flag->fresh())->toBeNull()
        ->and($user->flags()->count())->toBe(2);
})->with(['Pre-Paid', 'Non-Cancellable', 'Important']);

test('deleting a flag takes it off its calendar items and keeps the calendar items and their other flags', function () {
    $user = User::factory()->create();
    [$prePaid, , $important] = $user->flags()->orderBy('id')->get()->all();
    $concert = CalendarItem::factory()->for($user)->create();
    $concert->flags()->attach([$prePaid->id, $important->id]);
    $dinner = CalendarItem::factory()->for($user)->create();
    $dinner->flags()->attach($prePaid);

    $this->actingAs($user)->delete(route('flags.destroy', $prePaid));

    expect($concert->fresh()->flags->pluck('name')->all())->toBe(['Important'])
        ->and($dinner->fresh())->not->toBeNull()
        ->and($dinner->fresh()->flags)->toBeEmpty();
});

test('a deleted flag is no longer listed or offered on the create calendar item form', function () {
    $user = User::factory()->create();
    $flag = $user->flags()->where('name', 'Pre-Paid')->firstOrFail();

    $this->actingAs($user)->delete(route('flags.destroy', $flag));

    $this->get(route('flags.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('flags', 2)
            ->where('flags.0.name', 'Non-Cancellable'));

    $this->get(route('calendar-items.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('flags', 2)
            ->where('flags.0.name', 'Non-Cancellable'));
});

test('a user gets a not found page for another user\'s flag and nothing is deleted', function () {
    $owner = User::factory()->create();
    $flag = $owner->flags()->firstOrFail();
    $calendarItem = CalendarItem::factory()->for($owner)->create();
    $calendarItem->flags()->attach($flag);

    $this->actingAs(User::factory()->create())
        ->delete(route('flags.destroy', $flag))
        ->assertNotFound();

    expect($flag->fresh())->not->toBeNull()
        ->and($calendarItem->fresh()->flags->pluck('id')->all())->toBe([$flag->id]);
});

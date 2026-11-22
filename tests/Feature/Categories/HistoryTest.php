<?php

use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

// Created at 09:05 UTC on Saturday 21 November 2026, which is 09:05 in London
// and 22:05 in Auckland.
beforeEach(function () {
    $this->travelTo('2026-11-21 09:05:00');
    $this->user = User::factory()->create(['timezone' => 'Europe/London']);
    $this->actingAs($this->user);
});

/**
 * A category called Health, made now, for the user.
 *
 * @param  array<string, mixed>  $attributes
 */
function historyCategory(User $user, array $attributes = []): Category
{
    return Category::factory()->for($user)->create(['name' => 'Health', 'colour' => '#22C55E', ...$attributes]);
}

test('guests are sent to the login page from a category\'s history', function () {
    $category = historyCategory($this->user);

    auth()->logout();

    $this->get(route('categories.history', $category))->assertRedirect(route('login'));
});

test('a category belonging to another user has no history to see', function () {
    $category = historyCategory(User::factory()->create());

    $this->get(route('categories.history', $category))->assertNotFound();
});

test('a category that has not been changed has a single entry saying it was created', function () {
    $category = historyCategory($this->user);

    $this->get(route('categories.history', $category))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/History')
            ->where('category', ['id' => $category->id, 'name' => 'Health'])
            ->has('entries', 1)
            ->where('entries.0.event', 'created')
            ->where('entries.0.at', '21 Nov 2026, 09:05')
            ->where('entries.0.changes', []));
});

test('each change is shown newest first, with the fields that changed and their old and new values', function () {
    $category = historyCategory($this->user);

    $this->travelTo('2026-11-21 10:30:00');
    $category->update(['name' => 'Health and fitness']);

    $this->travelTo('2026-11-21 14:05:00');
    $category->update(['name' => 'Wellbeing', 'colour' => '#3b82f6']);

    $this->get(route('categories.history', $category))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 3)
            ->where('entries.0.event', 'updated')
            ->where('entries.0.at', '21 Nov 2026, 14:05')
            ->where('entries.0.changes', [
                ['field' => 'name', 'label' => 'Name', 'old' => 'Health and fitness', 'new' => 'Wellbeing'],
                ['field' => 'colour', 'label' => 'Colour', 'old' => '#22C55E', 'new' => '#3B82F6'],
            ])
            ->where('entries.1.at', '21 Nov 2026, 10:30')
            ->where('entries.1.changes', [
                ['field' => 'name', 'label' => 'Name', 'old' => 'Health', 'new' => 'Health and fitness'],
            ])
            ->where('entries.2.event', 'created'));
});

test('when a change was made is shown in the user\'s timezone', function () {
    $this->user->update(['timezone' => 'Pacific/Auckland']);
    $category = historyCategory($this->user);

    $this->travelTo('2026-11-22 08:00:00');
    $category->update(['name' => 'Wellbeing']);

    $this->get(route('categories.history', $category))
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.at', '22 Nov 2026, 21:00')
            ->where('entries.1.at', '21 Nov 2026, 22:05'));
});

test('a category made before changes were logged still shows when it was created', function () {
    $category = historyCategory($this->user);
    Activity::query()->whereMorphedTo('subject', $category)->delete();

    $this->get(route('categories.history', $category))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 1)
            ->where('entries.0.event', 'created')
            ->where('entries.0.at', '21 Nov 2026, 09:05'));
});

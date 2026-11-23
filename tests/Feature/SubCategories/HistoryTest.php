<?php

use App\Enums\TimeType;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

// Created at 09:05 UTC on Saturday 21 November 2026, which is 09:05 in London
// and 22:05 in Auckland.
beforeEach(function () {
    $this->travelTo('2026-11-21 09:05:00');
    $this->user = User::factory()->create(['timezone' => 'Europe/London']);
    $this->actingAs($this->user);
    $this->category = Category::factory()->for($this->user)->create(['name' => 'Work']);
});

/**
 * A sub-category called Meetings, made now, in the category.
 *
 * @param  array<string, mixed>  $attributes
 */
function historySubCategory(Category $category, array $attributes = []): SubCategory
{
    return SubCategory::factory()->for($category)->create([
        'emoji' => '📞',
        'name' => 'Meetings',
        'default_time_type' => TimeType::Exact,
        ...$attributes,
    ]);
}

test('guests are sent to the login page from a sub-category\'s history', function () {
    $subCategory = historySubCategory($this->category);

    auth()->logout();

    $this->get(route('sub-categories.history', $subCategory))->assertRedirect(route('login'));
});

test('a sub-category belonging to another user has no history to see', function () {
    $subCategory = historySubCategory(Category::factory()->create());

    $this->get(route('sub-categories.history', $subCategory))->assertNotFound();
});

test('a sub-category that has not been changed has a single entry saying it was created', function () {
    $subCategory = historySubCategory($this->category);

    $this->get(route('sub-categories.history', $subCategory))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sub-categories/History')
            ->where('subCategory', ['id' => $subCategory->id, 'emoji' => '📞', 'name' => 'Meetings'])
            ->has('entries', 1)
            ->where('entries.0.event', 'created')
            ->where('entries.0.at', '21 Nov 2026, 09:05')
            ->where('entries.0.changes', []));
});

test('each change is shown newest first, with the fields that changed and the default time type\'s label', function () {
    $subCategory = historySubCategory($this->category);

    $this->travelTo('2026-11-21 10:30:00');
    $subCategory->update(['name' => 'Calls']);

    $this->travelTo('2026-11-21 14:05:00');
    $subCategory->update(['emoji' => '💻', 'default_time_type' => TimeType::Window]);

    $this->get(route('sub-categories.history', $subCategory))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 3)
            ->where('entries.0.event', 'updated')
            ->where('entries.0.at', '21 Nov 2026, 14:05')
            ->where('entries.0.changes', [
                ['field' => 'emoji', 'label' => 'Emoji', 'old' => '📞', 'new' => '💻'],
                ['field' => 'default_time_type', 'label' => 'Default time type', 'old' => 'Exact', 'new' => 'Between'],
            ])
            ->where('entries.1.at', '21 Nov 2026, 10:30')
            ->where('entries.1.changes', [
                ['field' => 'name', 'label' => 'Name', 'old' => 'Meetings', 'new' => 'Calls'],
            ])
            ->where('entries.2.event', 'created'));
});

test('moving a sub-category to another category shows the old and new category names', function () {
    $subCategory = historySubCategory($this->category);
    $personal = Category::factory()->for($this->user)->create(['name' => 'Personal']);

    $this->travelTo('2026-11-21 10:30:00');
    $subCategory->category()->associate($personal)->save();

    $this->get(route('sub-categories.history', $subCategory))
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.changes', [
                ['field' => 'category_id', 'label' => 'Category', 'old' => 'Work', 'new' => 'Personal'],
            ]));
});

test('a deleted category it was moved from shows the name it had when it was deleted', function () {
    $subCategory = historySubCategory($this->category);
    $personal = Category::factory()->for($this->user)->create(['name' => 'Personal']);

    $subCategory->category()->associate($personal)->save();
    $this->category->update(['name' => 'Old work']);
    $this->category->delete();

    $this->get(route('sub-categories.history', $subCategory))
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.changes.0.old', 'Old work')
            ->where('entries.0.changes.0.new', 'Personal'));
});

test('when a change was made is shown in the user\'s timezone', function () {
    $this->user->update(['timezone' => 'Pacific/Auckland']);
    $subCategory = historySubCategory($this->category);

    $this->travelTo('2026-11-22 08:00:00');
    $subCategory->update(['name' => 'Calls']);

    $this->get(route('sub-categories.history', $subCategory))
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.at', '22 Nov 2026, 21:00')
            ->where('entries.1.at', '21 Nov 2026, 22:05'));
});

test('a sub-category made before changes were logged still shows when it was created', function () {
    $subCategory = historySubCategory($this->category);
    Activity::query()->whereMorphedTo('subject', $subCategory)->delete();

    $this->get(route('sub-categories.history', $subCategory))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 1)
            ->where('entries.0.event', 'created')
            ->where('entries.0.at', '21 Nov 2026, 09:05'));
});

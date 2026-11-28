<?php

use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are sent to the login page from insights', function () {
    $this->get(route('insights.index'))->assertRedirect(route('login'));
});

test('a user with nothing yet has no firsts and no categories', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('insights.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('insights/Index')
            ->where('firsts', ['category' => null, 'subCategory' => null, 'calendarItem' => null])
            ->where('categories', []));
});

test('the first category, sub-category and calendar item are the oldest ones', function () {
    $user = User::factory()->create(['timezone' => 'Europe/London']);

    $this->travelTo('2026-03-02 10:00:00');
    $health = Category::factory()->for($user)->create(['name' => 'Health', 'colour' => '#22C55E']);

    $this->travelTo('2026-04-15 10:00:00');
    $work = Category::factory()->for($user)->create(['name' => 'Work', 'colour' => '#3B82F6']);
    SubCategory::factory()->for($health)->create(['emoji' => '🦷', 'name' => 'Dentist']);

    $this->travelTo('2026-04-10 10:00:00');
    SubCategory::factory()->for($work)->create(['emoji' => '📅', 'name' => 'Meetings']);

    $this->travelTo('2026-06-20 10:00:00');
    CalendarItem::factory()->for($user)->create(['title' => 'Team lunch']);

    $this->travelTo('2026-05-01 10:00:00');
    CalendarItem::factory()->for($user)->create(['title' => 'Check-up']);

    $this->travelBack();

    $this->actingAs($user)
        ->get(route('insights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('firsts', [
                'category' => ['name' => 'Health', 'colour' => '#22C55E', 'created' => '2 Mar 2026'],
                'subCategory' => ['emoji' => '📅', 'name' => 'Meetings', 'categoryName' => 'Work', 'created' => '10 Apr 2026'],
                'calendarItem' => ['title' => 'Check-up', 'created' => '1 May 2026'],
            ]));
});

test('categories and their sub-categories are listed oldest first with when each was created', function () {
    $user = User::factory()->create(['timezone' => 'Europe/London']);

    $this->travelTo('2026-02-01 09:00:00');
    $work = Category::factory()->for($user)->create(['name' => 'Work', 'colour' => '#3B82F6']);

    $this->travelTo('2026-01-10 09:00:00');
    $family = Category::factory()->for($user)->create(['name' => 'Family', 'colour' => '#EF4444']);

    $this->travelTo('2026-03-05 09:00:00');
    $meetings = SubCategory::factory()->for($work)->create(['emoji' => '📅', 'name' => 'Meetings']);

    $this->travelTo('2026-02-20 09:00:00');
    $admin = SubCategory::factory()->for($work)->create(['emoji' => '🗂️', 'name' => 'Admin']);

    $this->travelBack();

    $this->actingAs($user)
        ->get(route('insights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories', [
                [
                    'id' => $family->id,
                    'name' => 'Family',
                    'colour' => '#EF4444',
                    'created' => '10 Jan 2026',
                    'firstCalendarItem' => null,
                    'subCategories' => [],
                ],
                [
                    'id' => $work->id,
                    'name' => 'Work',
                    'colour' => '#3B82F6',
                    'created' => '1 Feb 2026',
                    'firstCalendarItem' => null,
                    'subCategories' => [
                        ['id' => $admin->id, 'emoji' => '🗂️', 'name' => 'Admin', 'created' => '20 Feb 2026', 'firstCalendarItem' => null],
                        ['id' => $meetings->id, 'emoji' => '📅', 'name' => 'Meetings', 'created' => '5 Mar 2026', 'firstCalendarItem' => null],
                    ],
                ],
            ]));
});

test('things created at the same time are listed in the order they were added', function () {
    $user = User::factory()->create();

    $this->travelTo('2026-02-01 09:00:00');
    $second = Category::factory()->for($user)->create(['name' => 'B']);
    $first = Category::factory()->for($user)->create(['name' => 'A']);

    $this->actingAs($user)
        ->get(route('insights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories.0.id', $second->id)
            ->where('categories.1.id', $first->id)
            ->where('firsts.category.name', 'B'));
});

test('created dates are the day in the user\'s timezone', function () {
    // 22:30 UTC on 21 November 2026 is already 22 November in Auckland.
    $this->travelTo('2026-11-21 22:30:00');
    $user = User::factory()->create(['timezone' => 'Pacific/Auckland']);
    $category = Category::factory()->for($user)->create();
    SubCategory::factory()->for($category)->create();
    CalendarItem::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('insights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('firsts.category.created', '22 Nov 2026')
            ->where('firsts.subCategory.created', '22 Nov 2026')
            ->where('firsts.calendarItem.created', '22 Nov 2026')
            ->where('categories.0.created', '22 Nov 2026')
            ->where('categories.0.subCategories.0.created', '22 Nov 2026'));
});

test('other users\' categories, sub-categories and calendar items are left out', function () {
    $user = User::factory()->create();

    $this->travelTo('2025-01-01 09:00:00');
    $theirs = Category::factory()->create(['name' => 'Theirs']);
    SubCategory::factory()->for($theirs)->create(['name' => 'Their sub-category']);
    CalendarItem::factory()->create(['title' => 'Their item']);

    $this->travelTo('2026-01-01 09:00:00');
    $mine = Category::factory()->for($user)->create(['name' => 'Mine']);
    SubCategory::factory()->for($mine)->create(['name' => 'My sub-category']);
    CalendarItem::factory()->for($user)->create(['title' => 'My item']);

    $this->actingAs($user)
        ->get(route('insights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 1)
            ->where('categories.0.name', 'Mine')
            ->where('firsts.category.name', 'Mine')
            ->where('firsts.subCategory.name', 'My sub-category')
            ->where('firsts.calendarItem.title', 'My item'));
});

test('an uncategorised calendar item can be the first calendar item', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->create(['title' => 'Loose end', 'sub_category_id' => null]);

    $this->actingAs($user)
        ->get(route('insights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('firsts.calendarItem.title', 'Loose end')
            ->where('firsts.category', null)
            ->where('firsts.subCategory', null));
});

test('each category and sub-category shows the first calendar item created in it', function () {
    $user = User::factory()->create(['timezone' => 'Europe/London']);

    $this->travelTo('2026-01-01 09:00:00');
    $work = Category::factory()->for($user)->create(['name' => 'Work']);
    $meetings = SubCategory::factory()->for($work)->create(['name' => 'Meetings']);
    $admin = SubCategory::factory()->for($work)->create(['name' => 'Admin']);
    SubCategory::factory()->for($work)->create(['name' => 'Deep work']);

    $this->travelTo('2026-03-10 09:00:00');
    CalendarItem::factory()->for($user)->for($meetings)->create(['title' => 'Later meeting']);

    $this->travelTo('2026-02-14 09:00:00');
    CalendarItem::factory()->for($user)->for($meetings)->create(['title' => 'First meeting']);

    $this->travelTo('2026-02-01 09:00:00');
    CalendarItem::factory()->for($user)->for($admin)->create(['title' => 'Expenses']);

    $this->travelBack();

    $this->actingAs($user)
        ->get(route('insights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories.0.firstCalendarItem', ['title' => 'Expenses', 'created' => '1 Feb 2026'])
            ->where('categories.0.subCategories.0.firstCalendarItem', ['title' => 'First meeting', 'created' => '14 Feb 2026'])
            ->where('categories.0.subCategories.1.firstCalendarItem', ['title' => 'Expenses', 'created' => '1 Feb 2026'])
            ->where('categories.0.subCategories.2.firstCalendarItem', null));
});

test('a calendar item moved into a sub-category counts as its first by when it was created', function () {
    $user = User::factory()->create(['timezone' => 'Europe/London']);
    $category = Category::factory()->for($user)->create();
    $subCategory = SubCategory::factory()->for($category)->create();

    $this->travelTo('2026-01-05 09:00:00');
    $moved = CalendarItem::factory()->for($user)->create(['title' => 'Moved in later', 'sub_category_id' => null]);

    $this->travelTo('2026-02-05 09:00:00');
    CalendarItem::factory()->for($user)->for($subCategory)->create(['title' => 'Added straight in']);

    $this->travelTo('2026-03-05 09:00:00');
    $moved->update(['sub_category_id' => $subCategory->id]);

    $this->actingAs($user)
        ->get(route('insights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories.0.subCategories.0.firstCalendarItem', ['title' => 'Moved in later', 'created' => '5 Jan 2026']));
});

test('only the user\'s own calendar items count towards a category\'s first', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $subCategory = SubCategory::factory()->for($category)->create();

    // Not possible through the app, but the query should still only look at
    // the user's own calendar items.
    CalendarItem::factory()->for($subCategory)->create(['title' => 'Someone else\'s']);

    $this->actingAs($user)
        ->get(route('insights.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories.0.firstCalendarItem', null)
            ->where('categories.0.subCategories.0.firstCalendarItem', null));
});

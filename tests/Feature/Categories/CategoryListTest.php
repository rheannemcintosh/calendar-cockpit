<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are sent to the login page from the categories list', function () {
    $this->get(route('categories.index'))->assertRedirect(route('login'));
});

test('a user with no categories gets an empty list', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/Index')
            ->where('categories', []));
});

test('categories and their sub-categories are listed in alphabetical order', function () {
    $user = User::factory()->create();
    $work = Category::factory()->for($user)->create(['name' => 'Work', 'colour' => '#3B82F6']);
    $family = Category::factory()->for($user)->create(['name' => 'family', 'colour' => '#EF4444']);
    $meetings = SubCategory::factory()->for($work)->create(['emoji' => '📅', 'name' => 'Meetings']);
    $admin = SubCategory::factory()->for($work)->create(['emoji' => '🗂️', 'name' => 'admin']);
    $deepWork = SubCategory::factory()->for($work)->create(['emoji' => '💻', 'name' => 'Deep work']);
    $birthdays = SubCategory::factory()->for($family)->create(['emoji' => '🎂', 'name' => 'Birthdays']);

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/Index')
            ->where('categories', [
                [
                    'id' => $family->id,
                    'name' => 'family',
                    'colour' => '#EF4444',
                    'subCategories' => [
                        ['id' => $birthdays->id, 'emoji' => '🎂', 'name' => 'Birthdays'],
                    ],
                ],
                [
                    'id' => $work->id,
                    'name' => 'Work',
                    'colour' => '#3B82F6',
                    'subCategories' => [
                        ['id' => $admin->id, 'emoji' => '🗂️', 'name' => 'admin'],
                        ['id' => $deepWork->id, 'emoji' => '💻', 'name' => 'Deep work'],
                        ['id' => $meetings->id, 'emoji' => '📅', 'name' => 'Meetings'],
                    ],
                ],
            ]));
});

test('a category with no sub-categories is listed with none', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 1)
            ->where('categories.0.id', $category->id)
            ->where('categories.0.subCategories', []));
});

test('other users\' categories and sub-categories are not listed', function () {
    $user = User::factory()->create();
    $mine = Category::factory()->for($user)->create(['name' => 'Work']);
    SubCategory::factory()->for($mine)->create(['name' => 'Meetings']);
    $theirs = Category::factory()->create(['name' => 'Admin']);
    SubCategory::factory()->for($theirs)->create(['name' => 'Filing']);

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 1)
            ->where('categories.0.id', $mine->id)
            ->has('categories.0.subCategories', 1)
            ->where('categories.0.subCategories.0.name', 'Meetings'));
});

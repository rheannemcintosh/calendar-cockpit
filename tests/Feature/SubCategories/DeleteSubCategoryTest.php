<?php

use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot delete a sub-category', function () {
    $subCategory = SubCategory::factory()->create();

    $this->delete(route('sub-categories.destroy', $subCategory))->assertRedirect(route('login'));

    expect($subCategory->fresh())->not->toBeNull();
});

test('a user can delete a sub-category and goes back to the categories list', function () {
    $user = User::factory()->create();
    $subCategory = SubCategory::factory()->for(Category::factory()->for($user))->create();

    $this->actingAs($user)
        ->delete(route('sub-categories.destroy', $subCategory))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Sub-category deleted.'])
        ->assertRedirect(route('categories.index'));

    expect($subCategory->fresh())->toBeNull();
});

test('deleting a sub-category keeps its calendar items, without a sub-category or category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $meetings = SubCategory::factory()->for($category)->create();
    $admin = SubCategory::factory()->for($category)->create();
    $meeting = CalendarItem::factory()->for($user)->for($meetings)->create();
    $filing = CalendarItem::factory()->for($user)->for($admin)->create();

    $this->actingAs($user)->delete(route('sub-categories.destroy', $meetings));

    expect($meeting->fresh())->not->toBeNull()
        ->and($meeting->fresh()->subCategory)->toBeNull()
        ->and($filing->fresh()->sub_category_id)->toBe($admin->id)
        ->and($category->fresh())->not->toBeNull();
});

test('deleting the last sub-category in a category leaves the category with none', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $subCategory = SubCategory::factory()->for($category)->create();

    $this->actingAs($user)->delete(route('sub-categories.destroy', $subCategory));

    $this->get(route('categories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 1)
            ->where('categories.0.id', $category->id)
            ->where('categories.0.subCategories', []));
});

test('a user gets a not found page for another user\'s sub-category and nothing is deleted', function () {
    $subCategory = SubCategory::factory()->create();
    $calendarItem = CalendarItem::factory()->for($subCategory->category->user)->for($subCategory)->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('sub-categories.destroy', $subCategory))
        ->assertNotFound();

    expect($subCategory->fresh())->not->toBeNull()
        ->and($calendarItem->fresh()->sub_category_id)->toBe($subCategory->id);
});

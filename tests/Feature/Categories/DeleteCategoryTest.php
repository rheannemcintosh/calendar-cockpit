<?php

use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot delete a category', function () {
    $category = Category::factory()->create();

    $this->delete(route('categories.destroy', $category))->assertRedirect(route('login'));

    expect($category->fresh())->not->toBeNull();
});

test('a user can delete a category and goes back to the categories list', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('categories.destroy', $category))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Category deleted.'])
        ->assertRedirect(route('categories.index'));

    expect($category->fresh())->toBeNull();
});

test('deleting a category deletes its sub-categories and keeps their calendar items, uncategorised', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    [$meetings, $admin] = SubCategory::factory()->count(2)->for($category)->create();
    $meeting = CalendarItem::factory()->for($user)->for($meetings)->create();
    $filing = CalendarItem::factory()->for($user)->for($admin)->create();

    $family = SubCategory::factory()->for(Category::factory()->for($user))->create();
    $birthday = CalendarItem::factory()->for($user)->for($family)->create();

    $this->actingAs($user)->delete(route('categories.destroy', $category));

    expect(SubCategory::whereKey([$meetings->id, $admin->id])->exists())->toBeFalse()
        ->and($meeting->fresh()->sub_category_id)->toBeNull()
        ->and($filing->fresh()->sub_category_id)->toBeNull()
        ->and($birthday->fresh()->sub_category_id)->toBe($family->id);
});

test('a deleted category is no longer listed', function () {
    $user = User::factory()->create();
    $work = Category::factory()->for($user)->create(['name' => 'Work']);
    $family = Category::factory()->for($user)->create(['name' => 'Family']);

    $this->actingAs($user)->delete(route('categories.destroy', $work));

    $this->get(route('categories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 1)
            ->where('categories.0.id', $family->id));
});

test('a user gets a not found page for another user\'s category and nothing is deleted', function () {
    $category = Category::factory()->create();
    $subCategory = SubCategory::factory()->for($category)->create();
    $calendarItem = CalendarItem::factory()->for($category->user)->for($subCategory)->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('categories.destroy', $category))
        ->assertNotFound();

    expect($category->fresh())->not->toBeNull()
        ->and($subCategory->fresh())->not->toBeNull()
        ->and($calendarItem->fresh()->sub_category_id)->toBe($subCategory->id);
});

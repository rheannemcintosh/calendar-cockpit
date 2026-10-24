<?php

use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

test('the categories and sub-categories tables have the expected columns', function () {
    expect(Schema::hasColumns('categories', ['id', 'user_id', 'name', 'colour', 'created_at', 'updated_at']))->toBeTrue()
        ->and(Schema::hasColumns('sub_categories', ['id', 'category_id', 'emoji', 'name', 'created_at', 'updated_at']))->toBeTrue()
        ->and(Schema::hasColumn('calendar_items', 'sub_category_id'))->toBeTrue()
        ->and(Schema::hasColumn('calendar_items', 'category_id'))->toBeFalse();
});

test('a category is saved and read back with its name and colour, in upper case', function () {
    $user = User::factory()->create();

    $category = $user->categories()->create(['name' => 'Work', 'colour' => '#3b82f6']);

    $fresh = $category->fresh();

    expect($fresh->name)->toBe('Work')
        ->and($fresh->colour)->toBe('#3B82F6');
});

test('a sub-category is saved and read back with its emoji and name intact', function (string $emoji) {
    $category = Category::factory()->create();

    $subCategory = $category->subCategories()->create(['emoji' => $emoji, 'name' => 'Deep work']);

    $fresh = $subCategory->fresh();

    expect($fresh->emoji)->toBe($emoji)
        ->and($fresh->name)->toBe('Deep work');
})->with([
    'single code point' => '💻',
    'variation selector' => '🍽️',
    'skin tone' => '👋🏽',
    'ZWJ sequence' => '👩‍💻',
    'flag ZWJ sequence' => '🏳️‍🌈',
]);

test('a user has many categories and each category belongs to its user', function () {
    $user = User::factory()->create();
    $categories = Category::factory()->count(2)->for($user)->create();
    Category::factory()->create();

    expect($user->categories)->toHaveCount(2)
        ->and($user->categories->pluck('id')->all())->toEqualCanonicalizing($categories->pluck('id')->all())
        ->and($categories->first()->user->is($user))->toBeTrue();
});

test('a category has many sub-categories and each sub-category belongs to its category', function () {
    $category = Category::factory()->create();
    $subCategories = SubCategory::factory()->count(2)->for($category)->create();
    SubCategory::factory()->create();

    expect($category->subCategories)->toHaveCount(2)
        ->and($category->subCategories->pluck('id')->all())->toEqualCanonicalizing($subCategories->pluck('id')->all())
        ->and($subCategories->first()->category->is($category))->toBeTrue();
});

test('a user cannot have two categories with the same name', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Work']);

    Category::factory()->for($user)->create(['name' => 'Work']);
})->throws(QueryException::class);

test('two users can each have a category with the same name', function () {
    Category::factory()->create(['name' => 'Work']);
    Category::factory()->create(['name' => 'Work']);

    expect(Category::where('name', 'Work')->count())->toBe(2);
});

test('a category cannot have two sub-categories with the same name', function () {
    $category = Category::factory()->create();
    SubCategory::factory()->for($category)->create(['name' => 'Admin']);

    SubCategory::factory()->for($category)->create(['name' => 'Admin']);
})->throws(QueryException::class);

test('a calendar item can be saved without a sub-category', function () {
    $calendarItem = CalendarItem::factory()->create();

    expect($calendarItem->fresh()->sub_category_id)->toBeNull()
        ->and($calendarItem->fresh()->subCategory)->toBeNull();
});

test('a calendar item with a sub-category returns it and, through it, its category', function () {
    $subCategory = SubCategory::factory()->create();
    $calendarItem = CalendarItem::factory()->for($subCategory->category->user)->create();

    $calendarItem->subCategory()->associate($subCategory)->save();

    $fresh = $calendarItem->fresh();

    expect($fresh->subCategory->is($subCategory))->toBeTrue()
        ->and($fresh->subCategory->category->is($subCategory->category))->toBeTrue()
        ->and($subCategory->calendarItems->pluck('id')->all())->toBe([$calendarItem->id]);
});

test('deleting a user also deletes their calendar items, categories and sub-categories', function () {
    $user = User::factory()->create();
    $subCategory = SubCategory::factory()->for(Category::factory()->for($user))->create();
    CalendarItem::factory()->for($user)->for($subCategory)->create();
    CalendarItem::factory()->for($user)->create();

    $otherSubCategory = SubCategory::factory()->create();
    $otherCalendarItem = CalendarItem::factory()->for($otherSubCategory->category->user)->for($otherSubCategory)->create();

    $user->delete();

    expect(CalendarItem::pluck('id')->all())->toBe([$otherCalendarItem->id])
        ->and(Category::pluck('id')->all())->toBe([$otherSubCategory->category_id])
        ->and(SubCategory::pluck('id')->all())->toBe([$otherSubCategory->id]);
});

test('deleting a category deletes its sub-categories and leaves their calendar items uncategorised', function () {
    $category = Category::factory()->create();
    [$first, $second] = SubCategory::factory()->count(2)->for($category)->create();
    $firstItem = CalendarItem::factory()->for($category->user)->for($first)->create();
    $secondItem = CalendarItem::factory()->for($category->user)->for($second)->create();

    $otherSubCategory = SubCategory::factory()->for(Category::factory()->for($category->user))->create();
    $otherItem = CalendarItem::factory()->for($category->user)->for($otherSubCategory)->create();

    $category->delete();

    expect(Category::find($category->id))->toBeNull()
        ->and(SubCategory::whereIn('id', [$first->id, $second->id])->exists())->toBeFalse()
        ->and($firstItem->fresh()->sub_category_id)->toBeNull()
        ->and($secondItem->fresh()->sub_category_id)->toBeNull()
        ->and($otherItem->fresh()->sub_category_id)->toBe($otherSubCategory->id);
});

test('deleting a sub-category leaves its calendar items uncategorised', function () {
    $subCategory = SubCategory::factory()->create();
    $calendarItem = CalendarItem::factory()->for($subCategory->category->user)->for($subCategory)->create();

    $sibling = SubCategory::factory()->for($subCategory->category)->create();
    $siblingItem = CalendarItem::factory()->for($subCategory->category->user)->for($sibling)->create();

    $subCategory->delete();

    expect(SubCategory::find($subCategory->id))->toBeNull()
        ->and($calendarItem->fresh())->not->toBeNull()
        ->and($calendarItem->fresh()->sub_category_id)->toBeNull()
        ->and($siblingItem->fresh()->sub_category_id)->toBe($sibling->id);
});

test('the factories make valid categories and sub-categories', function () {
    $subCategory = SubCategory::factory()->create();

    expect($subCategory->category->colour)->toMatch('/^#[0-9A-F]{6}$/')
        ->and(mb_strlen($subCategory->category->name))->toBeLessThanOrEqual(50)
        ->and(mb_strlen($subCategory->name))->toBeLessThanOrEqual(50)
        ->and($subCategory->emoji)->not->toBe('');
});

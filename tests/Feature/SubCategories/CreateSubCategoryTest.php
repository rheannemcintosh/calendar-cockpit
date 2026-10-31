<?php

use App\Enums\TimeType;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A valid sub-category to send for the given category.
 *
 * @return array{category_id: int, emoji: string, name: string, default_time_type: string}
 */
function validSubCategory(Category $category, array $overrides = []): array
{
    return [
        'category_id' => $category->id,
        'emoji' => '💻',
        'name' => 'Deep work',
        'default_time_type' => 'exact',
        ...$overrides,
    ];
}

test('guests are sent to the login page from the create sub-category form', function () {
    $this->get(route('sub-categories.create'))->assertRedirect(route('login'));
});

test('guests cannot create a sub-category', function () {
    $category = Category::factory()->create();

    $this->post(route('sub-categories.store'), validSubCategory($category))
        ->assertRedirect(route('login'));

    expect(SubCategory::count())->toBe(0);
});

test('the form lists only the user\'s own categories, in alphabetical order, with their colours', function () {
    $user = User::factory()->create();
    $work = Category::factory()->for($user)->create(['name' => 'Work', 'colour' => '#3B82F6']);
    $family = Category::factory()->for($user)->create(['name' => 'family', 'colour' => '#EF4444']);
    $health = Category::factory()->for($user)->create(['name' => 'Health', 'colour' => '#22C55E']);
    Category::factory()->create(['name' => 'Admin']);

    $this->actingAs($user)
        ->get(route('sub-categories.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sub-categories/Create')
            ->where('categories', [
                ['id' => $family->id, 'name' => 'family', 'colour' => '#EF4444'],
                ['id' => $health->id, 'name' => 'Health', 'colour' => '#22C55E'],
                ['id' => $work->id, 'name' => 'Work', 'colour' => '#3B82F6'],
            ])
            ->where('categoryId', null));
});

test('a user with no categories sees the form with no categories to choose from', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('sub-categories.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sub-categories/Create')
            ->where('categories', []));
});

test('the category in the address is already chosen', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('sub-categories.create', ['category' => $category->id]))
        ->assertInertia(fn (Assert $page) => $page->where('categoryId', $category->id));
});

test('a category in the address that is not the user\'s is not chosen', function (mixed $category) {
    $user = User::factory()->create();
    Category::factory()->for($user)->create();
    $category = $category === 'someone else\'s' ? Category::factory()->create()->id : $category;

    $this->actingAs($user)
        ->get(route('sub-categories.create', ['category' => $category]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('categoryId', null));
})->with([
    'someone else\'s' => 'someone else\'s',
    'one that does not exist' => 999,
    'not a number' => 'work',
]);

test('a user can create a sub-category and goes back to the categories list', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('sub-categories.store'), validSubCategory($category))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('categories.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Sub-category created.']);

    $subCategory = SubCategory::sole();

    expect($subCategory->category_id)->toBe($category->id)
        ->and($subCategory->emoji)->toBe('💻')
        ->and($subCategory->name)->toBe('Deep work');
});

test('a sub-category needs a category, an emoji, a name and a default time type', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('sub-categories.store'), ['category_id' => '', 'emoji' => '', 'name' => '', 'default_time_type' => ''])
        ->assertSessionHasErrors([
            'category_id' => 'Please choose a category.',
            'emoji' => 'Please choose an emoji.',
            'name' => 'Please enter a name.',
            'default_time_type' => 'Please choose a default time type.',
        ]);

    expect(SubCategory::count())->toBe(0);
});

test('a sub-category name cannot be longer than 50 characters', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('sub-categories.store'), validSubCategory($category, ['name' => str_repeat('a', 51)]))
        ->assertSessionHasErrors(['name' => 'The name cannot be longer than 50 characters.']);

    expect(SubCategory::count())->toBe(0);
});

test('a sub-category name can be 50 characters long', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('sub-categories.store'), validSubCategory($category, ['name' => str_repeat('a', 50)]))
        ->assertSessionHasNoErrors();

    expect(SubCategory::count())->toBe(1);
});

test('the emoji must be exactly one emoji', function (string $emoji) {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('sub-categories.store'), validSubCategory($category, ['emoji' => $emoji]))
        ->assertSessionHasErrors(['emoji' => 'Please enter a single emoji.']);

    expect(SubCategory::count())->toBe(0);
})->with([
    'plain text' => 'ab',
    'a single letter' => 'a',
    'a digit' => '1',
    'two emoji' => '🏃🚴',
    'an emoji and text' => '💻a',
    'an emoji with many marks on it' => '💻'.str_repeat("\u{0301}", 20),
]);

test('an emoji with a skin tone, a flag or a combined emoji counts as one', function (string $emoji) {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('sub-categories.store'), validSubCategory($category, ['emoji' => $emoji]))
        ->assertSessionHasNoErrors();

    expect(SubCategory::sole()->emoji)->toBe($emoji);
})->with([
    'a simple emoji' => '🏃',
    'an emoji with a variation selector' => '🍽️',
    'a skin tone' => '👋🏽',
    'a flag' => '🇬🇧',
    'a combined emoji' => '👩‍💻',
    'a combined flag' => '🏳️‍🌈',
    'a family' => '👨‍👩‍👧‍👦',
    'a keycap' => '1️⃣',
]);

test('a category cannot have two sub-categories with the same name', function (string $name) {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    SubCategory::factory()->for($category)->create(['name' => 'Admin']);

    $this->actingAs($user)
        ->post(route('sub-categories.store'), validSubCategory($category, ['name' => $name]))
        ->assertSessionHasErrors(['name' => 'This category already has a sub-category with that name.']);

    expect($category->subCategories()->count())->toBe(1);
})->with([
    'the same' => 'Admin',
    'lower case' => 'admin',
    'upper case' => 'ADMIN',
    'spaces at either end' => '  admin ',
]);

test('the name is saved without spaces at either end', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('sub-categories.store'), validSubCategory($category, ['name' => '  Admin ']))
        ->assertSessionHasNoErrors();

    expect(SubCategory::sole()->name)->toBe('Admin');
});

test('two categories can each have a sub-category with the same name', function () {
    $user = User::factory()->create();
    $work = Category::factory()->for($user)->create(['name' => 'Work']);
    $home = Category::factory()->for($user)->create(['name' => 'Home']);
    SubCategory::factory()->for($work)->create(['name' => 'Admin']);

    $this->actingAs($user)
        ->post(route('sub-categories.store'), validSubCategory($home, ['name' => 'Admin']))
        ->assertSessionHasNoErrors();

    expect($home->subCategories()->count())->toBe(1);
});

test('two sub-categories can share an emoji', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    SubCategory::factory()->for($category)->create(['name' => 'Meetings', 'emoji' => '💻']);

    $this->actingAs($user)
        ->post(route('sub-categories.store'), validSubCategory($category))
        ->assertSessionHasNoErrors();

    expect($category->subCategories()->count())->toBe(2);
});

test('a sub-category cannot be added to a category that is not the user\'s', function (mixed $categoryId) {
    $user = User::factory()->create();
    $someoneElses = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('sub-categories.store'), [
            ...validSubCategory($someoneElses),
            'category_id' => $categoryId === 'someone else\'s' ? $someoneElses->id : $categoryId,
        ])
        ->assertSessionHasErrors(['category_id' => 'Please choose a category again.']);

    expect(SubCategory::count())->toBe(0);
})->with([
    'someone else\'s' => 'someone else\'s',
    'one that does not exist' => 999,
    'not a number' => 'work',
]);

test('the form offers every time type with its label and hint', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('sub-categories.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('timeTypes', TimeType::options())
            ->where('timeTypes.1', [
                'value' => 'window',
                'label' => 'Window',
                'hint' => 'A booked slot. It could happen any time in it.',
            ]));
});

test('a sub-category is saved with the default time type chosen', function (TimeType $timeType) {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('sub-categories.store'), validSubCategory($category, ['default_time_type' => $timeType->value]))
        ->assertSessionHasNoErrors();

    expect(SubCategory::sole()->default_time_type)->toBe($timeType);
})->with(TimeType::cases());

test('a sub-category cannot have a default time type that is not one of the four', function (mixed $timeType) {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('sub-categories.store'), validSubCategory($category, ['default_time_type' => $timeType]))
        ->assertSessionHasErrors(['default_time_type' => 'Please choose a default time type again.']);

    expect(SubCategory::count())->toBe(0);
})->with([
    'unknown' => 'sometime',
    'upper case' => 'EXACT',
    'a list' => [['exact']],
]);

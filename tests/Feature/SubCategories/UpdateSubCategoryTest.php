<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

const UPDATED_SUB_CATEGORY = [
    'emoji' => '📅',
    'name' => 'Meetings',
];

/**
 * A sub-category called Deep work in one of the given user's categories.
 */
function usersSubCategory(User $user): SubCategory
{
    return SubCategory::factory()
        ->for(Category::factory()->for($user)->create(['name' => 'Work', 'colour' => '#3B82F6']))
        ->create(['emoji' => '💻', 'name' => 'Deep work']);
}

test('guests are sent to the login page from the edit sub-category form', function () {
    $subCategory = SubCategory::factory()->create();

    $this->get(route('sub-categories.edit', $subCategory))->assertRedirect(route('login'));
});

test('guests cannot change a sub-category', function () {
    $subCategory = SubCategory::factory()->create(['name' => 'Deep work']);

    $this->put(route('sub-categories.update', $subCategory), UPDATED_SUB_CATEGORY)
        ->assertRedirect(route('login'));

    expect($subCategory->fresh()->name)->toBe('Deep work');
});

test('the edit form is filled in with the sub-category\'s current emoji and name and shows its category', function () {
    $user = User::factory()->create();
    $subCategory = usersSubCategory($user);

    $this->actingAs($user)
        ->get(route('sub-categories.edit', $subCategory))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sub-categories/Edit')
            ->where('subCategory', ['id' => $subCategory->id, 'emoji' => '💻', 'name' => 'Deep work'])
            ->where('category', ['id' => $subCategory->category_id, 'name' => 'Work', 'colour' => '#3B82F6']));
});

test('a user can change a sub-category\'s emoji and name and goes back to the categories list', function () {
    $user = User::factory()->create();
    $subCategory = usersSubCategory($user);

    $this->actingAs($user)
        ->put(route('sub-categories.update', $subCategory), UPDATED_SUB_CATEGORY)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Sub-category updated.'])
        ->assertRedirect(route('categories.index'));

    expect($subCategory->fresh())
        ->emoji->toBe('📅')
        ->name->toBe('Meetings');
});

test('a sub-category cannot be moved to another category', function () {
    $user = User::factory()->create();
    $subCategory = usersSubCategory($user);
    $family = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('sub-categories.update', $subCategory), [...UPDATED_SUB_CATEGORY, 'category_id' => $family->id])
        ->assertSessionHasNoErrors();

    expect($subCategory->fresh()->category_id)->toBe($subCategory->category_id);
});

test('a sub-category can keep its own name, in any case, while its emoji changes', function (string $name) {
    $user = User::factory()->create();
    $subCategory = usersSubCategory($user);

    $this->actingAs($user)
        ->put(route('sub-categories.update', $subCategory), ['emoji' => '📅', 'name' => $name])
        ->assertSessionHasNoErrors();

    expect($subCategory->fresh())
        ->emoji->toBe('📅')
        ->name->toBe(trim($name));
})->with([
    'the same' => 'Deep work',
    'lower case' => 'deep work',
    'spaces at either end' => '  Deep work ',
]);

test('a sub-category cannot be renamed to the name of another sub-category in its category', function (string $name) {
    $user = User::factory()->create();
    $subCategory = usersSubCategory($user);
    SubCategory::factory()->for($subCategory->category)->create(['name' => 'Meetings']);

    $this->actingAs($user)
        ->put(route('sub-categories.update', $subCategory), [...UPDATED_SUB_CATEGORY, 'name' => $name])
        ->assertSessionHasErrors(['name' => 'This category already has a sub-category with that name.']);

    expect($subCategory->fresh()->name)->toBe('Deep work');
})->with([
    'the same' => 'Meetings',
    'upper case' => 'MEETINGS',
]);

test('a sub-category can be renamed to the name of a sub-category in another category', function () {
    $user = User::factory()->create();
    $subCategory = usersSubCategory($user);
    SubCategory::factory()->for(Category::factory()->for($user))->create(['name' => 'Meetings']);

    $this->actingAs($user)
        ->put(route('sub-categories.update', $subCategory), UPDATED_SUB_CATEGORY)
        ->assertSessionHasNoErrors();

    expect($subCategory->fresh()->name)->toBe('Meetings');
});

test('the same checks apply as when creating a sub-category', function (array $data, array $errors) {
    $user = User::factory()->create();
    $subCategory = usersSubCategory($user);

    $this->actingAs($user)
        ->put(route('sub-categories.update', $subCategory), $data)
        ->assertSessionHasErrors($errors);

    expect($subCategory->fresh())
        ->emoji->toBe('💻')
        ->name->toBe('Deep work');
})->with([
    'no emoji or name' => [
        ['emoji' => '', 'name' => ''],
        ['emoji' => 'Please choose an emoji.', 'name' => 'Please enter a name.'],
    ],
    'two emoji' => [
        ['emoji' => '📅💻', 'name' => 'Meetings'],
        ['emoji' => 'Please enter a single emoji.'],
    ],
    'letters instead of an emoji' => [
        ['emoji' => 'a', 'name' => 'Meetings'],
        ['emoji' => 'Please enter a single emoji.'],
    ],
    'a name that is too long' => [
        ['emoji' => '📅', 'name' => str_repeat('a', 51)],
        ['name' => 'The name cannot be longer than 50 characters.'],
    ],
]);

test('the create calendar item form shows the sub-category\'s new emoji and name', function () {
    $user = User::factory()->create();
    $subCategory = usersSubCategory($user);

    $this->actingAs($user)
        ->put(route('sub-categories.update', $subCategory), UPDATED_SUB_CATEGORY)
        ->assertSessionHasNoErrors();

    $this->get(route('calendar-items.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories.0.subCategories.0', ['id' => $subCategory->id, 'emoji' => '📅', 'name' => 'Meetings']));
});

test('a user gets a not found page for another user\'s sub-category and nothing is saved', function () {
    $subCategory = usersSubCategory(User::factory()->create());

    $this->actingAs(User::factory()->create());

    $this->get(route('sub-categories.edit', $subCategory))->assertNotFound();
    $this->put(route('sub-categories.update', $subCategory), UPDATED_SUB_CATEGORY)->assertNotFound();

    expect($subCategory->fresh()->name)->toBe('Deep work');
});

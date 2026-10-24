<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

const UPDATED_CATEGORY = [
    'name' => 'Family',
    'colour' => '#EF4444',
];

test('guests are sent to the login page from the edit category form', function () {
    $category = Category::factory()->create();

    $this->get(route('categories.edit', $category))->assertRedirect(route('login'));
});

test('guests cannot change a category', function () {
    $category = Category::factory()->create(['name' => 'Work']);

    $this->put(route('categories.update', $category), UPDATED_CATEGORY)
        ->assertRedirect(route('login'));

    expect($category->fresh()->name)->toBe('Work');
});

test('the edit form is filled in with the category\'s current name and colour', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Work', 'colour' => '#3B82F6']);

    $this->actingAs($user)
        ->get(route('categories.edit', $category))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/Edit')
            ->where('category', ['id' => $category->id, 'name' => 'Work', 'colour' => '#3B82F6']));
});

test('a user can change a category\'s name and colour and goes back to the categories list', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Work', 'colour' => '#3B82F6']);

    $this->actingAs($user)
        ->put(route('categories.update', $category), UPDATED_CATEGORY)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Category updated.'])
        ->assertRedirect(route('categories.index'));

    expect($category->fresh())
        ->name->toBe('Family')
        ->colour->toBe('#EF4444')
        ->user_id->toBe($user->id);
});

test('a category can keep its own name, in any case, while its colour changes', function (string $name) {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Work', 'colour' => '#3B82F6']);

    $this->actingAs($user)
        ->put(route('categories.update', $category), ['name' => $name, 'colour' => '#EF4444'])
        ->assertSessionHasNoErrors();

    expect($category->fresh())
        ->name->toBe(trim($name))
        ->colour->toBe('#EF4444');
})->with([
    'the same' => 'Work',
    'lower case' => 'work',
    'spaces at either end' => '  Work ',
]);

test('a category cannot be renamed to the name of another of the user\'s categories', function (string $name) {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Family']);
    $category = Category::factory()->for($user)->create(['name' => 'Work']);

    $this->actingAs($user)
        ->put(route('categories.update', $category), [...UPDATED_CATEGORY, 'name' => $name])
        ->assertSessionHasErrors(['name' => 'You already have a category with that name.']);

    expect($category->fresh()->name)->toBe('Work');
})->with([
    'the same' => 'Family',
    'upper case' => 'FAMILY',
]);

test('a category can be renamed to the name of another user\'s category', function () {
    Category::factory()->create(['name' => 'Family']);
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Work']);

    $this->actingAs($user)
        ->put(route('categories.update', $category), UPDATED_CATEGORY)
        ->assertSessionHasNoErrors();

    expect($category->fresh()->name)->toBe('Family');
});

test('the same checks apply as when creating a category', function (array $data, array $errors) {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Work', 'colour' => '#3B82F6']);

    $this->actingAs($user)
        ->put(route('categories.update', $category), $data)
        ->assertSessionHasErrors($errors);

    expect($category->fresh())
        ->name->toBe('Work')
        ->colour->toBe('#3B82F6');
})->with([
    'no name or colour' => [
        ['name' => '', 'colour' => ''],
        ['name' => 'Please enter a name.', 'colour' => 'Please choose a colour.'],
    ],
    'a name that is too long' => [
        ['name' => str_repeat('a', 51), 'colour' => '#3B82F6'],
        ['name' => 'The name cannot be longer than 50 characters.'],
    ],
    'a colour that is not a hex code' => [
        ['name' => 'Work', 'colour' => 'blue'],
        ['colour' => 'Please enter a valid hex code, such as #3B82F6.'],
    ],
]);

test('colours are saved in upper case with a #', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('categories.update', $category), [...UPDATED_CATEGORY, 'colour' => 'ef4444'])
        ->assertSessionHasNoErrors();

    expect($category->fresh()->colour)->toBe('#EF4444');
});

test('the create calendar item form shows the category\'s new name and colour', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Work', 'colour' => '#3B82F6']);
    SubCategory::factory()->for($category)->create();

    $this->actingAs($user)
        ->put(route('categories.update', $category), UPDATED_CATEGORY)
        ->assertSessionHasNoErrors();

    $this->get(route('calendar-items.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories.0.name', 'Family')
            ->where('categories.0.colour', '#EF4444'));
});

test('a user gets a not found page for another user\'s category and nothing is saved', function () {
    $category = Category::factory()->create(['name' => 'Work']);

    $this->actingAs(User::factory()->create());

    $this->get(route('categories.edit', $category))->assertNotFound();
    $this->put(route('categories.update', $category), UPDATED_CATEGORY)->assertNotFound();

    expect($category->fresh()->name)->toBe('Work');
});

test('a user gets a not found page for a category that does not exist', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('categories.edit', 999))
        ->assertNotFound();
});

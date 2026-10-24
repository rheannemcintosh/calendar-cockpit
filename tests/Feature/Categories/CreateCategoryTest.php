<?php

use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

const VALID_CATEGORY = [
    'name' => 'Work',
    'colour' => '#3B82F6',
];

test('guests are sent to the login page from the create category form', function () {
    $this->get(route('categories.create'))->assertRedirect(route('login'));
});

test('guests cannot create a category', function () {
    $this->post(route('categories.store'), VALID_CATEGORY)
        ->assertRedirect(route('login'));

    expect(Category::count())->toBe(0);
});

test('signed-in users see the create category form', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('categories.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('categories/Create'));
});

test('a user can create a category with a name and colour and goes back to the categories list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('categories.store'), VALID_CATEGORY)
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Category created.']);

    $category = Category::sole();

    $response->assertRedirect(route('categories.index'));

    expect($category->user_id)->toBe($user->id)
        ->and($category->name)->toBe('Work')
        ->and($category->colour)->toBe('#3B82F6');
});

test('hex codes can be sent with or without the # and in upper or lower case', function (string $colour) {
    $this->actingAs(User::factory()->create())
        ->post(route('categories.store'), [...VALID_CATEGORY, 'colour' => $colour])
        ->assertSessionHasNoErrors();

    expect(Category::sole()->colour)->toBe('#3B82F6');
})->with([
    'with # in upper case' => '#3B82F6',
    'with # in lower case' => '#3b82f6',
    'without # in upper case' => '3B82F6',
    'without # in lower case' => '3b82f6',
]);

test('a category needs a name and a colour', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('categories.store'), ['name' => '', 'colour' => ''])
        ->assertSessionHasErrors([
            'name' => 'Please enter a name.',
            'colour' => 'Please choose a colour.',
        ]);

    expect(Category::count())->toBe(0);
});

test('a category name cannot be longer than 50 characters', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('categories.store'), [...VALID_CATEGORY, 'name' => str_repeat('a', 51)])
        ->assertSessionHasErrors(['name' => 'The name cannot be longer than 50 characters.']);

    expect(Category::count())->toBe(0);
});

test('a category name can be 50 characters long', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('categories.store'), [...VALID_CATEGORY, 'name' => str_repeat('a', 50)])
        ->assertSessionHasNoErrors();

    expect(Category::count())->toBe(1);
});

test('a category colour must be a valid hex code', function (string $colour) {
    $this->actingAs(User::factory()->create())
        ->post(route('categories.store'), [...VALID_CATEGORY, 'colour' => $colour])
        ->assertSessionHasErrors(['colour' => 'Please enter a valid hex code, such as #3B82F6.']);

    expect(Category::count())->toBe(0);
})->with([
    'too short' => '#12345',
    'too long' => '#1234567',
    'not hex digits' => '#GGGGGG',
    'three digits' => '#FFF',
    'two #' => '##3B82F6',
    'a colour name' => 'blue',
]);

test('a user cannot have two categories with the same name', function (string $name) {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Work']);

    $this->actingAs($user)
        ->post(route('categories.store'), [...VALID_CATEGORY, 'name' => $name])
        ->assertSessionHasErrors(['name' => 'You already have a category with that name.']);

    expect($user->categories()->count())->toBe(1);
})->with([
    'the same' => 'Work',
    'lower case' => 'work',
    'upper case' => 'WORK',
    'spaces at either end' => '  work ',
]);

test('the name is saved without spaces at either end', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('categories.store'), [...VALID_CATEGORY, 'name' => '  Work '])
        ->assertSessionHasNoErrors();

    expect(Category::sole()->name)->toBe('Work');
});

test('two different users can each have a category with the same name', function () {
    Category::factory()->create(['name' => 'Work']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('categories.store'), VALID_CATEGORY)
        ->assertSessionHasNoErrors();

    expect($user->categories()->count())->toBe(1);
});

test('two categories can share a colour', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Family', 'colour' => '#3B82F6']);

    $this->actingAs($user)
        ->post(route('categories.store'), VALID_CATEGORY)
        ->assertSessionHasNoErrors();

    expect($user->categories()->count())->toBe(2);
});

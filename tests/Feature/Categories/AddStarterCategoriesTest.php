<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

test('guests cannot add the starter categories', function () {
    $this->post(route('categories.starter'))->assertRedirect(route('login'));

    expect(Category::count())->toBe(0);
});

test('a user with no categories can add the whole starter set', function () {
    $user = User::factory()->create();
    $starter = config('starter-categories');

    $this->actingAs($user)
        ->post(route('categories.starter'))
        ->assertRedirect(route('categories.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Starter categories added.']);

    expect($user->categories()->count())->toBe(count($starter))
        ->and(SubCategory::count())->toBe(collect($starter)->sum(fn (array $category) => count($category['sub_categories'])));
});

test('the categories page shows the starter categories once they are added', function () {
    $user = User::factory()->create();
    $starter = config('starter-categories');

    $this->actingAs($user)->post(route('categories.starter'));

    $this->actingAs($user)
        ->get(route('categories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('categories/Index')
            ->has('categories', count($starter)));
});

test('nothing is added for a user who already has a category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Mine']);

    $this->actingAs($user)
        ->post(route('categories.starter'))
        ->assertRedirect(route('categories.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'info',
            'message' => 'You already have categories, so no starter categories were added.',
        ]);

    expect($user->categories()->pluck('id')->all())->toBe([$category->id])
        ->and(SubCategory::count())->toBe(0);
});

test('adding the starter categories twice only adds them once', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('categories.starter'));
    $this->actingAs($user)->post(route('categories.starter'));

    expect($user->categories()->count())->toBe(count(config('starter-categories')));
});

test('other users\' categories do not stop a user adding the starter categories', function () {
    Category::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('categories.starter'));

    expect($user->categories()->count())->toBe(count(config('starter-categories')));
});

test('a newly registered user has no categories', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->categories()->count())->toBe(0);
})->skip(fn () => ! Features::enabled(Features::registration()), 'Registration is disabled.');

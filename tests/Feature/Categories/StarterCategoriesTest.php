<?php

use App\Actions\CreateStarterCategories;
use App\Enums\TimeType;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The starter set as product/starter-categories.md describes it, in the
 * same shape as config/starter-categories.php.
 *
 * @return list<array{name: string, colour: string, sub_categories: list<array{emoji: string, name: string, default_time_type: string}>}>
 */
function starterCategoriesFromProductDocument(): array
{
    $lines = file(base_path('product/starter-categories.md'), FILE_IGNORE_NEW_LINES);
    $timeTypes = collect(TimeType::cases())->mapWithKeys(fn (TimeType $type) => [$type->name => $type->value]);

    $categories = [];
    $section = null;
    $category = null;

    foreach ($lines as $line) {
        if (str_starts_with($line, '## ')) {
            $section = substr($line, 3);

            continue;
        }

        if (str_starts_with($line, '### ')) {
            $category = substr($line, 4);

            continue;
        }

        // Only table rows, and not their header or divider rows.
        if (! str_starts_with($line, '|') || str_contains($line, '---')) {
            continue;
        }

        $cells = array_map(trim(...), explode('|', trim($line, '|')));

        if ($section === 'Categories' && $cells[0] !== 'Category') {
            $categories[$cells[0]] = ['name' => $cells[0], 'colour' => trim($cells[2], '`'), 'sub_categories' => []];
        }

        if ($section === 'Sub-Categories' && $cells[0] !== 'Emoji') {
            $categories[$category]['sub_categories'][] = [
                'emoji' => $cells[0],
                'name' => $cells[1],
                'default_time_type' => $timeTypes[$cells[2]],
            ];
        }
    }

    return array_values($categories);
}

test('the starter set matches the product document', function () {
    $fromDocument = starterCategoriesFromProductDocument();

    expect($fromDocument)->toHaveCount(11)
        ->and(config('starter-categories'))->toBe($fromDocument);
});

test('every starter category and sub-category follows the category and sub-category rules', function () {
    $starterCategories = config('starter-categories');

    $categoryNames = array_map(fn (array $category) => mb_strtolower($category['name']), $starterCategories);
    expect($categoryNames)->toBe(array_unique($categoryNames));

    foreach ($starterCategories as $category) {
        expect(mb_strlen($category['name']))->toBeLessThanOrEqual(50)
            ->and($category['colour'])->toMatch('/^#[0-9A-F]{6}$/')
            ->and($category['sub_categories'])->not->toBeEmpty();

        $subCategoryNames = array_map(fn (array $subCategory) => mb_strtolower($subCategory['name']), $category['sub_categories']);
        expect($subCategoryNames)->toBe(array_unique($subCategoryNames));

        foreach ($category['sub_categories'] as $subCategory) {
            expect(mb_strlen($subCategory['name']))->toBeLessThanOrEqual(50)
                ->and(TimeType::tryFrom($subCategory['default_time_type']))->not->toBeNull();
        }
    }
});

test('every starter category and sub-category can be created by hand', function () {
    $this->actingAs($user = User::factory()->create());

    foreach (config('starter-categories') as $starter) {
        $this->post(route('categories.store'), ['name' => $starter['name'], 'colour' => $starter['colour']])
            ->assertSessionHasNoErrors();

        $category = $user->categories()->where('name', $starter['name'])->sole();

        foreach ($starter['sub_categories'] as $subCategory) {
            $this->post(route('sub-categories.store'), ['category_id' => $category->id, ...$subCategory])
                ->assertSessionHasNoErrors();
        }
    }
});

test('a new user is given every starter category and sub-category', function () {
    $user = User::factory()->create();

    $created = app(CreateStarterCategories::class)->handle($user);

    $expected = config('starter-categories');

    expect($created)->toHaveCount(count($expected))
        ->and(Category::count())->toBe(count($expected))
        ->and(SubCategory::count())->toBe(array_sum(array_map(fn (array $category) => count($category['sub_categories']), $expected)));

    foreach ($expected as $starter) {
        $category = $user->categories()->where('name', $starter['name'])->sole();

        expect($category->colour)->toBe($starter['colour'])
            ->and($category->subCategories()->orderBy('id')->get()->map(fn (SubCategory $subCategory) => [
                'emoji' => $subCategory->emoji,
                'name' => $subCategory->name,
                'default_time_type' => $subCategory->default_time_type->value,
            ])->all())->toBe($starter['sub_categories']);
    }
});

test('a user who already has a category is given nothing', function () {
    $user = User::factory()->create();
    $existing = Category::factory()->for($user)->create(['name' => 'Mine']);

    $created = app(CreateStarterCategories::class)->handle($user);

    expect($created)->toBeEmpty()
        ->and(Category::sole()->is($existing))->toBeTrue()
        ->and(SubCategory::count())->toBe(0);
});

test('asking twice creates the starter set only once', function () {
    $user = User::factory()->create();

    app(CreateStarterCategories::class)->handle($user);
    $second = app(CreateStarterCategories::class)->handle($user);

    expect($second)->toBeEmpty()
        ->and(Category::count())->toBe(count(config('starter-categories')));
});

test('another user\'s categories are left alone and do not stop the starter set', function () {
    $other = User::factory()->create();
    $theirs = Category::factory()->for($other)->create(['name' => 'Work', 'colour' => '#000000']);
    SubCategory::factory()->for($theirs)->create(['name' => 'In Office']);

    $user = User::factory()->create();

    app(CreateStarterCategories::class)->handle($user);

    expect($user->categories()->count())->toBe(count(config('starter-categories')))
        ->and($other->categories()->sole()->is($theirs))->toBeTrue()
        ->and($theirs->fresh()->colour)->toBe('#000000')
        ->and($theirs->subCategories()->pluck('name')->all())->toBe(['In Office']);
});

test('a failure part way through leaves the user with no categories', function () {
    $user = User::factory()->create();

    $starters = config('starter-categories');
    $starters[1]['sub_categories'][] = $starters[1]['sub_categories'][0];
    config(['starter-categories' => $starters]);

    expect(fn () => app(CreateStarterCategories::class)->handle($user))->toThrow(Exception::class);

    expect(DB::transactionLevel())->toBe(1)
        ->and(Category::count())->toBe(0)
        ->and(SubCategory::count())->toBe(0);
});

test('registering does not create any categories', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    expect(Category::count())->toBe(0)
        ->and(SubCategory::count())->toBe(0);
});

test('the database seeder does not create any categories', function () {
    $this->seed();

    expect(User::count())->toBeGreaterThan(0)
        ->and(Category::count())->toBe(0)
        ->and(SubCategory::count())->toBe(0);
});

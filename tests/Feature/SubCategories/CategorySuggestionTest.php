<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use App\Support\CategorySuggester;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config([
        'services.typesafe.key' => 'ts-test-key',
        'services.typesafe.model' => 'jev-latest',
        'services.typesafe.category_suggestion_confidence' => 0.4,
    ]);
});

/**
 * Jev's answer to the category question.
 */
function jevCategoryAnswer(string|int $choice, float $confidence): array
{
    return [
        'model' => 'jev-1.14.0',
        'answers' => [
            'category' => [
                'type' => 'choice',
                'choice' => (string) $choice,
                'confidence' => $confidence,
                'probabilities' => [(string) $choice => $confidence],
            ],
        ],
        'usage' => ['input_tokens' => 500, 'output_tokens' => 50],
    ];
}

/**
 * Fake Jev's answer to every request.
 */
function fakeJevCategory(array|Closure $answer, int $status = 200): void
{
    Http::fake(['https://api.typesafe.ai/v1/systemone' => $answer instanceof Closure ? $answer : Http::response($answer, $status)]);
}

test('pages are told whether category suggestions are enabled', function (?string $key, bool $enabled) {
    config(['services.typesafe.key' => $key]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('categorySuggestionsEnabled', $enabled));
})->with([
    'with a key' => ['ts-test-key', true],
    'without a key' => [null, false],
    'with a blank key' => ['', false],
]);

test('guests cannot ask for a suggestion', function () {
    Http::fake();

    $this->getJson(route('sub-categories.category-suggestion', ['name' => 'Running']))->assertUnauthorized();

    Http::assertNothingSent();
});

test('signed-in users get the category Jev chooses for the name', function () {
    $user = User::factory()->create();
    $fitness = Category::factory()->for($user)->create(['name' => 'Fitness']);
    $work = Category::factory()->for($user)->create(['name' => 'work']);
    SubCategory::factory()->for($fitness)->create(['name' => 'Swimming']);
    SubCategory::factory()->for($fitness)->create(['name' => 'gym']);
    fakeJevCategory(jevCategoryAnswer($fitness->id, 0.97));

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => '  Running  ']))
        ->assertOk()
        ->assertExactJson(['category_id' => $fitness->id]);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.typesafe.ai/v1/systemone'
        && $request->method() === 'POST'
        && $request->hasHeader('Authorization', 'Bearer ts-test-key')
        && $request['model'] === 'jev-latest'
        && $request['state'] === ['sub_category' => 'Running']
        && $request['questions']['category']['type'] === 'choice'
        && $request['questions']['category']['criteria'] === [
            $fitness->id => ['category' => 'Fitness', 'sub_categories' => ['gym', 'Swimming']],
            $work->id => ['category' => 'work', 'sub_categories' => []],
            CategorySuggester::NONE => $request['questions']['category']['criteria'][CategorySuggester::NONE],
        ]);
});

test('the categories are sent as a map keyed by their ids', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Fitness']);
    fakeJevCategory(jevCategoryAnswer($category->id, 1.0));

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => 'Running']))
        ->assertOk();

    Http::assertSent(fn (Request $request) => str_contains($request->body(), '"criteria":{"'.$category->id.'":{"category":"Fitness","sub_categories":[]},"none":'));
});

test('another user\'s categories are never sent to Jev', function () {
    $user = User::factory()->create();
    $own = Category::factory()->for($user)->create(['name' => 'Fitness']);
    $other = Category::factory()->create(['name' => 'Sport']);
    SubCategory::factory()->for($other)->create(['name' => 'Running']);
    fakeJevCategory(jevCategoryAnswer($own->id, 1.0));

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => 'Running']))
        ->assertOk()
        ->assertExactJson(['category_id' => $own->id]);

    Http::assertSent(fn (Request $request) => array_keys($request['questions']['category']['criteria']) === [$own->id, CategorySuggester::NONE]
        && ! str_contains($request->body(), 'Sport'));
});

test('another user\'s category is never returned, even when Jev chooses it', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Fitness']);
    $other = Category::factory()->create(['name' => 'Sport']);
    fakeJevCategory(jevCategoryAnswer($other->id, 1.0));

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => 'Running']))
        ->assertOk()
        ->assertExactJson(['category_id' => null]);
});

test('there is no suggestion, and Jev is not asked, when the user has no categories', function () {
    Http::fake();
    Category::factory()->create();

    $this->actingAs(User::factory()->create())
        ->getJson(route('sub-categories.category-suggestion', ['name' => 'Running']))
        ->assertOk()
        ->assertExactJson(['category_id' => null]);

    Http::assertNothingSent();
});

test('at most 254 categories, and 30 sub-categories of each, are sent', function () {
    $user = User::factory()->create();
    $categories = Category::factory()->for($user)->count(CategorySuggester::MAX_CATEGORIES + 1)->sequence(
        fn ($sequence) => ['name' => sprintf('Category %03d', $sequence->index)],
    )->create();
    SubCategory::factory()->for($categories->first())->count(CategorySuggester::MAX_SUB_CATEGORIES + 1)->sequence(
        fn ($sequence) => ['name' => sprintf('Sub-category %02d', $sequence->index)],
    )->create();
    fakeJevCategory(jevCategoryAnswer($categories->first()->id, 1.0));

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => 'Running']))
        ->assertOk()
        ->assertExactJson(['category_id' => $categories->first()->id]);

    Http::assertSent(function (Request $request) use ($categories) {
        $criteria = $request['questions']['category']['criteria'];

        return count($criteria) === 255
            && ! array_key_exists($categories->last()->id, $criteria)
            && count($criteria[$categories->first()->id]['sub_categories']) === 30
            && end($criteria[$categories->first()->id]['sub_categories']) === 'Sub-category 29';
    });
});

test('there is no suggestion when Jev is not confident enough', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Fitness']);
    fakeJevCategory(jevCategoryAnswer($category->id, 0.32));

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => 'Errands']))
        ->assertOk()
        ->assertExactJson(['category_id' => null]);
});

test('the confidence threshold comes from config', function () {
    config(['services.typesafe.category_suggestion_confidence' => 0.9]);
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Work']);
    fakeJevCategory(jevCategoryAnswer($category->id, 0.88));

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => 'Deep work']))
        ->assertOk()
        ->assertExactJson(['category_id' => null]);
});

test('there is no suggestion when Jev says no category fits', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Fitness']);
    fakeJevCategory(jevCategoryAnswer(CategorySuggester::NONE, 0.98));

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => 'asdfgh']))
        ->assertOk()
        ->assertExactJson(['category_id' => null]);
});

test('there is no suggestion when Jev chooses something that is not a category', function (string $choice) {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Fitness']);
    fakeJevCategory(jevCategoryAnswer($choice, 1.0));

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => 'Running']))
        ->assertOk()
        ->assertExactJson(['category_id' => null]);
})->with(['Fitness', '999999', '1.5', '-1', '']);

test('there is no suggestion when Jev answers with an error', function (int $status) {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Fitness']);
    fakeJevCategory(['detail' => 'Nope'], $status);

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => 'Running']))
        ->assertOk()
        ->assertExactJson(['category_id' => null]);
})->with([400, 401, 422, 429, 500, 529]);

test('there is no suggestion when Jev cannot be reached or times out', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Fitness']);
    fakeJevCategory(fn () => Http::failedConnection());

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => 'Running']))
        ->assertOk()
        ->assertExactJson(['category_id' => null]);
});

test('there is no suggestion when Jev answers with something unexpected', function (array $body) {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Fitness']);
    fakeJevCategory($body);

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => 'Running']))
        ->assertOk()
        ->assertExactJson(['category_id' => null]);
})->with([
    'no answers' => [['answers' => []]],
    'no confidence' => [['answers' => ['category' => ['choice' => '1']]]],
    'a number as the choice' => [['answers' => ['category' => ['choice' => 1, 'confidence' => 1.0]]]],
]);

test('there is no suggestion, and Jev is not asked, without an API key', function (?string $key) {
    config(['services.typesafe.key' => $key]);
    Http::fake();
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Fitness']);

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => 'Running']))
        ->assertOk()
        ->assertExactJson(['category_id' => null]);

    Http::assertNothingSent();
})->with([null, '']);

test('a blank name or one over 50 characters is rejected, and Jev is not asked', function (?string $name, string $message) {
    Http::fake();
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Fitness']);

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => $name]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name' => $message]);

    Http::assertNothingSent();
})->with([
    'missing' => [null, 'Please enter a name.'],
    'blank' => ['   ', 'Please enter a name.'],
    'too long' => [str_repeat('a', 51), 'The name cannot be longer than 50 characters.'],
]);

test('a name of exactly 50 characters is accepted', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Fitness']);
    fakeJevCategory(jevCategoryAnswer($category->id, 1.0));

    $this->actingAs($user)
        ->getJson(route('sub-categories.category-suggestion', ['name' => str_repeat('a', 50)]))
        ->assertOk()
        ->assertExactJson(['category_id' => $category->id]);
});

test('suggestions are throttled', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Fitness']);
    fakeJevCategory(jevCategoryAnswer($category->id, 1.0));

    foreach (range(1, 60) as $attempt) {
        $this->actingAs($user)->getJson(route('sub-categories.category-suggestion', ['name' => 'Running']))->assertOk();
    }

    $this->actingAs($user)->getJson(route('sub-categories.category-suggestion', ['name' => 'Running']))->assertTooManyRequests();
});

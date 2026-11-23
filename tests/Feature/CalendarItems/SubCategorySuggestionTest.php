<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use App\Support\SubCategorySuggester;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config([
        'services.typesafe.key' => 'ts-test-key',
        'services.typesafe.model' => 'jev-latest',
        'services.typesafe.sub_category_suggestion_confidence' => 0.4,
    ]);
});

/**
 * Jev's answer to the sub-category question.
 */
function jevSubCategoryAnswer(string|int $choice, float $confidence): array
{
    return [
        'model' => 'jev-1.14.0',
        'answers' => [
            'sub_category' => [
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
function fakeJevSubCategory(array|Closure $answer, int $status = 200): void
{
    Http::fake(['https://api.typesafe.ai/v1/systemone' => $answer instanceof Closure ? $answer : Http::response($answer, $status)]);
}

/**
 * A sub-category of one of the user's categories.
 */
function ownSubCategory(User $user, string $category = 'Health', string $name = 'Dentist'): SubCategory
{
    return SubCategory::factory()
        ->for(Category::factory()->for($user)->create(['name' => $category]))
        ->create(['name' => $name]);
}

test('pages are told whether sub-category suggestions are enabled', function (?string $key, bool $enabled) {
    config(['services.typesafe.key' => $key]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('subCategorySuggestionsEnabled', $enabled));
})->with([
    'with a key' => ['ts-test-key', true],
    'without a key' => [null, false],
    'with a blank key' => ['', false],
]);

test('guests cannot ask for a suggestion', function () {
    Http::fake();

    $this->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Dentist']))->assertUnauthorized();

    Http::assertNothingSent();
});

test('signed-in users get the sub-category Jev chooses for the title', function () {
    $user = User::factory()->create();
    $health = Category::factory()->for($user)->create(['name' => 'Health']);
    $fitness = Category::factory()->for($user)->create(['name' => 'fitness']);
    $dentist = SubCategory::factory()->for($health)->create(['name' => 'Dentist']);
    $doctor = SubCategory::factory()->for($health)->create(['name' => 'doctor']);
    $gym = SubCategory::factory()->for($fitness)->create(['name' => 'Gym']);
    Category::factory()->for($user)->create(['name' => 'Empty']);
    fakeJevSubCategory(jevSubCategoryAnswer($dentist->id, 0.97));

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => '  Check-up at the dentist  ']))
        ->assertOk()
        ->assertExactJson(['sub_category_id' => $dentist->id]);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.typesafe.ai/v1/systemone'
        && $request->method() === 'POST'
        && $request->hasHeader('Authorization', 'Bearer ts-test-key')
        && $request['model'] === 'jev-latest'
        && $request['state'] === ['calendar_item' => 'Check-up at the dentist']
        && $request['questions']['sub_category']['type'] === 'choice'
        && $request['questions']['sub_category']['criteria'] === [
            $gym->id => ['category' => 'fitness', 'sub_category' => 'Gym'],
            $dentist->id => ['category' => 'Health', 'sub_category' => 'Dentist'],
            $doctor->id => ['category' => 'Health', 'sub_category' => 'doctor'],
            SubCategorySuggester::NONE => $request['questions']['sub_category']['criteria'][SubCategorySuggester::NONE],
        ]);
});

test('the sub-categories are sent as a map keyed by their ids', function () {
    $user = User::factory()->create();
    $subCategory = ownSubCategory($user);
    fakeJevSubCategory(jevSubCategoryAnswer($subCategory->id, 1.0));

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Dentist']))
        ->assertOk();

    Http::assertSent(fn (Request $request) => str_contains($request->body(), '"criteria":{"'.$subCategory->id.'":{"category":"Health","sub_category":"Dentist"},"none":'));
});

test('another user\'s sub-categories are never sent to Jev', function () {
    $user = User::factory()->create();
    $own = ownSubCategory($user);
    ownSubCategory(User::factory()->create(), 'Sport', 'Running');
    fakeJevSubCategory(jevSubCategoryAnswer($own->id, 1.0));

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Morning run']))
        ->assertOk()
        ->assertExactJson(['sub_category_id' => $own->id]);

    Http::assertSent(fn (Request $request) => array_keys($request['questions']['sub_category']['criteria']) === [$own->id, SubCategorySuggester::NONE]
        && ! str_contains($request->body(), 'Sport')
        && ! str_contains($request->body(), 'Running'));
});

test('another user\'s sub-category is never returned, even when Jev chooses it', function () {
    $user = User::factory()->create();
    ownSubCategory($user);
    $other = ownSubCategory(User::factory()->create(), 'Sport', 'Running');
    fakeJevSubCategory(jevSubCategoryAnswer($other->id, 1.0));

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Morning run']))
        ->assertOk()
        ->assertExactJson(['sub_category_id' => null]);
});

test('there is no suggestion, and Jev is not asked, when the user has no sub-categories', function () {
    Http::fake();
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Health']);
    ownSubCategory(User::factory()->create());

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Dentist']))
        ->assertOk()
        ->assertExactJson(['sub_category_id' => null]);

    Http::assertNothingSent();
});

test('at most 254 sub-categories are sent', function () {
    $user = User::factory()->create();
    $subCategories = SubCategory::factory()
        ->for(Category::factory()->for($user)->create(['name' => 'Health']))
        ->count(SubCategorySuggester::MAX_SUB_CATEGORIES + 1)
        ->sequence(fn ($sequence) => ['name' => sprintf('Sub-category %03d', $sequence->index)])
        ->create();
    fakeJevSubCategory(jevSubCategoryAnswer($subCategories->first()->id, 1.0));

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Dentist']))
        ->assertOk()
        ->assertExactJson(['sub_category_id' => $subCategories->first()->id]);

    Http::assertSent(function (Request $request) use ($subCategories) {
        $criteria = $request['questions']['sub_category']['criteria'];

        return count($criteria) === 255
            && ! array_key_exists($subCategories->last()->id, $criteria)
            && array_key_exists(SubCategorySuggester::NONE, $criteria);
    });
});

test('there is no suggestion when Jev is not confident enough', function () {
    $user = User::factory()->create();
    $subCategory = ownSubCategory($user);
    fakeJevSubCategory(jevSubCategoryAnswer($subCategory->id, 0.32));

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Errands']))
        ->assertOk()
        ->assertExactJson(['sub_category_id' => null]);
});

test('the confidence threshold comes from config', function () {
    config(['services.typesafe.sub_category_suggestion_confidence' => 0.9]);
    $user = User::factory()->create();
    $subCategory = ownSubCategory($user);
    fakeJevSubCategory(jevSubCategoryAnswer($subCategory->id, 0.88));

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Dentist']))
        ->assertOk()
        ->assertExactJson(['sub_category_id' => null]);
});

test('there is no suggestion when Jev says no sub-category fits', function () {
    $user = User::factory()->create();
    ownSubCategory($user);
    fakeJevSubCategory(jevSubCategoryAnswer(SubCategorySuggester::NONE, 0.98));

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'asdfgh']))
        ->assertOk()
        ->assertExactJson(['sub_category_id' => null]);
});

test('there is no suggestion when Jev chooses something that is not a sub-category', function (string $choice) {
    $user = User::factory()->create();
    ownSubCategory($user);
    fakeJevSubCategory(jevSubCategoryAnswer($choice, 1.0));

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Dentist']))
        ->assertOk()
        ->assertExactJson(['sub_category_id' => null]);
})->with(['Dentist', '999999', '1.5', '-1', '']);

test('there is no suggestion when Jev answers with an error', function (int $status) {
    $user = User::factory()->create();
    ownSubCategory($user);
    fakeJevSubCategory(['detail' => 'Nope'], $status);

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Dentist']))
        ->assertOk()
        ->assertExactJson(['sub_category_id' => null]);
})->with([400, 401, 422, 429, 500, 529]);

test('there is no suggestion when Jev cannot be reached or times out', function () {
    $user = User::factory()->create();
    ownSubCategory($user);
    fakeJevSubCategory(fn () => Http::failedConnection());

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Dentist']))
        ->assertOk()
        ->assertExactJson(['sub_category_id' => null]);
});

test('there is no suggestion when Jev answers with something unexpected', function (array $body) {
    $user = User::factory()->create();
    ownSubCategory($user);
    fakeJevSubCategory($body);

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Dentist']))
        ->assertOk()
        ->assertExactJson(['sub_category_id' => null]);
})->with([
    'no answers' => [['answers' => []]],
    'no confidence' => [['answers' => ['sub_category' => ['choice' => '1']]]],
    'a number as the choice' => [['answers' => ['sub_category' => ['choice' => 1, 'confidence' => 1.0]]]],
]);

test('there is no suggestion, and Jev is not asked, without an API key', function (?string $key) {
    config(['services.typesafe.key' => $key]);
    Http::fake();
    $user = User::factory()->create();
    ownSubCategory($user);

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Dentist']))
        ->assertOk()
        ->assertExactJson(['sub_category_id' => null]);

    Http::assertNothingSent();
})->with([null, '']);

test('a blank title or one over 255 characters is rejected, and Jev is not asked', function (?string $title, string $message) {
    Http::fake();
    $user = User::factory()->create();
    ownSubCategory($user);

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => $title]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title' => $message]);

    Http::assertNothingSent();
})->with([
    'missing' => [null, 'Please enter a title.'],
    'blank' => ['   ', 'Please enter a title.'],
    'too long' => [str_repeat('a', 256), 'The title cannot be longer than 255 characters.'],
]);

test('a title of exactly 255 characters is accepted', function () {
    $user = User::factory()->create();
    $subCategory = ownSubCategory($user);
    fakeJevSubCategory(jevSubCategoryAnswer($subCategory->id, 1.0));

    $this->actingAs($user)
        ->getJson(route('calendar-items.sub-category-suggestion', ['title' => str_repeat('a', 255)]))
        ->assertOk()
        ->assertExactJson(['sub_category_id' => $subCategory->id]);
});

test('suggestions are throttled', function () {
    $user = User::factory()->create();
    $subCategory = ownSubCategory($user);
    fakeJevSubCategory(jevSubCategoryAnswer($subCategory->id, 1.0));

    foreach (range(1, 60) as $attempt) {
        $this->actingAs($user)->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Dentist']))->assertOk();
    }

    $this->actingAs($user)->getJson(route('calendar-items.sub-category-suggestion', ['title' => 'Dentist']))->assertTooManyRequests();
});

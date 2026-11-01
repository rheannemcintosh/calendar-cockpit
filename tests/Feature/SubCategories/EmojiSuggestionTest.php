<?php

use App\Models\Category;
use App\Models\User;
use App\Support\EmojiSuggester;
use App\Support\EmojiSuggestionCandidates;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const JEV_URL = 'https://api.typesafe.ai/v1/systemone';

beforeEach(function () {
    Http::preventStrayRequests();
    config([
        'services.typesafe.key' => 'ts-test-key',
        'services.typesafe.model' => 'jev-latest',
        'services.typesafe.emoji_suggestion_confidence' => 0.4,
    ]);
});

/**
 * Jev's answer to the emoji question.
 */
function jevAnswer(string $choice, float $confidence): array
{
    return [
        'model' => 'jev-1.14.0',
        'answers' => [
            'emoji' => [
                'type' => 'choice',
                'choice' => $choice,
                'confidence' => $confidence,
                'probabilities' => [$choice => $confidence],
            ],
        ],
        'usage' => ['input_tokens' => 5000, 'output_tokens' => 2000],
    ];
}

test('the candidates fit in one choice question and are each a single emoji', function () {
    expect(count(EmojiSuggestionCandidates::ALL) + 1)->toBeLessThanOrEqual(255);

    foreach (array_keys(EmojiSuggestionCandidates::ALL) as $emoji) {
        expect(grapheme_strlen((string) $emoji))->toBe(1)
            ->and(preg_match('/\p{Extended_Pictographic}/u', (string) $emoji))->toBe(1);
    }
});

test('guests cannot ask for a suggestion', function () {
    Http::fake();

    $this->getJson(route('sub-categories.emoji-suggestion', ['name' => 'Running']))->assertUnauthorized();

    Http::assertNothingSent();
});

test('signed-in users get the emoji Jev chooses for the name and category', function () {
    Http::fake([JEV_URL => Http::response(jevAnswer('🏃', 0.98))]);
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Fitness']);

    $this->actingAs($user)
        ->getJson(route('sub-categories.emoji-suggestion', ['name' => '  Running  ', 'category_id' => $category->id]))
        ->assertOk()
        ->assertExactJson(['emoji' => '🏃']);

    Http::assertSent(fn (Request $request) => $request->url() === JEV_URL
        && $request->method() === 'POST'
        && $request->hasHeader('Authorization', 'Bearer ts-test-key')
        && $request['model'] === 'jev-latest'
        && $request['state'] === ['sub_category' => 'Running', 'category' => 'Fitness']
        && $request['questions']['emoji']['type'] === 'choice'
        && $request['questions']['emoji']['criteria'] === [
            ...EmojiSuggestionCandidates::ALL,
            EmojiSuggester::NONE => $request['questions']['emoji']['criteria'][EmojiSuggester::NONE],
        ]);
});

test('a suggestion can be asked for before a category is chosen', function () {
    Http::fake([JEV_URL => Http::response(jevAnswer('🐶', 1.0))]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('sub-categories.emoji-suggestion', ['name' => 'Dog walk']))
        ->assertOk()
        ->assertExactJson(['emoji' => '🐶']);

    Http::assertSent(fn (Request $request) => $request['state'] === ['sub_category' => 'Dog walk']);
});

test('there is no suggestion when Jev is not confident enough', function () {
    Http::fake([JEV_URL => Http::response(jevAnswer('👥', 0.32))]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('sub-categories.emoji-suggestion', ['name' => '1:1s']))
        ->assertOk()
        ->assertExactJson(['emoji' => null]);
});

test('the confidence threshold comes from config', function () {
    config(['services.typesafe.emoji_suggestion_confidence' => 0.9]);
    Http::fake([JEV_URL => Http::response(jevAnswer('💻', 0.88))]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('sub-categories.emoji-suggestion', ['name' => 'Deep work']))
        ->assertOk()
        ->assertExactJson(['emoji' => null]);
});

test('there is no suggestion when Jev says no emoji fits', function () {
    Http::fake([JEV_URL => Http::response(jevAnswer(EmojiSuggester::NONE, 0.98))]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('sub-categories.emoji-suggestion', ['name' => 'asdfgh']))
        ->assertOk()
        ->assertExactJson(['emoji' => null]);
});

test('there is no suggestion when Jev chooses something that is not a candidate', function () {
    Http::fake([JEV_URL => Http::response(jevAnswer('🦄', 1.0))]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('sub-categories.emoji-suggestion', ['name' => 'Unicorns']))
        ->assertOk()
        ->assertExactJson(['emoji' => null]);
});

test('there is no suggestion when Jev answers with an error', function (int $status) {
    Http::fake([JEV_URL => Http::response(['detail' => 'Nope'], $status)]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('sub-categories.emoji-suggestion', ['name' => 'Running']))
        ->assertOk()
        ->assertExactJson(['emoji' => null]);
})->with([400, 401, 429, 500, 503]);

test('there is no suggestion when Jev cannot be reached or times out', function () {
    Http::fake([JEV_URL => Http::failedConnection()]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('sub-categories.emoji-suggestion', ['name' => 'Running']))
        ->assertOk()
        ->assertExactJson(['emoji' => null]);
});

test('there is no suggestion when Jev answers with something unexpected', function () {
    Http::fake([JEV_URL => Http::response(['answers' => []])]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('sub-categories.emoji-suggestion', ['name' => 'Running']))
        ->assertOk()
        ->assertExactJson(['emoji' => null]);
});

test('there is no suggestion, and Jev is not asked, without an API key', function (?string $key) {
    config(['services.typesafe.key' => $key]);
    Http::fake();

    $this->actingAs(User::factory()->create())
        ->getJson(route('sub-categories.emoji-suggestion', ['name' => 'Running']))
        ->assertOk()
        ->assertExactJson(['emoji' => null]);

    Http::assertNothingSent();
})->with([null, '']);

test('a blank name or one over 50 characters is rejected, and Jev is not asked', function (?string $name, string $message) {
    Http::fake();

    $this->actingAs(User::factory()->create())
        ->getJson(route('sub-categories.emoji-suggestion', ['name' => $name]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name' => $message]);

    Http::assertNothingSent();
})->with([
    'missing' => [null, 'Please enter a name.'],
    'blank' => ['   ', 'Please enter a name.'],
    'too long' => [str_repeat('a', 51), 'The name cannot be longer than 50 characters.'],
]);

test('a name of exactly 50 characters is accepted', function () {
    Http::fake([JEV_URL => Http::response(jevAnswer('🏃', 1.0))]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('sub-categories.emoji-suggestion', ['name' => str_repeat('a', 50)]))
        ->assertOk();
});

test('another user\'s category is rejected, and Jev is not asked', function () {
    Http::fake();
    $otherCategory = Category::factory()->create();

    $this->actingAs(User::factory()->create())
        ->getJson(route('sub-categories.emoji-suggestion', ['name' => 'Running', 'category_id' => $otherCategory->id]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id' => 'Please choose a category again.']);

    Http::assertNothingSent();
});

test('suggestions are throttled', function () {
    Http::fake([JEV_URL => Http::response(jevAnswer('🏃', 1.0))]);
    $user = User::factory()->create();

    foreach (range(1, 60) as $attempt) {
        $this->actingAs($user)->getJson(route('sub-categories.emoji-suggestion', ['name' => 'Running']))->assertOk();
    }

    $this->actingAs($user)->getJson(route('sub-categories.emoji-suggestion', ['name' => 'Running']))->assertTooManyRequests();
});

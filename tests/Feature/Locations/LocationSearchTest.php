<?php

use App\Models\User;
use App\Support\LocationSearch;
use App\Support\LocationSearchFailed;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const MAPBOX_SEARCH_URL = 'https://api.mapbox.com/search/searchbox/v1/forward*';

beforeEach(function () {
    Http::preventStrayRequests();
    config(['services.mapbox.token' => 'pk.test-token']);
});

function mapboxFeature(array $properties): array
{
    return ['type' => 'Feature', 'geometry' => ['type' => 'Point', 'coordinates' => [0, 0]], 'properties' => $properties];
}

function mapboxResponse(array $features): array
{
    return ['type' => 'FeatureCollection', 'features' => $features, 'attribution' => '© 2026 Mapbox'];
}

test('a search asks Mapbox for up to five matches for the query with the token', function () {
    Http::fake([MAPBOX_SEARCH_URL => Http::response(mapboxResponse([]))]);

    (new LocationSearch)->search('Tesco Extra');

    Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://api.mapbox.com/search/searchbox/v1/forward?')
        && $request->method() === 'GET'
        && $request['q'] === 'Tesco Extra'
        && (int) $request['limit'] === 5
        && $request['access_token'] === 'pk.test-token');
});

test('a search returns each match with its name, address and coordinates in the order Mapbox gives them', function () {
    Http::fake([MAPBOX_SEARCH_URL => Http::response(mapboxResponse([
        mapboxFeature([
            'mapbox_id' => 'dXJuOm1ieHBvaTox',
            'name' => 'Tesco Extra',
            'full_address' => '1 High Street, Leeds, LS1 1AA, United Kingdom',
            'place_formatted' => 'Leeds, LS1 1AA, United Kingdom',
            'coordinates' => ['latitude' => 53.7997, 'longitude' => -1.5492],
        ]),
        mapboxFeature([
            'mapbox_id' => 'dXJuOm1ieHBsYzoy',
            'name' => 'Leeds',
            'place_formatted' => 'West Yorkshire, England, United Kingdom',
            'coordinates' => ['latitude' => 53.8, 'longitude' => -1.55],
        ]),
    ]))]);

    expect((new LocationSearch)->search('Tesco'))->toBe([
        [
            'id' => 'dXJuOm1ieHBvaTox',
            'name' => 'Tesco Extra',
            'address' => '1 High Street, Leeds, LS1 1AA, United Kingdom',
            'latitude' => 53.7997,
            'longitude' => -1.5492,
        ],
        [
            'id' => 'dXJuOm1ieHBsYzoy',
            'name' => 'Leeds',
            'address' => 'West Yorkshire, England, United Kingdom',
            'latitude' => 53.8,
            'longitude' => -1.55,
        ],
    ]);
});

test('a match with no address has an empty address', function () {
    Http::fake([MAPBOX_SEARCH_URL => Http::response(mapboxResponse([
        mapboxFeature(['mapbox_id' => 'abc', 'name' => 'Somewhere', 'coordinates' => ['latitude' => 1, 'longitude' => 2]]),
    ]))]);

    expect((new LocationSearch)->search('Somewhere'))->toBe([
        ['id' => 'abc', 'name' => 'Somewhere', 'address' => '', 'latitude' => 1.0, 'longitude' => 2.0],
    ]);
});

test('a match without an id, a name or coordinates is left out', function () {
    Http::fake([MAPBOX_SEARCH_URL => Http::response(mapboxResponse([
        mapboxFeature(['name' => 'No id', 'coordinates' => ['latitude' => 1, 'longitude' => 2]]),
        mapboxFeature(['mapbox_id' => 'a', 'coordinates' => ['latitude' => 1, 'longitude' => 2]]),
        mapboxFeature(['mapbox_id' => 'b', 'name' => 'No coordinates']),
        mapboxFeature(['mapbox_id' => 'c', 'name' => 'Kept', 'coordinates' => ['latitude' => 1, 'longitude' => 2]]),
    ]))]);

    expect(array_column((new LocationSearch)->search('x'), 'name'))->toBe(['Kept']);
});

test('a search with no matches returns an empty list', function () {
    Http::fake([MAPBOX_SEARCH_URL => Http::response(mapboxResponse([]))]);

    expect((new LocationSearch)->search('zzzzqqq'))->toBe([]);
});

test('a search fails without a Mapbox token, and Mapbox is not asked', function (?string $token) {
    config(['services.mapbox.token' => $token]);
    Http::fake();

    expect(fn () => (new LocationSearch)->search('Tesco'))->toThrow(LocationSearchFailed::class, 'MAPBOX_ACCESS_TOKEN');

    Http::assertNothingSent();
})->with([null, '']);

test('a search fails when Mapbox answers with an error', function (int $status) {
    Http::fake([MAPBOX_SEARCH_URL => Http::response(['message' => 'Nope'], $status)]);

    expect(fn () => (new LocationSearch)->search('Tesco'))->toThrow(LocationSearchFailed::class);
})->with([401, 429, 500]);

test('a search fails when Mapbox cannot be reached', function () {
    Http::fake([MAPBOX_SEARCH_URL => Http::failedConnection()]);

    expect(fn () => (new LocationSearch)->search('Tesco'))->toThrow(LocationSearchFailed::class);
});

test('guests cannot search for locations', function () {
    Http::fake();

    $this->getJson(route('locations.search', ['q' => 'Tesco']))->assertUnauthorized();

    Http::assertNothingSent();
});

test('signed-in users get the matches for their search', function () {
    Http::fake([MAPBOX_SEARCH_URL => Http::response(mapboxResponse([
        mapboxFeature([
            'mapbox_id' => 'dXJuOm1ieHBvaTox',
            'name' => 'Tesco Extra',
            'full_address' => '1 High Street, Leeds, LS1 1AA, United Kingdom',
            'coordinates' => ['latitude' => 53.7997, 'longitude' => -1.5492],
        ]),
    ]))]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('locations.search', ['q' => '  Tesco Extra  ']))
        ->assertOk()
        ->assertExactJson(['matches' => [[
            'id' => 'dXJuOm1ieHBvaTox',
            'name' => 'Tesco Extra',
            'address' => '1 High Street, Leeds, LS1 1AA, United Kingdom',
            'latitude' => 53.7997,
            'longitude' => -1.5492,
        ]]]);

    Http::assertSent(fn (Request $request) => $request['q'] === 'Tesco Extra');
});

test('a search needs something to search for, of at most 256 characters', function (array $query) {
    Http::fake();

    $this->actingAs(User::factory()->create())
        ->getJson(route('locations.search', $query))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('q');

    Http::assertNothingSent();
})->with([
    'missing' => [[]],
    'empty' => [['q' => '']],
    'only spaces' => [['q' => '   ']],
    'too long' => [['q' => str_repeat('a', 257)]],
]);

test('a failed search tells the user it is not available right now', function () {
    Http::fake([MAPBOX_SEARCH_URL => Http::response(['message' => 'Not Authorized - Invalid Token'], 401)]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('locations.search', ['q' => 'Tesco']))
        ->assertServiceUnavailable()
        ->assertExactJson(['message' => 'Location search is not available right now. Try again later.']);
});

test('searches are limited to 60 a minute for each user', function () {
    Http::fake([MAPBOX_SEARCH_URL => Http::response(mapboxResponse([]))]);
    $user = User::factory()->create();

    for ($i = 0; $i < 60; $i++) {
        $this->actingAs($user)->getJson(route('locations.search', ['q' => 'Tesco']))->assertOk();
    }

    $this->actingAs($user)->getJson(route('locations.search', ['q' => 'Tesco']))->assertTooManyRequests();

    $this->actingAs(User::factory()->create())->getJson(route('locations.search', ['q' => 'Tesco']))->assertOk();
});

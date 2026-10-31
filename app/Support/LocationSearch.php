<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Searches Mapbox for real places by name or address, using the Search Box API's
 * forward search, which finds both places such as shops and plain addresses.
 *
 * @phpstan-type LocationMatch array{id: string, name: string, address: string, latitude: float, longitude: float}
 */
class LocationSearch
{
    private const URL = 'https://api.mapbox.com/search/searchbox/v1/forward';

    /**
     * How many matches a search returns at most.
     */
    public const LIMIT = 5;

    /**
     * Find up to five places matching the query, best match first.
     *
     * @return list<LocationMatch>
     *
     * @throws LocationSearchFailed when there is no Mapbox token, or Mapbox cannot be reached or answers with an error.
     */
    public function search(string $query): array
    {
        $token = config('services.mapbox.token');

        if (! is_string($token) || $token === '') {
            throw new LocationSearchFailed('No Mapbox access token is set. Set MAPBOX_ACCESS_TOKEN.');
        }

        try {
            $response = Http::timeout(5)
                ->get(self::URL, [
                    'q' => $query,
                    'limit' => self::LIMIT,
                    'access_token' => $token,
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new LocationSearchFailed('Mapbox location search failed.', previous: $exception);
        }

        $matches = [];

        foreach ($response->json('features') ?? [] as $feature) {
            $properties = $feature['properties'] ?? [];
            $coordinates = $properties['coordinates'] ?? [];

            if (! isset($properties['mapbox_id'], $properties['name'], $coordinates['latitude'], $coordinates['longitude'])) {
                continue;
            }

            $matches[] = [
                'id' => (string) $properties['mapbox_id'],
                'name' => (string) $properties['name'],
                'address' => (string) ($properties['full_address'] ?? $properties['place_formatted'] ?? ''),
                'latitude' => (float) $coordinates['latitude'],
                'longitude' => (float) $coordinates['longitude'],
            ];
        }

        return $matches;
    }
}

<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Asks TypeSafe AI's Jev model which emoji fits a sub-category best, with one
 * choice question over the curated candidates in EmojiSuggestionCandidates.
 */
class EmojiSuggester
{
    private const URL = 'https://api.typesafe.ai/v1/systemone';

    /**
     * The option Jev picks when none of the emoji fit the name.
     */
    public const NONE = 'none';

    /**
     * Suggest an emoji for a sub-category's name, and the name of the
     * category it is in when there is one. Returns null, rather than failing,
     * when there is no API key, Jev errors or times out, or Jev is not
     * confident enough, so the forms carry on as normal without a suggestion.
     */
    public function suggest(string $name, ?string $categoryName = null): ?string
    {
        $key = config('services.typesafe.key');

        if (! is_string($key) || $key === '') {
            Log::warning('No TypeSafe API key is set, so no emoji is suggested. Set TYPESAFE_API_KEY.');

            return null;
        }

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->timeout(config()->float('services.typesafe.timeout'))
                ->post(self::URL, [
                    'model' => config()->string('services.typesafe.model'),
                    'state' => array_filter([
                        'sub_category' => $name,
                        'category' => $categoryName,
                    ], fn (?string $value): bool => $value !== null && $value !== ''),
                    'questions' => [
                        'emoji' => [
                            'type' => 'choice',
                            'instructions' => 'Which emoji best represents this sub-category of a personal calendar, given its name and the category it belongs to? Choose "none" if no emoji fits the name.',
                            'criteria' => [
                                ...EmojiSuggestionCandidates::ALL,
                                self::NONE => 'None of these emoji fit the sub-category, or the name is not meaningful',
                            ],
                        ],
                    ],
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            report($exception);

            return null;
        }

        $choice = $response->json('answers.emoji.choice');
        $confidence = $response->json('answers.emoji.confidence');

        if (! is_string($choice) || ! array_key_exists($choice, EmojiSuggestionCandidates::ALL)) {
            return null;
        }

        if (! is_numeric($confidence) || (float) $confidence < config()->float('services.typesafe.emoji_suggestion_confidence')) {
            return null;
        }

        return $choice;
    }
}

<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Asks TypeSafe AI's Jev model one choice question, for the suggesters.
 */
class Jev
{
    private const URL = 'https://api.typesafe.ai/v1/systemone';

    /**
     * Whether a TypeSafe API key is set, so suggestions are only asked for
     * when there can be one.
     */
    public static function isEnabled(): bool
    {
        $key = config('services.typesafe.key');

        return is_string($key) && $key !== '';
    }

    /**
     * Ask one choice question about the state, with the criteria as the
     * options. Returns Jev's choice and its confidence from 0 to 1, or null,
     * rather than failing, when there is no API key, Jev errors or times out,
     * or the answer is malformed. The choice is not checked against the
     * criteria, so callers must do that.
     *
     * @param  array<string, mixed>  $state
     * @param  array<array-key, mixed>  $criteria
     * @return array{choice: string, confidence: float}|null
     */
    public function choose(string $question, array $state, string $instructions, array $criteria): ?array
    {
        if (! self::isEnabled()) {
            return null;
        }

        try {
            $response = Http::withToken(config()->string('services.typesafe.key'))
                ->acceptJson()
                ->timeout(config()->float('services.typesafe.timeout'))
                ->post(self::URL, [
                    'model' => config()->string('services.typesafe.model'),
                    'state' => $state,
                    'questions' => [
                        $question => [
                            'type' => 'choice',
                            'instructions' => $instructions,
                            'criteria' => $criteria,
                        ],
                    ],
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            report($exception);

            return null;
        }

        $choice = $response->json("answers.{$question}.choice");
        $confidence = $response->json("answers.{$question}.confidence");

        if (! is_string($choice) || ! is_numeric($confidence)) {
            return null;
        }

        return ['choice' => $choice, 'confidence' => (float) $confidence];
    }
}

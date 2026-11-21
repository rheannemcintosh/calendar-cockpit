<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * When something was created and last changed, as people read it.
 *
 * @phpstan-type TimestampLabels array{created: string, updated: string|null}
 */
final class Timestamps
{
    /**
     * When the model was created and last updated, e.g. "21 Nov 2026, 14:05",
     * in the given timezone. Updated is null when it has not been changed
     * since it was created.
     *
     * @return TimestampLabels
     */
    public static function of(Model $model, string $timezone): array
    {
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return [
            'created' => $createdAt->setTimezone($timezone)->format('j M Y, H:i'),
            'updated' => $updatedAt !== null && $updatedAt->ne($createdAt)
                ? $updatedAt->setTimezone($timezone)->format('j M Y, H:i')
                : null,
        ];
    }
}

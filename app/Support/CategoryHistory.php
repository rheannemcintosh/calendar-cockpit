<?php

namespace App\Support;

use App\Models\Category;
use Carbon\CarbonImmutable;
use Spatie\Activitylog\Models\Activity;

/**
 * The changes made to a category, from its activity log, as people read them.
 *
 * @phpstan-type HistoryChange array{field: string, label: string, old: string|null, new: string|null}
 * @phpstan-type HistoryEntry array{key: string, event: 'created'|'updated', at: string, datetime: string, changes: list<HistoryChange>}
 */
final class CategoryHistory
{
    /**
     * The fields shown in a change, in the order the form has them, with the
     * names the app uses for them.
     */
    private const LABELS = [
        'name' => 'Name',
        'colour' => 'Colour',
    ];

    /**
     * Each change made to the category, newest first, ending with when it was
     * created. Each update lists the fields that changed with their old and
     * new values, and when it was made is shown in the given timezone, e.g.
     * "21 Nov 2026, 14:05". A category made before changes were logged still
     * ends with when it was created.
     *
     * @return list<HistoryEntry>
     */
    public static function of(Category $category, string $timezone): array
    {
        $activities = Activity::query()
            ->inLog('category')
            ->whereMorphedTo('subject', $category)
            ->whereIn('event', ['created', 'updated'])
            ->orderByDesc('id')
            ->get();

        $entries = [];
        $created = false;

        foreach ($activities as $activity) {
            $at = CarbonImmutable::instance($activity->created_at)->setTimezone($timezone);

            if ($activity->event === 'created') {
                $created = true;
                $entries[] = self::entry('activity-'.$activity->id, 'created', $at, []);

                continue;
            }

            /** @var array<string, mixed> $new */
            $new = $activity->attribute_changes['attributes'] ?? [];
            /** @var array<string, mixed> $old */
            $old = $activity->attribute_changes['old'] ?? [];

            $changes = [];

            foreach (self::LABELS as $field => $label) {
                if (! array_key_exists($field, $new)) {
                    continue;
                }

                $changes[] = [
                    'field' => $field,
                    'label' => $label,
                    'old' => self::string($old[$field] ?? null),
                    'new' => self::string($new[$field]),
                ];
            }

            $entries[] = self::entry('activity-'.$activity->id, 'updated', $at, $changes);
        }

        if (! $created && $category->created_at !== null) {
            $entries[] = self::entry('created', 'created', $category->created_at->setTimezone($timezone), []);
        }

        return $entries;
    }

    /**
     * @param  list<HistoryChange>  $changes
     * @param  'created'|'updated'  $event
     * @return HistoryEntry
     */
    private static function entry(string $key, string $event, CarbonImmutable $at, array $changes): array
    {
        return [
            'key' => $key,
            'event' => $event,
            'at' => $at->format('j M Y, H:i'),
            'datetime' => $at->toIso8601String(),
            'changes' => $changes,
        ];
    }

    /**
     * A logged value as text, or null when there is none.
     */
    private static function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}

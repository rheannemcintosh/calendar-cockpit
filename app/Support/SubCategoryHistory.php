<?php

namespace App\Support;

use App\Enums\TimeType;
use App\Models\Category;
use App\Models\SubCategory;
use Carbon\CarbonImmutable;
use Spatie\Activitylog\Models\Activity;

/**
 * The changes made to a sub-category, from its activity log, as people read them.
 *
 * @phpstan-type HistoryChange array{field: string, label: string, old: string|null, new: string|null}
 * @phpstan-type HistoryEntry array{key: string, event: 'created'|'updated', at: string, datetime: string, changes: list<HistoryChange>}
 */
final class SubCategoryHistory
{
    /**
     * The fields shown in a change, in the order the form has them, with the
     * names the app uses for them.
     */
    private const LABELS = [
        'category_id' => 'Category',
        'name' => 'Name',
        'emoji' => 'Emoji',
        'default_time_type' => 'Default time type',
    ];

    /**
     * Each change made to the sub-category, newest first, ending with when it
     * was created. Each update lists the fields that changed with their old and
     * new values, and when it was made is shown in the given timezone, e.g.
     * "21 Nov 2026, 14:05". A sub-category made before changes were logged
     * still ends with when it was created.
     *
     * @return list<HistoryEntry>
     */
    public static function of(SubCategory $subCategory, string $timezone): array
    {
        $activities = Activity::query()
            ->inLog('sub_category')
            ->whereMorphedTo('subject', $subCategory)
            ->whereIn('event', ['created', 'updated'])
            ->orderByDesc('id')
            ->get();

        $categoryNames = self::categoryNames($activities->all());

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
                    'old' => self::value($field, $old[$field] ?? null, $categoryNames),
                    'new' => self::value($field, $new[$field], $categoryNames),
                ];
            }

            $entries[] = self::entry('activity-'.$activity->id, 'updated', $at, $changes);
        }

        if (! $created && $subCategory->created_at !== null) {
            $entries[] = self::entry('created', 'created', $subCategory->created_at->setTimezone($timezone), []);
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
     * A logged value as the app shows it, or null when there is none. A
     * category shows its name and a default time type its label.
     *
     * @param  array<int, string>  $categoryNames
     */
    private static function value(string $field, mixed $value, array $categoryNames): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = (string) $value;

        return match ($field) {
            'category_id' => $categoryNames[(int) $value] ?? __('A deleted category'),
            'default_time_type' => TimeType::tryFrom($value)?->label() ?? $value,
            default => $value,
        };
    }

    /**
     * The name of every category the sub-category has been in, by ID. A
     * deleted category's name is the one it had when it was deleted.
     *
     * @param  array<int, Activity>  $activities
     * @return array<int, string>
     */
    private static function categoryNames(array $activities): array
    {
        $ids = [];

        foreach ($activities as $activity) {
            foreach (['old', 'attributes'] as $side) {
                $id = $activity->attribute_changes[$side]['category_id'] ?? null;

                if ($id !== null) {
                    $ids[(int) $id] = true;
                }
            }
        }

        if ($ids === []) {
            return [];
        }

        $names = [];

        foreach (Category::query()->whereIn('id', array_keys($ids))->get(['id', 'name']) as $category) {
            $names[$category->id] = $category->name;
        }

        $deleted = Activity::query()
            ->inLog('category')
            ->where('event', 'deleted')
            ->where('subject_type', (new Category)->getMorphClass())
            ->whereIn('subject_id', array_keys(array_diff_key($ids, $names)))
            ->get();

        foreach ($deleted as $activity) {
            $name = $activity->attribute_changes['old']['name'] ?? null;

            if (is_string($name)) {
                $names[(int) $activity->subject_id] ??= $name;
            }
        }

        return $names;
    }
}

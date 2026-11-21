<?php

namespace App\Support;

use App\Enums\Firmness;
use App\Enums\TimeType;
use App\Models\CalendarItem;
use App\Models\SubCategory;
use Carbon\CarbonImmutable;
use Spatie\Activitylog\Models\Activity;

/**
 * The changes made to a calendar item, from its activity log, as people read them.
 *
 * @phpstan-type HistoryChange array{field: string, label: string, old: string|null, new: string|null}
 * @phpstan-type HistoryEntry array{key: string, event: 'created'|'updated', at: string, datetime: string, changes: list<HistoryChange>}
 */
final class CalendarItemHistory
{
    /**
     * The fields shown in a change, in the order the form has them, with the
     * names the app uses for them. The location's fields show as one.
     */
    private const LABELS = [
        'title' => 'Title',
        'starts_at' => 'Starts',
        'ends_at' => 'Ends',
        'all_day' => 'All day',
        'sub_category_id' => 'Sub-category',
        'time_type' => 'Time type',
        'time_type_overridden' => 'Time type chosen by you',
        'firmness' => 'Firmness',
        'location' => 'Location',
    ];

    private const LOCATION_FIELDS = ['location_name', 'location_address', 'location_latitude', 'location_longitude'];

    /**
     * Each change made to the calendar item, newest first, ending with when it
     * was created. Each update lists the fields that changed with their old and
     * new values, and when it was made is shown in the given timezone, e.g.
     * "21 Nov 2026, 14:05". A calendar item made before changes were logged
     * still ends with when it was created.
     *
     * @return list<HistoryEntry>
     */
    public static function of(CalendarItem $calendarItem, string $timezone): array
    {
        $activities = Activity::query()
            ->inLog('calendar_item')
            ->whereMorphedTo('subject', $calendarItem)
            ->whereIn('event', ['created', 'updated'])
            ->orderByDesc('id')
            ->get();

        $subCategoryNames = self::subCategoryNames($activities->all());

        // Walking back from how it is now, what it was like after each change.
        $state = [
            'all_day' => $calendarItem->all_day,
            'location_name' => $calendarItem->location_name,
            'location_address' => $calendarItem->location_address,
        ];

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

            $after = $state;
            $before = [...$state, ...array_intersect_key($old, $state)];
            $state = $before;

            $changes = [];

            foreach (array_keys(self::LABELS) as $field) {
                if ($field === 'location') {
                    if (array_intersect(self::LOCATION_FIELDS, array_keys($new)) !== []) {
                        $changes[] = self::change($field, self::location($before), self::location($after));
                    }

                    continue;
                }

                if (! array_key_exists($field, $new)) {
                    continue;
                }

                $changes[] = self::change(
                    $field,
                    self::value($field, $old[$field] ?? null, (bool) $before['all_day'], $subCategoryNames),
                    self::value($field, $new[$field], (bool) $after['all_day'], $subCategoryNames),
                );
            }

            $entries[] = self::entry('activity-'.$activity->id, 'updated', $at, $changes);
        }

        if (! $created && $calendarItem->created_at !== null) {
            $entries[] = self::entry('created', 'created', $calendarItem->created_at->setTimezone($timezone), []);
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
     * @return HistoryChange
     */
    private static function change(string $field, ?string $old, ?string $new): array
    {
        return ['field' => $field, 'label' => self::LABELS[$field], 'old' => $old, 'new' => $new];
    }

    /**
     * A logged value as the app shows it, or null when there is none. Times are
     * the ones the user typed in, so they are shown as they are stored. An
     * all-day calendar item shows only its dates, and ends at the midnight
     * after its last day, so that day is shown.
     *
     * @param  array<int, string>  $subCategoryNames
     */
    private static function value(string $field, mixed $value, bool $allDay, array $subCategoryNames): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($field) {
            'starts_at' => $allDay
                ? CarbonImmutable::parse(self::string($value))->format('j M Y')
                : CarbonImmutable::parse(self::string($value))->format('j M Y, H:i'),
            'ends_at' => $allDay
                ? CarbonImmutable::parse(self::string($value))->subDay()->format('j M Y')
                : CarbonImmutable::parse(self::string($value))->format('j M Y, H:i'),
            'all_day', 'time_type_overridden' => $value ? __('Yes') : __('No'),
            'sub_category_id' => $subCategoryNames[(int) self::string($value)] ?? __('A deleted sub-category'),
            'time_type' => TimeType::tryFrom(self::string($value))?->label() ?? self::string($value),
            'firmness' => Firmness::tryFrom(self::string($value))?->label() ?? self::string($value),
            default => self::string($value),
        };
    }

    private static function string(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * A location's name and address, as one, or null when there is none.
     *
     * @param  array<string, mixed>  $state
     */
    private static function location(array $state): ?string
    {
        if ($state['location_name'] === null) {
            return null;
        }

        return implode(', ', array_filter([self::string($state['location_name']), self::string($state['location_address'])], fn (string $part): bool => $part !== ''));
    }

    /**
     * The name of every sub-category the calendar item has been in, by ID. A
     * deleted sub-category's name is the one it had when it was deleted.
     *
     * @param  array<int, Activity>  $activities
     * @return array<int, string>
     */
    private static function subCategoryNames(array $activities): array
    {
        $ids = [];

        foreach ($activities as $activity) {
            foreach (['old', 'attributes'] as $side) {
                $id = $activity->attribute_changes[$side]['sub_category_id'] ?? null;

                if ($id !== null) {
                    $ids[(int) $id] = true;
                }
            }
        }

        if ($ids === []) {
            return [];
        }

        $names = [];

        foreach (SubCategory::query()->whereIn('id', array_keys($ids))->get() as $subCategory) {
            $names[$subCategory->id] = $subCategory->name;
        }

        $deleted = Activity::query()
            ->inLog('sub_category')
            ->where('event', 'deleted')
            ->where('subject_type', (new SubCategory)->getMorphClass())
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

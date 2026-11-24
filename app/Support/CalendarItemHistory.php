<?php

namespace App\Support;

use App\Enums\Firmness;
use App\Enums\TimeType;
use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use Carbon\CarbonImmutable;
use Spatie\Activitylog\Models\Activity;

/**
 * The changes made to a calendar item, from its activity log, as people read them.
 *
 * A change can come from the calendar item's category or sub-category rather
 * than from the calendar item itself, which its source says.
 *
 * @phpstan-type HistoryChange array{field: string, label: string, old: string|null, new: string|null}
 * @phpstan-type HistorySource array{type: 'category'|'sub_category', action: 'updated'|'deleted', name: string}
 * @phpstan-type HistoryEntry array{key: string, event: 'created'|'updated', at: string, datetime: string, changes: list<HistoryChange>, source: HistorySource|null}
 * @phpstan-type Span array{id: int, from: int, to: int|null}
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
        'minutes_before' => 'Time before',
        'minutes_after' => 'Time after',
        'sub_category_id' => 'Sub-category',
        'time_type' => 'Time type',
        'time_type_overridden' => 'Time type chosen by you',
        'firmness' => 'Firmness',
        'location' => 'Location',
    ];

    /**
     * The fields of a sub-category or category shown when a change to it
     * changed what the calendar item is filed under, with the names the app
     * uses for them. How they look, such as their emoji or colour, is left out.
     */
    private const SUB_CATEGORY_LABELS = [
        'category_id' => 'Category',
        'name' => 'Name',
    ];

    private const CATEGORY_LABELS = [
        'name' => 'Name',
    ];

    private const LOCATION_FIELDS = ['location_name', 'location_address', 'location_latitude', 'location_longitude'];

    /**
     * Each change made to the calendar item, newest first, ending with when it
     * was created. Each update lists the fields that changed with their old and
     * new values, and when it was made is shown in the given timezone, e.g.
     * "21 Nov 2026, 14:05". A calendar item made before changes were logged
     * still ends with when it was created.
     *
     * Renaming its sub-category or that sub-category's category, or moving
     * the sub-category to another category, shows too, but only while the
     * calendar item was in them, as does it becoming uncategorised because
     * one of them was deleted.
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
        $subCategorySpans = self::subCategorySpans($calendarItem, $activities->all());

        // Walking back from how it is now, what it was like after each change.
        $state = [
            'all_day' => $calendarItem->all_day,
            'location_name' => $calendarItem->location_name,
            'location_address' => $calendarItem->location_address,
        ];

        // By activity ID, so the calendar item's own changes and those that
        // came from its category or sub-category are in the order they were made.
        $entries = self::subCategoryEntries($subCategorySpans, $timezone);
        $created = false;

        foreach ($activities as $activity) {
            $at = CarbonImmutable::instance($activity->created_at)->setTimezone($timezone);

            if ($activity->event === 'created') {
                $created = true;
                $entries[$activity->id] = self::entry('activity-'.$activity->id, 'created', $at, []);

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

            $entries[$activity->id] = self::entry('activity-'.$activity->id, 'updated', $at, $changes, self::deletedSource($activity));
        }

        krsort($entries);
        $entries = array_values($entries);

        if (! $created && $calendarItem->created_at !== null) {
            $entries[] = self::entry('created', 'created', $calendarItem->created_at->setTimezone($timezone), []);
        }

        return $entries;
    }

    /**
     * @param  list<HistoryChange>  $changes
     * @param  'created'|'updated'  $event
     * @param  HistorySource|null  $source
     * @return HistoryEntry
     */
    private static function entry(string $key, string $event, CarbonImmutable $at, array $changes, ?array $source = null): array
    {
        return [
            'key' => $key,
            'event' => $event,
            'at' => $at->format('j M Y, H:i'),
            'datetime' => $at->toIso8601String(),
            'changes' => $changes,
            'source' => $source,
        ];
    }

    /**
     * What was deleted when the calendar item became uncategorised because
     * its category or sub-category was deleted, or null for any other change.
     *
     * @return HistorySource|null
     */
    private static function deletedSource(Activity $activity): ?array
    {
        $deleted = $activity->properties['deleted'] ?? null;

        if (! is_array($deleted) || ! in_array($deleted['type'] ?? null, ['category', 'sub_category'], true)) {
            return null;
        }

        return ['type' => $deleted['type'], 'action' => 'deleted', 'name' => self::string($deleted['name'] ?? null)];
    }

    /**
     * The sub-categories the calendar item has been in, each with the
     * activity IDs it went in and came out at, oldest first. It came out at
     * null if it is still in it, and went in at 0 if that was before changes
     * were logged.
     *
     * @param  array<int, Activity>  $activities  newest first
     * @return list<Span>
     */
    private static function subCategorySpans(CalendarItem $calendarItem, array $activities): array
    {
        $moves = array_values(array_filter(
            array_reverse($activities),
            fn (Activity $activity): bool => $activity->event === 'updated'
                && array_key_exists('sub_category_id', $activity->attribute_changes['attributes'] ?? []),
        ));

        $created = array_values(array_filter($activities, fn (Activity $activity): bool => $activity->event === 'created'));

        $current = $moves === [] ? $calendarItem->sub_category_id : ($moves[0]->attribute_changes['old']['sub_category_id'] ?? null);
        $from = $created === [] ? 0 : $created[0]->id;

        $spans = [];

        foreach ($moves as $move) {
            if ($current !== null) {
                $spans[] = ['id' => (int) $current, 'from' => $from, 'to' => $move->id];
            }

            $current = $move->attribute_changes['attributes']['sub_category_id'] ?? null;
            $from = $move->id;
        }

        if ($current !== null) {
            $spans[] = ['id' => (int) $current, 'from' => $from, 'to' => null];
        }

        return $spans;
    }

    /**
     * The changes to the calendar item's sub-categories, and to their
     * categories, made while it was in them, by activity ID.
     *
     * @param  list<Span>  $subCategorySpans
     * @return array<int, HistoryEntry>
     */
    private static function subCategoryEntries(array $subCategorySpans, string $timezone): array
    {
        if ($subCategorySpans === []) {
            return [];
        }

        $subCategoryActivities = Activity::query()
            ->inLog('sub_category')
            ->where('subject_type', (new SubCategory)->getMorphClass())
            ->whereIn('subject_id', array_unique(array_column($subCategorySpans, 'id')))
            ->orderBy('id')
            ->get()
            ->groupBy('subject_id');

        $categorySpans = [];
        $entries = [];
        $categoryNames = self::categoryNames($subCategoryActivities->flatten()->all());

        foreach ($subCategorySpans as $span) {
            /** @var array<int, Activity> $logged */
            $logged = $subCategoryActivities->get($span['id'])?->all() ?? [];

            $categoryId = self::initialCategoryId($span['id'], $logged);
            $from = $span['from'];

            foreach ($logged as $activity) {
                if ($activity->event !== 'updated' || $activity->id <= $span['from'] || ($span['to'] !== null && $activity->id >= $span['to'])) {
                    continue;
                }

                /** @var array<string, mixed> $new */
                $new = $activity->attribute_changes['attributes'] ?? [];
                /** @var array<string, mixed> $old */
                $old = $activity->attribute_changes['old'] ?? [];

                if (array_key_exists('category_id', $new)) {
                    if ($categoryId !== null) {
                        $categorySpans[] = ['id' => $categoryId, 'from' => $from, 'to' => $activity->id];
                    }

                    $categoryId = is_scalar($new['category_id']) ? (int) $new['category_id'] : null;
                    $from = $activity->id;
                }

                $changes = [];

                foreach (self::SUB_CATEGORY_LABELS as $field => $label) {
                    if (array_key_exists($field, $new)) {
                        $changes[] = [
                            'field' => $field,
                            'label' => $label,
                            'old' => $field === 'category_id' ? self::categoryName($old[$field] ?? null, $categoryNames) : self::nullableString($old[$field] ?? null),
                            'new' => $field === 'category_id' ? self::categoryName($new[$field], $categoryNames) : self::nullableString($new[$field]),
                        ];
                    }
                }

                // A change to only its emoji or default time type leaves what the calendar item is filed under as it was.
                if ($changes !== []) {
                    $entries[$activity->id] = self::entry(
                        'activity-'.$activity->id,
                        'updated',
                        CarbonImmutable::instance($activity->created_at)->setTimezone($timezone),
                        $changes,
                        ['type' => 'sub_category', 'action' => 'updated', 'name' => self::string($new['name'] ?? self::nameAt($logged, $activity->id))],
                    );
                }
            }

            if ($categoryId !== null) {
                $categorySpans[] = ['id' => $categoryId, 'from' => $from, 'to' => $span['to']];
            }
        }

        return $entries + self::categoryEntries($categorySpans, $timezone);
    }

    /**
     * The changes to the calendar item's categories made while it was in
     * them, by activity ID.
     *
     * @param  list<Span>  $categorySpans
     * @return array<int, HistoryEntry>
     */
    private static function categoryEntries(array $categorySpans, string $timezone): array
    {
        if ($categorySpans === []) {
            return [];
        }

        $categoryActivities = Activity::query()
            ->inLog('category')
            ->where('subject_type', (new Category)->getMorphClass())
            ->whereIn('subject_id', array_unique(array_column($categorySpans, 'id')))
            ->orderBy('id')
            ->get()
            ->groupBy('subject_id');

        $entries = [];

        foreach ($categorySpans as $span) {
            /** @var array<int, Activity> $logged */
            $logged = $categoryActivities->get($span['id'])?->all() ?? [];

            foreach ($logged as $activity) {
                if ($activity->event !== 'updated' || $activity->id <= $span['from'] || ($span['to'] !== null && $activity->id >= $span['to'])) {
                    continue;
                }

                /** @var array<string, mixed> $new */
                $new = $activity->attribute_changes['attributes'] ?? [];
                /** @var array<string, mixed> $old */
                $old = $activity->attribute_changes['old'] ?? [];

                $changes = [];

                foreach (self::CATEGORY_LABELS as $field => $label) {
                    if (array_key_exists($field, $new)) {
                        $changes[] = ['field' => $field, 'label' => $label, 'old' => self::nullableString($old[$field] ?? null), 'new' => self::nullableString($new[$field])];
                    }
                }

                if ($changes !== []) {
                    $entries[$activity->id] = self::entry(
                        'activity-'.$activity->id,
                        'updated',
                        CarbonImmutable::instance($activity->created_at)->setTimezone($timezone),
                        $changes,
                        ['type' => 'category', 'action' => 'updated', 'name' => self::string($new['name'] ?? self::nameAt($logged, $activity->id))],
                    );
                }
            }
        }

        return $entries;
    }

    /**
     * The category a sub-category was in before any of its logged moves: the
     * one it left first, or else the one it was created in, or else the one
     * it is in now, or was in when it was deleted.
     *
     * @param  array<int, Activity>  $logged  oldest first
     */
    private static function initialCategoryId(int $subCategoryId, array $logged): ?int
    {
        foreach ($logged as $activity) {
            if ($activity->event === 'updated' && array_key_exists('category_id', $activity->attribute_changes['attributes'] ?? [])) {
                return self::id($activity->attribute_changes['old']['category_id'] ?? null);
            }
        }

        foreach ($logged as $activity) {
            if ($activity->event === 'created') {
                return self::id($activity->attribute_changes['attributes']['category_id'] ?? null);
            }
        }

        $categoryId = SubCategory::query()->whereKey($subCategoryId)->value('category_id');

        if ($categoryId !== null) {
            return self::id($categoryId);
        }

        foreach ($logged as $activity) {
            if ($activity->event === 'deleted') {
                return self::id($activity->attribute_changes['old']['category_id'] ?? null);
            }
        }

        return null;
    }

    /**
     * The name a sub-category or category had just after the given activity,
     * from the logged changes to it, oldest first, or the one it has now.
     *
     * @param  array<int, Activity>  $logged
     */
    private static function nameAt(array $logged, int $activityId): ?string
    {
        foreach ($logged as $activity) {
            if ($activity->id > $activityId && array_key_exists('name', $activity->attribute_changes['old'] ?? [])) {
                return self::nullableString($activity->attribute_changes['old']['name']);
            }
        }

        $subject = $logged === [] ? null : $logged[0]->subject;

        return $subject?->getAttribute('name') ?? null;
    }

    private static function id(mixed $value): ?int
    {
        return is_scalar($value) && $value !== '' ? (int) $value : null;
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * @param  array<int, string>  $categoryNames
     */
    private static function categoryName(mixed $value, array $categoryNames): ?string
    {
        $id = self::id($value);

        if ($id === null) {
            return null;
        }

        return $categoryNames[$id] ?? __('A deleted category');
    }

    /**
     * The name of every category the given sub-category changes mention, by
     * ID. A deleted category's name is the one it had when it was deleted.
     *
     * @param  array<int, Activity>  $activities
     * @return array<int, string>
     */
    private static function categoryNames(array $activities): array
    {
        $ids = [];

        foreach ($activities as $activity) {
            foreach (['old', 'attributes'] as $side) {
                $id = self::id($activity->attribute_changes[$side]['category_id'] ?? null);

                if ($id !== null) {
                    $ids[$id] = true;
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
            'minutes_before', 'minutes_after' => self::minutes((int) self::string($value)),
            'sub_category_id' => $subCategoryNames[(int) self::string($value)] ?? __('A deleted sub-category'),
            'time_type' => TimeType::tryFrom(self::string($value))?->label() ?? self::string($value),
            'firmness' => Firmness::tryFrom(self::string($value))?->label() ?? self::string($value),
            default => self::string($value),
        };
    }

    /**
     * Time before or after as the app shows it, e.g. "30 min", "1 hr" or
     * "2 hr 30 min", or "None" when there is none.
     */
    private static function minutes(int $minutes): string
    {
        if ($minutes === 0) {
            return __('None');
        }

        $hours = intdiv($minutes, 60);
        $minutes %= 60;

        return match (true) {
            $hours === 0 => "{$minutes} min",
            $minutes === 0 => "{$hours} hr",
            default => "{$hours} hr {$minutes} min",
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

        // Deleting a category deletes its sub-categories without logging it,
        // so becoming uncategorised then saved the sub-category's name.
        foreach ($activities as $activity) {
            $id = $activity->attribute_changes['old']['sub_category_id'] ?? null;
            $name = $activity->properties['sub_category_name'] ?? null;

            if ($id !== null && is_string($name)) {
                $names[(int) $id] ??= $name;
            }
        }

        return $names;
    }
}

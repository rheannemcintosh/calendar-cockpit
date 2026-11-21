<?php

namespace App\Support;

use App\Enums\Firmness;
use App\Enums\FlagIcon;
use App\Enums\TimeType;
use App\Models\CalendarItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Places a day's calendar items on a timeline of that day, to the minute.
 *
 * The time before and after a calendar item is joined to it, from
 * `beforeMinute` to `startMinute` and from `endMinute` to `afterMinute`, and
 * is part of it when working out which calendar items overlap.
 *
 * Calendar items that overlap share the width of the timeline side by side.
 * Each one goes in the leftmost column that is free when it starts, and every
 * item in a group of overlapping items has the same number of columns.
 * All-day calendar items aren't at a time, so they go above the timeline.
 *
 * @phpstan-import-type TimelineItem from CalendarItemTimeline
 *
 * @phpstan-type DayItem array{id: int, title: string, allDay: bool, time: string, timeBefore: array{minutes: int, from: string}|null, timeAfter: array{minutes: int, until: string}|null, timeType: value-of<TimeType>, firmness: value-of<Firmness>, flags: list<array{name: string, icon: value-of<FlagIcon>}>, category: array{name: string, colour: string}|null, subCategory: array{emoji: string, name: string}|null, location: array{name: string, address: string|null, mapsUrl: string}|null, beforeMinute: int, startMinute: int, endMinute: int, afterMinute: int, column: int, columns: int}
 */
class CalendarItemDay
{
    public const MINUTES_IN_DAY = 1440;

    /**
     * Place the timed calendar items, which must already be in start order, on
     * the given day. A part of an item that falls outside the day is cut off.
     * All-day calendar items are left out, for allDay(). When each one was
     * created and last updated is shown in the given timezone.
     *
     * @param  Collection<int, CalendarItem>  $calendarItems
     * @return list<DayItem>
     */
    public static function place(Collection $calendarItems, CarbonInterface $day, string $timezone): array
    {
        $dayStart = $day->copy()->startOfDay();
        $calendarItems = $calendarItems->reject(fn (CalendarItem $calendarItem): bool => $calendarItem->all_day)->values();

        $minutes = [];
        $withTimeAround = [];

        foreach ($calendarItems as $calendarItem) {
            $minutes[] = [
                static::minuteOfDay($dayStart, $calendarItem->starts_at),
                static::minuteOfDay($dayStart, $calendarItem->ends_at, roundUp: true),
            ];
            $withTimeAround[] = [
                static::minuteOfDay($dayStart, $calendarItem->startsWithTimeBefore()),
                static::minuteOfDay($dayStart, $calendarItem->endsWithTimeAfter(), roundUp: true),
            ];
        }

        $columns = static::columns($withTimeAround);
        $items = [];

        foreach ($calendarItems as $index => $calendarItem) {
            $item = CalendarItemTimeline::item($calendarItem, $timezone);

            $items[] = [
                ...$item,
                'beforeMinute' => $withTimeAround[$index][0],
                'startMinute' => $minutes[$index][0],
                'endMinute' => $minutes[$index][1],
                'afterMinute' => $withTimeAround[$index][1],
                'column' => $columns[$index][0],
                'columns' => $columns[$index][1],
            ];
        }

        return $items;
    }

    /**
     * The all-day calendar items, in the order they are given, for the row
     * above the timeline. When each one was created and last updated is
     * shown in the given timezone.
     *
     * @param  Collection<int, CalendarItem>  $calendarItems
     * @return list<TimelineItem>
     */
    public static function allDay(Collection $calendarItems, string $timezone): array
    {
        $items = [];

        foreach ($calendarItems as $calendarItem) {
            if ($calendarItem->all_day) {
                $items[] = CalendarItemTimeline::item($calendarItem, $timezone);
            }
        }

        return $items;
    }

    /**
     * The column each calendar item goes in and how many columns its group of
     * overlapping calendar items has, from their start and end minutes.
     *
     * @param  list<array{int, int}>  $minutes
     * @return list<array{int, int}>
     */
    public static function columns(array $minutes): array
    {
        $column = [];
        $group = [];
        $groupColumns = [0];
        $columnEnds = [];
        $groupEnd = 0;

        foreach ($minutes as [$start, $end]) {
            if ($column !== [] && $start >= $groupEnd) {
                $groupColumns[] = 0;
                $columnEnds = [];
                $groupEnd = 0;
            }

            $free = 0;

            while (isset($columnEnds[$free]) && $columnEnds[$free] > $start) {
                $free++;
            }

            $columnEnds[$free] = $end;
            $groupEnd = max($groupEnd, $end);
            $current = count($groupColumns) - 1;
            $groupColumns[$current] = max($groupColumns[$current], $free + 1);
            $column[] = $free;
            $group[] = $current;
        }

        return array_map(
            fn (int $free, int $current): array => [$free, $groupColumns[$current]],
            $column,
            $group,
        );
    }

    /**
     * The minute of the day a moment falls on, from 0 at midnight to 1440 at
     * the next midnight, kept within the day. A start rounds down and an end
     * rounds up, so a calendar item of under a minute still covers one.
     */
    public static function minuteOfDay(CarbonInterface $dayStart, CarbonInterface $moment, bool $roundUp = false): int
    {
        $minutes = $dayStart->diffInMinutes($moment);
        $minutes = (int) ($roundUp ? ceil($minutes) : floor($minutes));

        return max(0, min(self::MINUTES_IN_DAY, $minutes));
    }
}

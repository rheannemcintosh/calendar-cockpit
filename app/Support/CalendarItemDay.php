<?php

namespace App\Support;

use App\Enums\Firmness;
use App\Enums\TimeType;
use App\Models\CalendarItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Places a day's calendar items on a timeline of that day, to the minute.
 *
 * Calendar items that overlap share the width of the timeline side by side.
 * Each one goes in the leftmost column that is free when it starts, and every
 * item in a group of overlapping items has the same number of columns.
 *
 * @phpstan-type DayItem array{id: int, title: string, time: string, timeType: value-of<TimeType>, firmness: value-of<Firmness>, category: array{name: string, colour: string}|null, subCategory: array{emoji: string, name: string}|null, location: array{name: string, address: string|null, mapsUrl: string}|null, startMinute: int, endMinute: int, column: int, columns: int}
 */
class CalendarItemDay
{
    public const MINUTES_IN_DAY = 1440;

    /**
     * Place calendar items, which must already be in start order, on the given
     * day. A part of an item that falls outside the day is cut off.
     *
     * @param  Collection<int, CalendarItem>  $calendarItems
     * @return list<DayItem>
     */
    public static function place(Collection $calendarItems, CarbonInterface $day): array
    {
        $dayStart = $day->copy()->startOfDay();
        $calendarItems = $calendarItems->values();

        $minutes = [];

        foreach ($calendarItems as $calendarItem) {
            $minutes[] = [
                static::minuteOfDay($dayStart, $calendarItem->starts_at),
                static::minuteOfDay($dayStart, $calendarItem->ends_at, roundUp: true),
            ];
        }

        $columns = static::columns($minutes);
        $items = [];

        foreach ($calendarItems as $index => $calendarItem) {
            $item = CalendarItemTimeline::item($calendarItem);

            $items[] = [
                ...$item,
                'startMinute' => $minutes[$index][0],
                'endMinute' => $minutes[$index][1],
                'column' => $columns[$index][0],
                'columns' => $columns[$index][1],
            ];
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

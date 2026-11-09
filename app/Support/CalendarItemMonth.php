<?php

namespace App\Support;

use App\Enums\TimeType;
use App\Models\CalendarItem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Lays a month's calendar items out on a grid of its weeks, Sunday to
 * Saturday, from the Sunday on or before the 1st to the Saturday on or after
 * the last day. A calendar item that runs past midnight is on each day it
 * covers.
 *
 * @phpstan-type MonthItem array{id: int, title: string, time: string, timeType: value-of<TimeType>, category: array{name: string, colour: string}|null, subCategory: array{emoji: string, name: string}|null, location: array{name: string, address: string|null, mapsUrl: string}|null, startTime: string, continued: bool, minutes: int}
 * @phpstan-type MonthDay array{date: string, dayOfMonth: int, label: string, inMonth: bool, items: list<MonthItem>}
 */
class CalendarItemMonth
{
    /**
     * The Sunday the grid starts on for the month the given day is in.
     */
    public static function gridStart(CarbonInterface $day): CarbonImmutable
    {
        return CarbonImmutable::instance(CalendarItemTimeline::weekStart($day->copy()->startOfMonth()));
    }

    /**
     * The Saturday the grid ends on for the month the given day is in.
     */
    public static function gridEnd(CarbonInterface $day): CarbonImmutable
    {
        return CarbonImmutable::instance(CalendarItemTimeline::weekStart($day->copy()->endOfMonth()))->addDays(6);
    }

    /**
     * Lay calendar items, which must already be in start order, out on the
     * weeks of the month the given day is in. Each day has its calendar items
     * in start order, and says whether each one started on an earlier day.
     *
     * @param  Collection<int, CalendarItem>  $calendarItems
     * @return list<list<MonthDay>>
     */
    public static function weeks(Collection $calendarItems, CarbonInterface $day): array
    {
        $month = CarbonImmutable::instance($day)->startOfDay();
        $gridEnd = static::gridEnd($month);
        $weeks = [];

        for ($weekStart = static::gridStart($month); $weekStart <= $gridEnd; $weekStart = $weekStart->addWeek()) {
            $week = [];

            for ($offset = 0; $offset < 7; $offset++) {
                $date = $weekStart->addDays($offset);
                $next = $date->addDay();
                $items = [];

                foreach ($calendarItems as $calendarItem) {
                    if ($calendarItem->starts_at < $next && $calendarItem->ends_at > $date) {
                        $items[] = [
                            ...CalendarItemTimeline::item($calendarItem),
                            'startTime' => $calendarItem->starts_at->format('H:i'),
                            'continued' => $calendarItem->starts_at < $date,
                            // How long it is on this day, for its details.
                            'minutes' => CalendarItemDay::minuteOfDay($date, $calendarItem->ends_at, roundUp: true)
                                - CalendarItemDay::minuteOfDay($date, $calendarItem->starts_at),
                        ];
                    }
                }

                $week[] = [
                    'date' => $date->toDateString(),
                    'dayOfMonth' => $date->day,
                    'label' => $date->format('l j F Y'),
                    'inMonth' => $date->month === $month->month,
                    'items' => $items,
                ];
            }

            $weeks[] = $week;
        }

        return $weeks;
    }
}

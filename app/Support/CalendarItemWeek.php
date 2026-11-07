<?php

namespace App\Support;

use App\Models\CalendarItem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Places a week's calendar items on a timeline of each of its days, Sunday to
 * Saturday, to the minute. A calendar item that runs past midnight is on each
 * day it covers, cut off at the edges of each one.
 *
 * @phpstan-import-type DayItem from CalendarItemDay
 *
 * @phpstan-type WeekDay array{date: string, weekday: string, dayOfMonth: int, label: string, items: list<DayItem>}
 */
class CalendarItemWeek
{
    /**
     * Place calendar items, which must already be in start order, on the days
     * of the week that starts on the given Sunday.
     *
     * @param  Collection<int, CalendarItem>  $calendarItems
     * @return list<WeekDay>
     */
    public static function days(Collection $calendarItems, CarbonInterface $weekStart): array
    {
        $weekStart = CarbonImmutable::instance($weekStart)->startOfDay();
        $days = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $day = $weekStart->addDays($offset);
            $next = $day->addDay();

            $days[] = [
                'date' => $day->toDateString(),
                'weekday' => $day->format('D'),
                'dayOfMonth' => $day->day,
                'label' => $day->format('l j F Y'),
                'items' => CalendarItemDay::place(
                    $calendarItems->filter(fn (CalendarItem $calendarItem): bool => $calendarItem->starts_at < $next && $calendarItem->ends_at > $day),
                    $day,
                ),
            ];
        }

        return $days;
    }
}

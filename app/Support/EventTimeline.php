<?php

namespace App\Support;

use App\Models\Event;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Groups events into years, weeks and days for the events list.
 *
 * Weeks run Sunday to Saturday. Week 1 is the week that contains 1 January,
 * so a week that crosses New Year belongs to the new year. Each event is
 * listed once, under the day it starts.
 *
 * @phpstan-type TimelineEvent array{id: int, title: string, time: string}
 * @phpstan-type TimelineDay array{date: string, label: string, events: list<TimelineEvent>}
 * @phpstan-type TimelineWeek array{key: string, number: int, range: string, days: list<TimelineDay>}
 * @phpstan-type TimelineYear array{year: int, weeks: list<TimelineWeek>}
 */
class EventTimeline
{
    /**
     * Group events, which must already be in start order, into years, weeks and days.
     *
     * @param  Collection<int, Event>  $events
     * @return list<TimelineYear>
     */
    public static function group(Collection $events): array
    {
        $years = [];

        foreach ($events as $event) {
            $day = $event->starts_at->copy()->startOfDay();
            $weekStart = static::weekStart($day);
            $year = static::weekYear($weekStart);
            $weekKey = sprintf('%d-W%02d', $year, static::weekNumber($weekStart));
            $dayKey = $day->toDateString();

            $years[$year] ??= ['year' => $year, 'weeks' => []];

            $years[$year]['weeks'][$weekKey] ??= [
                'key' => $weekKey,
                'number' => static::weekNumber($weekStart),
                'range' => static::weekRange($weekStart),
                'days' => [],
            ];

            $years[$year]['weeks'][$weekKey]['days'][$dayKey] ??= [
                'date' => $dayKey,
                'label' => $day->format('l j F'),
                'events' => [],
            ];

            $years[$year]['weeks'][$weekKey]['days'][$dayKey]['events'][] = [
                'id' => $event->id,
                'title' => $event->title,
                'time' => static::timeRange($event->starts_at, $event->ends_at),
            ];
        }

        return array_values(array_map(fn (array $year) => [
            'year' => $year['year'],
            'weeks' => array_values(array_map(fn (array $week) => [
                ...$week,
                'days' => array_values($week['days']),
            ], $year['weeks'])),
        ], $years));
    }

    /**
     * The Sunday that starts the week containing the given day.
     */
    public static function weekStart(CarbonInterface $day): Carbon
    {
        return Carbon::instance($day)->startOfWeek(CarbonInterface::SUNDAY);
    }

    /**
     * The year a week belongs to: the year of its Saturday, so the week
     * containing 1 January is counted in the new year.
     */
    public static function weekYear(CarbonInterface $weekStart): int
    {
        return $weekStart->copy()->addDays(6)->year;
    }

    /**
     * The week's number within its year, counting the week containing 1 January as week 1.
     */
    public static function weekNumber(CarbonInterface $weekStart): int
    {
        $firstWeekStart = static::weekStart(Carbon::create(static::weekYear($weekStart), 1, 1));

        return intdiv((int) round($firstWeekStart->diffInDays($weekStart)), 7) + 1;
    }

    /**
     * The week's dates, e.g. "18–24 Oct", "27 Sep – 3 Oct" or "27 Dec 2026 – 2 Jan 2027".
     */
    public static function weekRange(CarbonInterface $weekStart): string
    {
        $weekEnd = $weekStart->copy()->addDays(6);

        if ($weekStart->year !== $weekEnd->year) {
            return $weekStart->format('j M Y').' – '.$weekEnd->format('j M Y');
        }

        if ($weekStart->month !== $weekEnd->month) {
            return $weekStart->format('j M').' – '.$weekEnd->format('j M');
        }

        return $weekStart->format('j').'–'.$weekEnd->format('j M');
    }

    /**
     * The event's times in 24-hour format, with the end date added when it ends on a later day.
     */
    public static function timeRange(CarbonInterface $startsAt, CarbonInterface $endsAt): string
    {
        if ($startsAt->isSameDay($endsAt)) {
            return $startsAt->format('H:i').'–'.$endsAt->format('H:i');
        }

        $endFormat = $startsAt->year === $endsAt->year ? 'D j M, H:i' : 'D j M Y, H:i';

        return $startsAt->format('H:i').' – '.$endsAt->format($endFormat);
    }
}

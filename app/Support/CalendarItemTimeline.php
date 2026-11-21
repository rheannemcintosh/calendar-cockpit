<?php

namespace App\Support;

use App\Enums\Firmness;
use App\Enums\FlagIcon;
use App\Enums\TimeType;
use App\Models\CalendarItem;
use App\Models\Flag;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Groups calendar items into years, weeks and days for the calendar items list.
 *
 * Weeks run Sunday to Saturday. Week 1 is the week that contains 1 January,
 * so a week that crosses New Year belongs to the new year.
 *
 * @phpstan-import-type TimestampLabels from Timestamps
 *
 * @phpstan-type TimelineCategory array{name: string, colour: string}
 * @phpstan-type TimelineSubCategory array{emoji: string, name: string}
 * @phpstan-type TimelineLocation array{name: string, address: string|null, mapsUrl: string}
 * @phpstan-type TimelineFlag array{name: string, icon: value-of<FlagIcon>}
 * @phpstan-type TimelineItem array{id: int, title: string, allDay: bool, time: string, timeType: value-of<TimeType>, firmness: value-of<Firmness>, flags: list<TimelineFlag>, category: TimelineCategory|null, subCategory: TimelineSubCategory|null, location: TimelineLocation|null, timestamps: TimestampLabels}
 * @phpstan-type TimelineDay array{date: string, label: string, items: list<TimelineItem>}
 * @phpstan-type TimelineWeek array{key: string, number: int, range: string, days: list<TimelineDay>}
 * @phpstan-type TimelineYear array{year: int, weeks: list<TimelineWeek>}
 */
class CalendarItemTimeline
{
    /**
     * Group calendar items, which must already be in start order, into years, weeks and days.
     * Eager load their sub-categories, categories and flags so each one is not fetched separately.
     * When each one was created and last updated is shown in the given timezone.
     *
     * @param  Collection<int, CalendarItem>  $calendarItems
     * @return list<TimelineYear>
     */
    public static function group(Collection $calendarItems, string $timezone): array
    {
        $years = [];

        foreach ($calendarItems as $calendarItem) {
            $day = $calendarItem->starts_at->copy()->startOfDay();
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
                'items' => [],
            ];

            $years[$year]['weeks'][$weekKey]['days'][$dayKey]['items'][] = static::item($calendarItem, $timezone);
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
     * A calendar item as the calendar items list and day view show it. Eager
     * load its sub-category, category and flags so they are not fetched
     * separately. An all-day calendar item's time is "All day", and one that
     * runs over several days also says the day it ends, see time(). When it
     * was created and last updated is shown in the given timezone.
     *
     * @return TimelineItem
     */
    public static function item(CalendarItem $calendarItem, string $timezone): array
    {
        return [
            'id' => $calendarItem->id,
            'title' => $calendarItem->title,
            'allDay' => $calendarItem->all_day,
            'time' => static::time($calendarItem),
            'timeType' => $calendarItem->time_type->value,
            'firmness' => $calendarItem->firmness->value,
            'flags' => array_values(array_map(fn (Flag $flag): array => [
                'name' => $flag->name,
                'icon' => $flag->icon->value,
            ], $calendarItem->flags->all())),
            'category' => $calendarItem->subCategory ? [
                'name' => $calendarItem->subCategory->category->name,
                'colour' => $calendarItem->subCategory->category->colour,
            ] : null,
            'subCategory' => $calendarItem->subCategory ? [
                'emoji' => $calendarItem->subCategory->emoji,
                'name' => $calendarItem->subCategory->name,
            ] : null,
            'location' => $calendarItem->location_name !== null ? [
                'name' => $calendarItem->location_name,
                'address' => $calendarItem->location_address,
                'mapsUrl' => static::mapsUrl($calendarItem->location_latitude, $calendarItem->location_longitude),
            ] : null,
            'timestamps' => Timestamps::of($calendarItem, $timezone),
        ];
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
     * A link that drops a pin on the location's point in Google Maps. It opens
     * the Google Maps app where one is installed, or the website if not.
     */
    public static function mapsUrl(float $latitude, float $longitude): string
    {
        return 'https://www.google.com/maps/search/?'.http_build_query([
            'api' => 1,
            'query' => $latitude.','.$longitude,
        ]);
    }

    /**
     * When the calendar item is, e.g. "09:00–10:30" or "All day". One that
     * ends on a later day also gives its end day, e.g. "15:00 – Mon 16:00" or
     * "All day until Mon". A timed one ending at the next midnight doesn't.
     */
    public static function time(CalendarItem $calendarItem): string
    {
        $startDay = $calendarItem->starts_at->copy()->startOfDay();

        if ($calendarItem->all_day) {
            $endDay = $calendarItem->ends_at->copy()->subDay()->startOfDay();

            return $endDay > $startDay
                ? __('All day until :day', ['day' => static::endDay($startDay, $endDay)])
                : __('All day');
        }

        if ($calendarItem->ends_at <= $startDay->copy()->addDay()) {
            return static::timeRange($calendarItem->starts_at, $calendarItem->ends_at);
        }

        $endDay = $calendarItem->ends_at->copy()->startOfDay();

        return $calendarItem->starts_at->format('H:i').' – '.static::endDay($startDay, $endDay).' '.$calendarItem->ends_at->format('H:i');
    }

    /**
     * The day a calendar item ends, after the day it starts: its weekday, e.g.
     * "Mon", when that is within the next six days, or with its date as well,
     * e.g. "Mon 2 Nov", when it is further away and the weekday alone would be
     * unclear.
     */
    public static function endDay(CarbonInterface $startDay, CarbonInterface $endDay): string
    {
        return $startDay->diffInDays($endDay) <= 6 ? $endDay->format('D') : $endDay->format('D j M');
    }

    /**
     * The calendar item's times in 24-hour format, e.g. "09:00–10:30".
     */
    public static function timeRange(CarbonInterface $startsAt, CarbonInterface $endsAt): string
    {
        return $startsAt->format('H:i').'–'.$endsAt->format('H:i');
    }
}

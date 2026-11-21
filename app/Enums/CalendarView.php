<?php

namespace App\Enums;

use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * The ways the calendar can be looked at, which editing a calendar item
 * returns to.
 */
enum CalendarView: string
{
    case List = 'list';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';

    /**
     * This view of the calendar opened on the given day, so a calendar item on
     * it is shown. The list starts at today, so it only starts at an earlier day.
     */
    public function url(CarbonImmutable $day, User $user): string
    {
        $date = $day->toDateString();

        return match ($this) {
            self::List => $day->lt($user->today())
                ? route('calendar-items.index', ['from' => $date])
                : route('calendar-items.index'),
            self::Day => route('calendar-items.day', $date),
            self::Week => route('calendar-items.week', $date),
            self::Month => route('calendar-items.month', $date),
        };
    }
}

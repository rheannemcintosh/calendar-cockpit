<?php

use App\Support\CalendarItemTimeline;
use Illuminate\Support\Carbon;

test('weeks start on Sunday', function (string $day, string $expectedStart) {
    expect(CalendarItemTimeline::weekStart(Carbon::parse($day))->toDateString())->toBe($expectedStart);
})->with([
    'a Sunday' => ['2026-10-18', '2026-10-18'],
    'a Wednesday' => ['2026-10-21', '2026-10-18'],
    'a Saturday' => ['2026-10-24', '2026-10-18'],
]);

test('week 1 is the week that contains 1 January', function (string $day, int $year, int $number) {
    $weekStart = CalendarItemTimeline::weekStart(Carbon::parse($day));

    expect(CalendarItemTimeline::weekYear($weekStart))->toBe($year)
        ->and(CalendarItemTimeline::weekNumber($weekStart))->toBe($number);
})->with([
    'Thursday 1 January 2026' => ['2026-01-01', 2026, 1],
    'Sunday 28 December 2025, in the week of 1 January' => ['2025-12-28', 2026, 1],
    'Sunday 4 January 2026' => ['2026-01-04', 2026, 2],
    'Tuesday 20 October 2026' => ['2026-10-20', 2026, 43],
    'Saturday 26 December 2026' => ['2026-12-26', 2026, 52],
    'Sunday 27 December 2026, in the week of 1 January' => ['2026-12-27', 2027, 1],
    'Saturday 1 January 2028, the last day of its week' => ['2028-01-01', 2028, 1],
    'Friday 31 December 2027, in the same week' => ['2027-12-31', 2028, 1],
    'Saturday 25 December 2027' => ['2027-12-25', 2027, 52],
    'Sunday 1 January 2023' => ['2023-01-01', 2023, 1],
    'Saturday 31 December 2022, a week before' => ['2022-12-31', 2022, 53],
]);

test('week ranges show the dates the week covers', function (string $weekStart, string $range) {
    expect(CalendarItemTimeline::weekRange(Carbon::parse($weekStart)))->toBe($range);
})->with([
    'within one month' => ['2026-10-18', '18–24 Oct'],
    'across two months' => ['2026-09-27', '27 Sep – 3 Oct'],
    'across two years' => ['2026-12-27', '27 Dec 2026 – 2 Jan 2027'],
]);

test('calendar item times are shown in 24-hour format', function (string $startsAt, string $endsAt, string $time) {
    expect(CalendarItemTimeline::timeRange(Carbon::parse($startsAt), Carbon::parse($endsAt)))->toBe($time);
})->with([
    'the same day' => ['2026-10-20 09:00', '2026-10-20 10:30', '09:00–10:30'],
    'the afternoon' => ['2026-10-20 13:15', '2026-10-20 18:45', '13:15–18:45'],
]);

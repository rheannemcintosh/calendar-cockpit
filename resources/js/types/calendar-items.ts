// The shape of App\Support\CalendarItemTimeline::group(), passed to the calendar items list.

import type { TimeTypeValue } from './time-types';

// A calendar item's category and sub-category are both null when it is uncategorised.
export type TimelineItem = {
    id: number;
    title: string;
    time: string;
    timeType: TimeTypeValue;
    category: { name: string; colour: string } | null;
    subCategory: { emoji: string; name: string } | null;
    // Null when the calendar item has no location. Not every place has an address.
    location: { name: string; address: string | null; mapsUrl: string } | null;
};

export type TimelineDay = {
    date: string;
    label: string;
    items: TimelineItem[];
};

export type TimelineWeek = {
    key: string;
    number: number;
    range: string;
    days: TimelineDay[];
};

export type TimelineYear = {
    year: number;
    weeks: TimelineWeek[];
};

// A calendar item placed on the day view by App\Support\CalendarItemDay::place().
// Minutes count from midnight, 0 to 1440. Overlapping calendar items share the
// width in `columns` columns, and this one is in column `column`, from 0.
export type DayItem = TimelineItem & {
    startMinute: number;
    endMinute: number;
    column: number;
    columns: number;
};

// A day of the week view, from App\Support\CalendarItemWeek::days().
export type WeekDay = {
    // As YYYY-MM-DD.
    date: string;
    // Short, e.g. "Sun".
    weekday: string;
    dayOfMonth: number;
    // In full, e.g. "Sunday 18 October 2026".
    label: string;
    items: DayItem[];
};

// A calendar item on a day of the month view, from App\Support\CalendarItemMonth::weeks().
// `continued` is true on the days after the one it starts on.
export type MonthItem = TimelineItem & {
    // As HH:MM.
    startTime: string;
    continued: boolean;
};

// A day of the month view's grid, which runs from the Sunday on or before the
// 1st to the Saturday on or after the last day.
export type MonthDay = {
    // As YYYY-MM-DD.
    date: string;
    dayOfMonth: number;
    // In full, e.g. "Sunday 18 October 2026".
    label: string;
    // False for the days of the months before and after.
    inMonth: boolean;
    items: MonthItem[];
};

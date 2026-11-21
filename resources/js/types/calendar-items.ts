// The shape of App\Support\CalendarItemTimeline::group(), passed to the calendar items list.

import type { FirmnessValue } from './firmness';
import type { ItemFlag } from './flags';
import type { LocationMatch } from './locations';
import type { TimeTypeValue } from './time-types';
import type { Timestamps } from './timestamps';

// A calendar item's category and sub-category are both null when it is uncategorised.
export type TimelineItem = {
    id: number;
    title: string;
    // All day on its dates, with no times. Its time is then "All day", and its
    // time type, which is always exact, isn't shown.
    allDay: boolean;
    // E.g. "09:00–10:30" or "All day". One that ends on a later day also
    // gives its end day, e.g. "15:00 – Mon 16:00" or "All day until Mon".
    time: string;
    timeType: TimeTypeValue;
    firmness: FirmnessValue;
    // In the order the user's flags are in. Empty when it has none.
    flags: ItemFlag[];
    category: { name: string; colour: string } | null;
    subCategory: { emoji: string; name: string } | null;
    // Null when the calendar item has no location. Not every place has an address.
    location: { name: string; address: string | null; mapsUrl: string } | null;
    timestamps: Timestamps;
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
    // Shown in a row above the timeline, not in it.
    allDayItems: TimelineItem[];
    items: DayItem[];
};

// A calendar item on a day of the month view, from App\Support\CalendarItemMonth::weeks().
// `continued` is true on the days after the one it starts on.
export type MonthItem = TimelineItem & {
    // As HH:MM.
    startTime: string;
    continued: boolean;
    // How long it is on this day.
    minutes: number;
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

// A calendar item as it is now, filling in the edit calendar item form.
export type EditableCalendarItem = {
    id: number;
    title: string;
    allDay: boolean;
    // As YYYY-MM-DD. The end date is the last day it is on.
    date: string;
    endDate: string;
    // As HH:MM, or null when it is all day.
    startTime: string | null;
    endTime: string | null;
    // Null when it is uncategorised.
    subCategoryId: number | null;
    timeType: TimeTypeValue;
    // Whether its time type was chosen rather than its sub-category's default.
    timeTypeOverridden: boolean;
    firmness: FirmnessValue;
    flagIds: number[];
    // Null when it has no location.
    location: Omit<LocationMatch, 'id'> | null;
};

// The calendar views a calendar item can be edited from, and returned to.
export type CalendarView = 'list' | 'day' | 'week' | 'month';

// A field that changed in an entry of a calendar item's history, from
// App\Support\CalendarItemHistory::of(), by the name the app uses for it. A
// sub-category shows its name. Old or new is null when it had no value.
export type CalendarItemHistoryChange = {
    field: string;
    label: string;
    old: string | null;
    new: string | null;
};

// A change made to a calendar item, or it being created, which lists no
// changes. When it was made is in the user's timezone, e.g. "21 Nov 2026,
// 14:05", and as ISO 8601 in `datetime`.
export type CalendarItemHistoryEntry = {
    key: string;
    event: 'created' | 'updated';
    at: string;
    datetime: string;
    changes: CalendarItemHistoryChange[];
};

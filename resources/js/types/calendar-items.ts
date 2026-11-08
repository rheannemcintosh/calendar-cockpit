// The shape of App\Support\CalendarItemTimeline::group(), passed to the calendar items list.

import type { FirmnessValue } from './firmness';
import type { ItemFlag } from './flags';
import type { TimeTypeValue } from './time-types';

// A calendar item's category and sub-category are both null when it is uncategorised.
export type TimelineItem = {
    id: number;
    title: string;
    time: string;
    timeType: TimeTypeValue;
    firmness: FirmnessValue;
    // In the order the user's flags are in. Empty when it has none.
    flags: ItemFlag[];
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

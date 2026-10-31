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

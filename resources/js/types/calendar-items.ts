// The shape of App\Support\CalendarItemTimeline::group(), passed to the calendar items list.

// A calendar item's category and sub-category are both null when it is uncategorised.
export type TimelineItem = {
    id: number;
    title: string;
    time: string;
    category: { name: string; colour: string } | null;
    subCategory: { emoji: string; name: string } | null;
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

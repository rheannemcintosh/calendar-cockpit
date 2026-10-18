// The shape of App\Support\CalendarItemTimeline::group(), passed to the calendar items list.

export type TimelineItem = {
    id: number;
    title: string;
    time: string;
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

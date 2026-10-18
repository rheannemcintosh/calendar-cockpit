// The shape of App\Support\EventTimeline::group(), passed to the events list.

export type TimelineEvent = {
    id: number;
    title: string;
    time: string;
};

export type TimelineDay = {
    date: string;
    label: string;
    events: TimelineEvent[];
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

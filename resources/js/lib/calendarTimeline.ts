// Shared by the day and week views, which place calendar items on a
// timeline of each day to the minute, and the month view.

// Each minute of the day is this many pixels tall, so a 10-minute calendar
// item still has room for a line of text.
export const PIXELS_PER_MINUTE = 2;
export const DAY_HEIGHT = 24 * 60 * PIXELS_PER_MINUTE;
export const HOURS = Array.from({ length: 24 }, (_, hour) => hour);

export function hourLabel(hour: number): string {
    return `${String(hour).padStart(2, '0')}:00`;
}

// Calendar item times are the times the user typed in, so the line for now
// uses the date and time it is in the user's timezone, and only shows on
// today's date there.
export function wallClock(
    moment: Date,
    timeZone: string,
): { date: string; minute: number } {
    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-GB', {
            timeZone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        })
            .formatToParts(moment)
            .map((part) => [part.type, part.value]),
    );

    return {
        date: `${parts.year}-${parts.month}-${parts.day}`,
        minute: Number(parts.hour) * 60 + Number(parts.minute),
    };
}

// Where to start a timeline scrolled to: a little before now, or the first
// calendar item, or the morning.
export function scrollTopFor(minute: number | null | undefined): number {
    return Math.max(0, ((minute ?? 8 * 60) - 30) * PIXELS_PER_MINUTE);
}

// Any colour can be chosen for a category, so a calendar item is a tint of it
// with the colour itself down the side, and the text stays in the page's own
// colour. Uncategorised calendar items have none.
export function categoryColours(
    category: { colour: string } | null,
): Record<string, string> | undefined {
    if (!category) {
        return undefined;
    }

    return {
        backgroundColor: `color-mix(in srgb, ${category.colour} 22%, var(--background))`,
        borderLeftColor: category.colour,
    };
}

// A length of time for a calendar item's details, e.g. "1 hr 30 min".
export function duration(minutes: number): string {
    const hoursPart = Math.floor(minutes / 60);
    const minutesPart = minutes % 60;

    if (!hoursPart) {
        return `${minutesPart} min`;
    }

    return minutesPart
        ? `${hoursPart} hr ${minutesPart} min`
        : `${hoursPart} hr`;
}

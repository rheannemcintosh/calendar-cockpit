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

// Calendar item times are the times the user typed in, so the line for
// now uses this device's clock and only shows on today's date here.
export function localDate(moment: Date): string {
    return [
        moment.getFullYear(),
        String(moment.getMonth() + 1).padStart(2, '0'),
        String(moment.getDate()).padStart(2, '0'),
    ].join('-');
}

export function minuteOfDay(moment: Date): number {
    return moment.getHours() * 60 + moment.getMinutes();
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

// The timezone this browser is in, such as Europe/London.
export function browserTimezone(): string {
    return Intl.DateTimeFormat().resolvedOptions().timeZone;
}

// How far a timezone is from UTC right now, such as GMT+1, or nothing when
// the browser cannot say.
export function timezoneOffset(timeZone: string, moment = new Date()): string {
    try {
        return (
            new Intl.DateTimeFormat('en-GB', {
                timeZone,
                timeZoneName: 'shortOffset',
            })
                .formatToParts(moment)
                .find((part) => part.type === 'timeZoneName')?.value ?? ''
        );
    } catch {
        return '';
    }
}

// A timezone as people read it, such as America/New York.
export function timezoneLabel(timeZone: string): string {
    return timeZone.replaceAll('_', ' ');
}

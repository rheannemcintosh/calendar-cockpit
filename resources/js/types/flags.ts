// The values of App\Enums\FlagIcon, each the kebab-case name of a Lucide icon.
export type FlagIconKey = 'pound-sterling' | 'lock' | 'star';

// A flag on a calendar item, in App\Support\CalendarItemTimeline::item().
export type ItemFlag = {
    name: string;
    icon: FlagIconKey;
};

// One of the user's flags as offered on a form.
export type FlagOption = ItemFlag & {
    id: number;
};

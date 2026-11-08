// The values of App\Enums\FlagIcon, each the kebab-case name of a Lucide icon.
export type FlagIconKey =
    | 'pound-sterling'
    | 'lock'
    | 'star'
    | 'heart'
    | 'bell'
    | 'flag'
    | 'circle-alert'
    | 'clock'
    | 'calendar-check'
    | 'ticket'
    | 'plane'
    | 'gift'
    | 'users'
    | 'briefcase'
    | 'house'
    | 'car'
    | 'handshake'
    | 'credit-card'
    | 'receipt'
    | 'shield'
    | 'sparkles'
    | 'zap'
    | 'pin'
    | 'bookmark';

// A flag on a calendar item, in App\Support\CalendarItemTimeline::item().
export type ItemFlag = {
    name: string;
    icon: FlagIconKey;
};

// One of the user's flags as offered on a form.
export type FlagOption = ItemFlag & {
    id: number;
};

// A flag on the flags page, with how many calendar items have it.
export type ManagedFlag = FlagOption & {
    calendarItemsCount: number;
};

// An icon a flag can show, from App\Enums\FlagIcon::options().
export type FlagIconOption = {
    value: FlagIconKey;
    label: string;
};

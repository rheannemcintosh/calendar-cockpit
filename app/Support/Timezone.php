<?php

namespace App\Support;

use DateTimeZone;

final class Timezone
{
    /**
     * The timezones a user can choose from in alphabetical order, with the
     * one they have, when it is an older name for one of them, kept in.
     *
     * @return list<string>
     */
    public static function options(?string $current = null): array
    {
        $timezones = DateTimeZone::listIdentifiers();

        if ($current !== null && ! in_array($current, $timezones, true)) {
            $timezones[] = $current;
            sort($timezones);
        }

        return $timezones;
    }

    /**
     * The timezone a browser gave, or null when it is not one PHP knows,
     * so a browser that gives no usable timezone is not held up by it.
     */
    public static function fromBrowser(mixed $timezone): ?string
    {
        return is_string($timezone) && in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)
            ? $timezone
            : null;
    }
}

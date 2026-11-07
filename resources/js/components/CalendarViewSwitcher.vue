<script setup lang="ts">
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
import { Calendar1, CalendarDays, CalendarRange, List } from '@lucide/vue';
import { day, index, week } from '@/routes/calendar-items';

export type CalendarView = 'list' | 'day' | 'week' | 'month';

type ViewOption = {
    value: CalendarView;
    label: string;
    icon: LucideIcon;
    // Where the view opens for the day being looked at, or for today when
    // there is none. A view without one is not built yet.
    href:
        | ((date: string | null) => NonNullable<InertiaLinkProps['href']>)
        | null;
};

defineProps<{
    current: CalendarView;
    // The day being looked at, as YYYY-MM-DD, so other views open on it.
    date?: string | null;
}>();

const views: ViewOption[] = [
    {
        value: 'list',
        label: 'List',
        icon: List,
        href: (date) => (date ? index({ query: { from: date } }) : index()),
    },
    {
        value: 'day',
        label: 'Day',
        icon: Calendar1,
        href: (date) => (date ? day(date) : day()),
    },
    {
        value: 'week',
        label: 'Week',
        icon: CalendarRange,
        href: (date) => (date ? week(date) : week()),
    },
    { value: 'month', label: 'Month', icon: CalendarDays, href: null },
];

const option =
    'flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors';
</script>

<template>
    <nav
        aria-label="Calendar views"
        class="bg-muted inline-flex gap-1 rounded-lg p-1"
        data-test="calendar-view-switcher"
    >
        <template v-for="view in views" :key="view.value">
            <span
                v-if="view.value === current"
                :class="[option, 'bg-background text-foreground shadow-xs']"
                aria-current="page"
                :data-test="`calendar-view-${view.value}`"
            >
                <component :is="view.icon" class="size-4" aria-hidden="true" />
                {{ view.label }}
            </span>
            <Link
                v-else-if="view.href"
                :href="view.href(date ?? null)"
                :class="[
                    option,
                    'text-muted-foreground hover:bg-background/60 hover:text-foreground focus-visible:ring-ring/50 cursor-pointer outline-none focus-visible:ring-[3px]',
                ]"
                :data-test="`calendar-view-${view.value}`"
            >
                <component :is="view.icon" class="size-4" aria-hidden="true" />
                {{ view.label }}
            </Link>
            <span
                v-else
                :class="[
                    option,
                    'text-muted-foreground cursor-not-allowed opacity-50',
                ]"
                title="Coming soon"
                aria-disabled="true"
                :data-test="`calendar-view-${view.value}`"
            >
                <component :is="view.icon" class="size-4" aria-hidden="true" />
                {{ view.label }}
                <span class="sr-only">(coming soon)</span>
            </span>
        </template>
    </nav>
</template>

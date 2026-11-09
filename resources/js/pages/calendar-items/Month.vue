<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarPlus, ChevronLeft, ChevronRight, Plus } from '@lucide/vue';
import { computed } from 'vue';
import CalendarItemDetails from '@/components/CalendarItemDetails.vue';
import CalendarViewSwitcher from '@/components/CalendarViewSwitcher.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useUserNow } from '@/composables/useUserNow';
import { categoryColours, duration } from '@/lib/calendarTimeline';
import { create, day, index, month } from '@/routes/calendar-items';
import type { MonthDay, TimeTypeOption } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Calendar items',
                href: index(),
            },
            {
                // The last breadcrumb is not a link, so it needs no date.
                title: 'Month view',
                href: month(),
            },
        ],
    },
});

const props = defineProps<{
    // The day the month was opened on, as YYYY-MM-DD, so other views open on it.
    date: string;
    // As YYYY-MM.
    month: string;
    label: string;
    previousDate: string;
    nextDate: string;
    // Sunday to Saturday, from the week of the 1st to the week of the last day.
    weeks: MonthDay[][];
    timeTypes: TimeTypeOption[];
}>();

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const { today } = useUserNow();

const isEmpty = computed(() =>
    props.weeks.every((week) =>
        week.every((monthDay) => !monthDay.inMonth || !monthDay.items.length),
    ),
);

const timeTypeOptions = computed(
    () =>
        Object.fromEntries(
            props.timeTypes.map((timeType) => [timeType.value, timeType]),
        ) as Record<string, TimeTypeOption>,
);

function itemCount(monthDay: MonthDay): string {
    const count = monthDay.items.length;

    return count === 1 ? '1 calendar item' : `${count || 'No'} calendar items`;
}
</script>

<template>
    <Head :title="label" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="label"
                description="Your calendar items, day by day in start order"
            />
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    New calendar item
                </Link>
            </Button>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <CalendarViewSwitcher current="month" :date="date" />
            <nav aria-label="Months" class="flex items-center gap-2">
                <Button variant="outline" size="icon" as-child>
                    <Link
                        :href="month(previousDate)"
                        aria-label="Previous month"
                        data-test="previous-month-link"
                    >
                        <ChevronLeft />
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="month()" data-test="this-month-link"
                        >This month</Link
                    >
                </Button>
                <Button variant="outline" size="icon" as-child>
                    <Link
                        :href="month(nextDate)"
                        aria-label="Next month"
                        data-test="next-month-link"
                    >
                        <ChevronRight />
                    </Link>
                </Button>
            </nav>
        </div>

        <div
            v-if="isEmpty"
            class="text-muted-foreground flex items-center gap-3 rounded-lg border border-dashed p-4 text-sm"
            data-test="empty-month"
        >
            <CalendarPlus class="size-5 shrink-0" />
            Nothing planned for this month.
        </div>

        <div class="overflow-x-auto rounded-lg border" data-test="month-grid">
            <div class="min-w-[48rem]">
                <div class="grid grid-cols-7 border-b" aria-hidden="true">
                    <div
                        v-for="weekday in WEEKDAYS"
                        :key="weekday"
                        class="text-muted-foreground py-2 text-center text-xs font-medium uppercase"
                    >
                        {{ weekday }}
                    </div>
                </div>

                <div
                    v-for="week in weeks"
                    :key="week[0].date"
                    class="grid grid-cols-7 not-last:border-b"
                    data-test="month-week"
                >
                    <div
                        v-for="monthDay in week"
                        :key="monthDay.date"
                        :class="[
                            'flex h-28 min-w-0 flex-col gap-1.5 overflow-hidden p-1.5 not-first:border-l',
                            monthDay.date === today
                                ? 'bg-primary/5'
                                : !monthDay.inMonth &&
                                  'bg-muted/40 text-muted-foreground',
                        ]"
                        :aria-current="
                            monthDay.date === today ? 'date' : undefined
                        "
                        :data-date="monthDay.date"
                        :data-in-month="monthDay.inMonth"
                        data-test="month-day"
                    >
                        <Link
                            :href="day(monthDay.date)"
                            :class="[
                                'focus-visible:ring-ring flex size-7 shrink-0 items-center justify-center self-start rounded-full text-sm font-semibold tabular-nums outline-none focus-visible:ring-2',
                                monthDay.date === today
                                    ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                    : 'hover:bg-muted',
                            ]"
                            :aria-label="`${monthDay.label}, ${itemCount(monthDay)}`"
                            data-test="month-day-link"
                            >{{ monthDay.dayOfMonth }}</Link
                        >

                        <ul
                            v-if="monthDay.items.length"
                            class="flex flex-wrap content-start gap-1"
                            :aria-label="`Calendar items on ${monthDay.label}`"
                        >
                            <Tooltip
                                v-for="item in monthDay.items"
                                :key="item.id"
                                :delay-duration="150"
                            >
                                <TooltipTrigger as-child>
                                    <li
                                        tabindex="0"
                                        :class="[
                                            'focus-visible:ring-ring h-3.5 w-6 shrink-0 rounded-sm border border-l-4 outline-none focus-visible:ring-2',
                                            !item.category &&
                                                'bg-muted border-l-muted-foreground/40',
                                            item.timeType === 'block'
                                                ? 'border-foreground/40 border-dashed'
                                                : 'border-black/10 dark:border-white/20',
                                            !monthDay.inMonth && 'opacity-60',
                                        ]"
                                        :style="categoryColours(item.category)"
                                        :data-time-type="item.timeType"
                                        data-test="month-item"
                                    >
                                        <span class="sr-only"
                                            >{{ item.title }},
                                            {{ item.time }}</span
                                        >
                                    </li>
                                </TooltipTrigger>
                                <TooltipContent
                                    side="top"
                                    align="start"
                                    :collision-padding="8"
                                    class="flex max-w-64 flex-col gap-1 py-2"
                                    data-test="month-item-details"
                                >
                                    <CalendarItemDetails
                                        :item="item"
                                        :time-type="
                                            timeTypeOptions[item.timeType]
                                        "
                                        :duration="duration(item.minutes)"
                                    />
                                </TooltipContent>
                            </Tooltip>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

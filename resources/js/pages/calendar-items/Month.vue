<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarPlus, ChevronLeft, ChevronRight, Plus } from '@lucide/vue';
import { computed } from 'vue';
import CalendarViewSwitcher from '@/components/CalendarViewSwitcher.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { useUserNow } from '@/composables/useUserNow';
import { categoryColours } from '@/lib/calendarTimeline';
import { create, day, index, month } from '@/routes/calendar-items';
import type { MonthDay, MonthItem, TimeTypeOption } from '@/types';

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

// A day shows this many calendar items. When it has more, the last of them
// makes way for how many more there are, which opens the day view.
const MAX_ITEMS = 3;

const { today } = useUserNow();

const isEmpty = computed(() =>
    props.weeks.every((week) =>
        week.every((monthDay) => !monthDay.inMonth || !monthDay.items.length),
    ),
);

const timeTypeLabels = computed(
    () =>
        Object.fromEntries(
            props.timeTypes.map((timeType) => [timeType.value, timeType.label]),
        ) as Record<string, string>,
);

function shown(monthDay: MonthDay): MonthItem[] {
    return monthDay.items.length > MAX_ITEMS
        ? monthDay.items.slice(0, MAX_ITEMS - 1)
        : monthDay.items;
}

function details(item: MonthItem): string {
    return [
        item.subCategory
            ? `${item.subCategory.emoji} ${item.subCategory.name}: ${item.title}`
            : item.title,
        item.timeType === 'exact'
            ? item.time
            : `${item.time} (${timeTypeLabels.value[item.timeType]})`,
        item.location?.name,
    ]
        .filter(Boolean)
        .join('\n');
}

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
                    <Link
                        v-for="monthDay in week"
                        :key="monthDay.date"
                        :href="day(monthDay.date)"
                        :class="[
                            'focus-visible:ring-ring flex min-h-28 min-w-0 cursor-pointer flex-col gap-1 p-1.5 outline-none not-first:border-l focus-visible:ring-2 focus-visible:ring-inset',
                            monthDay.date === today
                                ? 'bg-primary/5 hover:bg-primary/10'
                                : monthDay.inMonth
                                  ? 'hover:bg-muted/60'
                                  : 'bg-muted/40 text-muted-foreground hover:bg-muted/70',
                        ]"
                        :aria-current="
                            monthDay.date === today ? 'date' : undefined
                        "
                        :data-date="monthDay.date"
                        :data-in-month="monthDay.inMonth"
                        data-test="month-day"
                    >
                        <span class="sr-only"
                            >{{ monthDay.label }},
                            {{ itemCount(monthDay) }}</span
                        >
                        <span
                            :class="[
                                'flex size-7 items-center justify-center self-start rounded-full text-sm font-semibold tabular-nums',
                                monthDay.date === today &&
                                    'bg-primary text-primary-foreground',
                            ]"
                            aria-hidden="true"
                            >{{ monthDay.dayOfMonth }}</span
                        >

                        <ul
                            v-if="monthDay.items.length"
                            class="flex flex-col gap-0.5"
                        >
                            <li
                                v-for="item in shown(monthDay)"
                                :key="item.id"
                                :class="[
                                    'flex min-w-0 items-center gap-1 rounded-sm border border-l-4 px-1 text-xs leading-5',
                                    !item.category &&
                                        'bg-muted border-l-muted-foreground/40',
                                    item.timeType === 'block'
                                        ? 'border-foreground/40 border-dashed'
                                        : 'border-black/10 dark:border-white/20',
                                    !monthDay.inMonth && 'opacity-60',
                                ]"
                                :style="categoryColours(item.category)"
                                :title="details(item)"
                                :data-time-type="item.timeType"
                                data-test="month-item"
                            >
                                <span
                                    class="text-muted-foreground shrink-0 tabular-nums"
                                    data-test="month-item-time"
                                    >{{
                                        item.continued
                                            ? 'Cont.'
                                            : `${item.timeType === 'approximate' ? '~' : ''}${item.startTime}`
                                    }}</span
                                >
                                <span class="truncate font-medium">
                                    <span
                                        v-if="item.subCategory"
                                        class="mr-0.5"
                                        aria-hidden="true"
                                        >{{ item.subCategory.emoji }}</span
                                    >{{ item.title }}
                                </span>
                            </li>
                            <li
                                v-if="
                                    monthDay.items.length >
                                    shown(monthDay).length
                                "
                                class="text-muted-foreground px-1 text-xs leading-5 font-medium hover:underline"
                                data-test="month-day-more"
                            >
                                +{{
                                    monthDay.items.length -
                                    shown(monthDay).length
                                }}
                                more
                            </li>
                        </ul>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

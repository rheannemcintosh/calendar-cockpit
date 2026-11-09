<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarPlus, ChevronLeft, ChevronRight, Plus } from '@lucide/vue';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import CalendarDayColumn from '@/components/CalendarDayColumn.vue';
import CalendarHourGutter from '@/components/CalendarHourGutter.vue';
import CalendarViewSwitcher from '@/components/CalendarViewSwitcher.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { useUserNow } from '@/composables/useUserNow';
import { DAY_HEIGHT, scrollTopFor } from '@/lib/calendarTimeline';
import { create, day, index, week } from '@/routes/calendar-items';
import type { FirmnessOption, TimeTypeOption, WeekDay } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Calendar items',
                href: index(),
            },
            {
                // The last breadcrumb is not a link, so it needs no date.
                title: 'Week view',
                href: week(),
            },
        ],
    },
});

const props = defineProps<{
    // The day the week was opened on, as YYYY-MM-DD, so other views open on it.
    date: string;
    label: string;
    weekNumber: number;
    previousDate: string;
    nextDate: string;
    // Sunday to Saturday.
    days: WeekDay[];
    timeTypes: TimeTypeOption[];
    firmnesses: FirmnessOption[];
}>();

const { today, nowMinute } = useUserNow();

const isEmpty = computed(() => props.days.every((d) => !d.items.length));

// Start the timeline scrolled to now when the week has today in it, or to
// the earliest calendar item of the week, or to the morning.
const timeline = ref<HTMLElement | null>(null);

function scrollToStart(): void {
    const hasToday = props.days.some((d) => d.date === today.value);
    const starts = props.days.flatMap((d) =>
        d.items.map((item) => item.startMinute),
    );

    timeline.value?.scrollTo({
        top: scrollTopFor(
            hasToday
                ? nowMinute.value
                : starts.length
                  ? Math.min(...starts)
                  : null,
        ),
    });
}

onMounted(scrollToStart);
watch(
    () => props.date,
    () => nextTick(scrollToStart),
);
</script>

<template>
    <Head :title="`Week of ${label}`" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="label"
                :description="`Week ${weekNumber}: your calendar items, placed to the minute`"
            />
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    New calendar item
                </Link>
            </Button>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <CalendarViewSwitcher current="week" :date="date" />
            <nav aria-label="Weeks" class="flex items-center gap-2">
                <Button variant="outline" size="icon" as-child>
                    <Link
                        :href="week(previousDate)"
                        aria-label="Previous week"
                        data-test="previous-week-link"
                    >
                        <ChevronLeft />
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="week()" data-test="this-week-link"
                        >This week</Link
                    >
                </Button>
                <Button variant="outline" size="icon" as-child>
                    <Link
                        :href="week(nextDate)"
                        aria-label="Next week"
                        data-test="next-week-link"
                    >
                        <ChevronRight />
                    </Link>
                </Button>
            </nav>
        </div>

        <div
            v-if="isEmpty"
            class="text-muted-foreground flex items-center gap-3 rounded-lg border border-dashed p-4 text-sm"
            data-test="empty-week"
        >
            <CalendarPlus class="size-5 shrink-0" />
            Nothing planned for this week.
        </div>

        <div
            ref="timeline"
            class="max-h-[70vh] overflow-auto rounded-lg border"
            data-test="week-timeline"
        >
            <div class="min-w-[56rem]">
                <div
                    class="bg-background sticky top-0 z-20 flex border-b"
                    data-test="week-days"
                >
                    <div class="w-14 shrink-0" aria-hidden="true" />
                    <Link
                        v-for="weekDay in days"
                        :key="weekDay.date"
                        :href="day(weekDay.date)"
                        :class="[
                            'hover:bg-muted focus-visible:ring-ring flex flex-1 cursor-pointer flex-col items-center gap-0.5 border-l py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-inset',
                            weekDay.date === today && 'bg-primary/5',
                        ]"
                        :aria-label="weekDay.label"
                        :aria-current="
                            weekDay.date === today ? 'date' : undefined
                        "
                        data-test="week-day-link"
                    >
                        <span
                            :class="[
                                'text-xs font-medium uppercase',
                                weekDay.date === today
                                    ? 'text-primary'
                                    : 'text-muted-foreground',
                            ]"
                            >{{ weekDay.weekday }}</span
                        >
                        <span
                            :class="[
                                'flex size-8 items-center justify-center rounded-full text-base font-semibold tabular-nums',
                                weekDay.date === today &&
                                    'bg-primary text-primary-foreground',
                            ]"
                            >{{ weekDay.dayOfMonth }}</span
                        >
                    </Link>
                </div>

                <div
                    class="relative flex"
                    :style="{ height: `${DAY_HEIGHT}px` }"
                >
                    <CalendarHourGutter />
                    <CalendarDayColumn
                        v-for="weekDay in days"
                        :key="weekDay.date"
                        :items="weekDay.items"
                        :time-types="timeTypes"
                        :firmnesses="firmnesses"
                        :now-minute="weekDay.date === today ? nowMinute : null"
                        :class="weekDay.date === today && 'bg-primary/5'"
                        :data-date="weekDay.date"
                        data-test="week-day"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

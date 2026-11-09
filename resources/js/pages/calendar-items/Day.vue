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
import { create, day, index } from '@/routes/calendar-items';
import type { DayItem, TimeTypeOption } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Calendar items',
                href: index(),
            },
            {
                // The last breadcrumb is not a link, so it needs no date.
                title: 'Day view',
                href: day(),
            },
        ],
    },
});

const props = defineProps<{
    // The day shown, as YYYY-MM-DD.
    date: string;
    label: string;
    previousDate: string;
    nextDate: string;
    items: DayItem[];
    timeTypes: TimeTypeOption[];
}>();

const { today, nowMinute: minuteNow } = useUserNow();

const nowMinute = computed(() =>
    today.value === props.date ? minuteNow.value : null,
);

// Start the timeline scrolled to now on today, or to the first calendar
// item, or to the morning, with a little of the time before it in view.
const timeline = ref<HTMLElement | null>(null);

function scrollToStart(): void {
    timeline.value?.scrollTo({
        top: scrollTopFor(nowMinute.value ?? props.items[0]?.startMinute),
    });
}

onMounted(scrollToStart);
watch(
    () => props.date,
    () => nextTick(scrollToStart),
);
</script>

<template>
    <Head :title="label" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="label"
                description="Your calendar items, placed to the minute"
            />
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    New calendar item
                </Link>
            </Button>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <CalendarViewSwitcher current="day" :date="date" />
            <nav aria-label="Days" class="flex items-center gap-2">
                <Button variant="outline" size="icon" as-child>
                    <Link
                        :href="day(previousDate)"
                        aria-label="Previous day"
                        data-test="previous-day-link"
                    >
                        <ChevronLeft />
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="day()" data-test="today-link">Today</Link>
                </Button>
                <Button variant="outline" size="icon" as-child>
                    <Link
                        :href="day(nextDate)"
                        aria-label="Next day"
                        data-test="next-day-link"
                    >
                        <ChevronRight />
                    </Link>
                </Button>
            </nav>
        </div>

        <div
            v-if="!items.length"
            class="text-muted-foreground flex items-center gap-3 rounded-lg border border-dashed p-4 text-sm"
            data-test="empty-day"
        >
            <CalendarPlus class="size-5 shrink-0" />
            Nothing planned for this day.
        </div>

        <div
            ref="timeline"
            class="max-h-[70vh] overflow-y-auto rounded-lg border"
            data-test="day-timeline"
        >
            <div class="relative flex" :style="{ height: `${DAY_HEIGHT}px` }">
                <CalendarHourGutter />
                <CalendarDayColumn
                    :items="items"
                    :time-types="timeTypes"
                    :now-minute="nowMinute"
                />
            </div>
        </div>
    </div>
</template>

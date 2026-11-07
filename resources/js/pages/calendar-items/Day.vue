<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarPlus,
    ChevronLeft,
    ChevronRight,
    Clock,
    MapPin,
    Plus,
} from '@lucide/vue';
import { useIntervalFn } from '@vueuse/core';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import CalendarItemTime from '@/components/CalendarItemTime.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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

const timeTypes = Object.fromEntries(
    props.timeTypes.map((timeType) => [timeType.value, timeType]),
);

// Each minute of the day is this many pixels tall, so a 10-minute calendar
// item still has room for a line of text.
const PIXELS_PER_MINUTE = 2;
const DAY_HEIGHT = 24 * 60 * PIXELS_PER_MINUTE;
const hours = Array.from({ length: 24 }, (_, hour) => hour);

// Below this length a block is too short for text, so it shows only its
// colour and the details are in its hover.
const SHORT_MINUTES = 10;
// From this length a block has room for the time on a line of its own.
const STACKED_MINUTES = 25;
// From this length a block also has room for its location.
const LOCATION_MINUTES = 35;

function hourLabel(hour: number): string {
    return `${String(hour).padStart(2, '0')}:00`;
}

function minutes(item: DayItem): number {
    return item.endMinute - item.startMinute;
}

function duration(item: DayItem): string {
    const total = minutes(item);
    const hoursPart = Math.floor(total / 60);
    const minutesPart = total % 60;

    if (!hoursPart) {
        return `${minutesPart} min`;
    }

    return minutesPart
        ? `${hoursPart} hr ${minutesPart} min`
        : `${hoursPart} hr`;
}

function placement(item: DayItem) {
    const width = 100 / item.columns;

    return {
        top: `${item.startMinute * PIXELS_PER_MINUTE}px`,
        height: `${(item.endMinute - item.startMinute) * PIXELS_PER_MINUTE}px`,
        left: `${item.column * width}%`,
        width: `calc(${width}% - 2px)`,
    };
}

// Any colour can be chosen, so the block is a tint of it with the colour
// itself down the side, and the text stays in the page's own colour.
function colours(item: DayItem) {
    if (!item.category) {
        return undefined;
    }

    return {
        backgroundColor: `color-mix(in srgb, ${item.category.colour} 22%, var(--background))`,
        borderLeftColor: item.category.colour,
    };
}

// Calendar item times are the times the user typed in, so the line for
// now uses this device's clock and only shows on today's date here.
function localDate(moment: Date): string {
    return [
        moment.getFullYear(),
        String(moment.getMonth() + 1).padStart(2, '0'),
        String(moment.getDate()).padStart(2, '0'),
    ].join('-');
}

const now = ref(new Date());
useIntervalFn(() => (now.value = new Date()), 60_000);

const nowMinute = computed(() =>
    localDate(now.value) === props.date
        ? now.value.getHours() * 60 + now.value.getMinutes()
        : null,
);

// Start the timeline scrolled to now on today, or to the first calendar
// item, or to the morning, with a little of the time before it in view.
const timeline = ref<HTMLElement | null>(null);

function scrollToStart(): void {
    const minute = nowMinute.value ?? props.items[0]?.startMinute ?? 8 * 60;

    timeline.value?.scrollTo({
        top: Math.max(0, (minute - 30) * PIXELS_PER_MINUTE),
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
            <div class="flex items-center gap-2">
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
                <Button as-child>
                    <Link :href="create()">
                        <Plus />
                        New calendar item
                    </Link>
                </Button>
            </div>
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
                <div class="relative w-14 shrink-0" aria-hidden="true">
                    <span
                        v-for="hour in hours"
                        :key="hour"
                        class="text-muted-foreground absolute right-2 -translate-y-1/2 text-xs tabular-nums first:translate-y-0.5"
                        :style="{ top: `${hour * 60 * PIXELS_PER_MINUTE}px` }"
                        >{{ hourLabel(hour) }}</span
                    >
                </div>

                <div class="relative flex-1 border-l">
                    <div
                        v-for="hour in hours"
                        :key="hour"
                        class="absolute inset-x-0 border-t first:border-t-0"
                        :style="{ top: `${hour * 60 * PIXELS_PER_MINUTE}px` }"
                        aria-hidden="true"
                    />

                    <ol class="absolute inset-0 mr-1 ml-0.5">
                        <Tooltip
                            v-for="item in items"
                            :key="item.id"
                            :delay-duration="150"
                        >
                            <TooltipTrigger as-child>
                                <li
                                    tabindex="0"
                                    :class="[
                                        'focus-visible:ring-ring absolute flex overflow-hidden rounded-md border border-l-4 px-2 text-xs leading-4 outline-none focus-visible:ring-2',
                                        !item.category &&
                                            'bg-muted border-l-muted-foreground/40',
                                        item.timeType === 'block'
                                            ? 'border-foreground/40 border-dashed'
                                            : 'border-black/10 dark:border-white/20',
                                        minutes(item) >= STACKED_MINUTES
                                            ? 'flex-col gap-0.5 py-1'
                                            : 'items-start gap-1.5',
                                    ]"
                                    :style="{
                                        ...placement(item),
                                        ...colours(item),
                                    }"
                                    :data-time-type="item.timeType"
                                    data-test="day-item"
                                >
                                    <span
                                        v-if="minutes(item) < SHORT_MINUTES"
                                        class="sr-only"
                                        >{{ item.title }}, {{ item.time }}</span
                                    >
                                    <template v-else>
                                        <span class="truncate font-medium">
                                            <span
                                                v-if="item.subCategory"
                                                class="mr-1"
                                                aria-hidden="true"
                                                >{{
                                                    item.subCategory.emoji
                                                }}</span
                                            >{{ item.title }}
                                        </span>
                                        <span
                                            class="text-muted-foreground flex shrink-0 items-center gap-1 tabular-nums"
                                        >
                                            <Clock
                                                v-if="
                                                    minutes(item) >=
                                                    STACKED_MINUTES
                                                "
                                                class="size-3 shrink-0"
                                                aria-hidden="true"
                                            />
                                            <CalendarItemTime
                                                :time="item.time"
                                                :time-type="
                                                    timeTypes[item.timeType]
                                                "
                                                inline
                                            />
                                        </span>
                                        <a
                                            v-if="
                                                item.location &&
                                                minutes(item) >=
                                                    LOCATION_MINUTES
                                            "
                                            :href="item.location.mapsUrl"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-muted-foreground hover:text-foreground flex max-w-full items-center gap-1 self-start underline-offset-4 hover:underline"
                                            data-test="location"
                                        >
                                            <MapPin
                                                class="size-3 shrink-0"
                                                aria-hidden="true"
                                            />
                                            <span class="truncate">{{
                                                item.location.name
                                            }}</span>
                                            <span class="sr-only"
                                                >(opens in maps in a new
                                                tab)</span
                                            >
                                        </a>
                                    </template>
                                </li>
                            </TooltipTrigger>
                            <TooltipContent
                                side="top"
                                align="start"
                                :collision-padding="8"
                                class="flex max-w-64 flex-col gap-1 py-2"
                                data-test="day-item-details"
                            >
                                <span class="font-medium">
                                    <span
                                        v-if="item.subCategory"
                                        class="mr-1"
                                        aria-hidden="true"
                                        >{{ item.subCategory.emoji }}</span
                                    >{{ item.title }}
                                </span>
                                <span
                                    class="flex items-center gap-1.5 tabular-nums"
                                >
                                    <Clock
                                        class="size-3 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {{
                                        item.timeType === 'approximate'
                                            ? '~'
                                            : ''
                                    }}{{ item.time }} · {{ duration(item) }}
                                </span>
                                <span
                                    v-if="item.timeType !== 'exact'"
                                    class="opacity-80"
                                >
                                    {{ timeTypes[item.timeType].label }}:
                                    {{ timeTypes[item.timeType].hint }}
                                </span>
                                <span
                                    v-if="item.location"
                                    class="flex items-start gap-1.5"
                                >
                                    <MapPin
                                        class="mt-0.5 size-3 shrink-0"
                                        aria-hidden="true"
                                    />
                                    <span>
                                        {{ item.location.name }}
                                        <span
                                            v-if="item.location.address"
                                            class="block opacity-80"
                                            >{{ item.location.address }}</span
                                        >
                                    </span>
                                </span>
                            </TooltipContent>
                        </Tooltip>
                    </ol>

                    <div
                        v-if="nowMinute !== null"
                        class="pointer-events-none absolute inset-x-0 z-10 border-t-2 border-red-500"
                        :style="{ top: `${nowMinute * PIXELS_PER_MINUTE}px` }"
                        data-test="now-line"
                    >
                        <span
                            class="absolute -top-[5px] -left-[5px] size-2 rounded-full bg-red-500"
                        />
                        <span class="sr-only">Now</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Clock, MapPin } from '@lucide/vue';
import { computed } from 'vue';
import CalendarItemTime from '@/components/CalendarItemTime.vue';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { PIXELS_PER_MINUTE } from '@/lib/calendarTimeline';
import type { DayItem, TimeTypeOption } from '@/types';

// A calendar item as a block on a day's timeline, placed to the minute, with
// its details in a hover.
const props = defineProps<{
    item: DayItem;
    timeType: TimeTypeOption;
}>();

// Below this length a block is too short for text, so it shows only its
// colour and the details are in its hover.
const SHORT_MINUTES = 10;
// From this length a block has room for the time on a line of its own.
const STACKED_MINUTES = 25;
// From this length a block also has room for its location.
const LOCATION_MINUTES = 35;

const minutes = computed(() => props.item.endMinute - props.item.startMinute);

const duration = computed(() => {
    const hoursPart = Math.floor(minutes.value / 60);
    const minutesPart = minutes.value % 60;

    if (!hoursPart) {
        return `${minutesPart} min`;
    }

    return minutesPart
        ? `${hoursPart} hr ${minutesPart} min`
        : `${hoursPart} hr`;
});

const placement = computed(() => {
    const width = 100 / props.item.columns;

    return {
        top: `${props.item.startMinute * PIXELS_PER_MINUTE}px`,
        height: `${minutes.value * PIXELS_PER_MINUTE}px`,
        left: `${props.item.column * width}%`,
        width: `calc(${width}% - 2px)`,
    };
});

// Any colour can be chosen, so the block is a tint of it with the colour
// itself down the side, and the text stays in the page's own colour.
const colours = computed(() => {
    if (!props.item.category) {
        return undefined;
    }

    return {
        backgroundColor: `color-mix(in srgb, ${props.item.category.colour} 22%, var(--background))`,
        borderLeftColor: props.item.category.colour,
    };
});
</script>

<template>
    <Tooltip :delay-duration="150">
        <TooltipTrigger as-child>
            <li
                tabindex="0"
                :class="[
                    'focus-visible:ring-ring absolute flex overflow-hidden rounded-md border border-l-4 px-2 text-xs leading-4 outline-none focus-visible:ring-2',
                    !item.category && 'bg-muted border-l-muted-foreground/40',
                    item.timeType === 'block'
                        ? 'border-foreground/40 border-dashed'
                        : 'border-black/10 dark:border-white/20',
                    minutes >= STACKED_MINUTES
                        ? 'flex-col gap-0.5 py-1'
                        : 'items-start gap-1.5',
                ]"
                :style="{ ...placement, ...colours }"
                :data-time-type="item.timeType"
                data-test="day-item"
            >
                <span v-if="minutes < SHORT_MINUTES" class="sr-only"
                    >{{ item.title }}, {{ item.time }}</span
                >
                <template v-else>
                    <span class="truncate font-medium">
                        <span
                            v-if="item.subCategory"
                            class="mr-1"
                            aria-hidden="true"
                            >{{ item.subCategory.emoji }}</span
                        >{{ item.title }}
                    </span>
                    <span
                        class="text-muted-foreground flex shrink-0 items-center gap-1 tabular-nums"
                    >
                        <Clock
                            v-if="minutes >= STACKED_MINUTES"
                            class="size-3 shrink-0"
                            aria-hidden="true"
                        />
                        <CalendarItemTime
                            :time="item.time"
                            :time-type="timeType"
                            inline
                        />
                    </span>
                    <a
                        v-if="item.location && minutes >= LOCATION_MINUTES"
                        :href="item.location.mapsUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-muted-foreground hover:text-foreground flex max-w-full items-center gap-1 self-start underline-offset-4 hover:underline"
                        data-test="location"
                    >
                        <MapPin class="size-3 shrink-0" aria-hidden="true" />
                        <span class="truncate">{{ item.location.name }}</span>
                        <span class="sr-only"
                            >(opens in maps in a new tab)</span
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
                <span v-if="item.subCategory" class="mr-1" aria-hidden="true">{{
                    item.subCategory.emoji
                }}</span
                >{{ item.title }}
            </span>
            <span class="flex items-center gap-1.5 tabular-nums">
                <Clock class="size-3 shrink-0" aria-hidden="true" />
                {{ item.timeType === 'approximate' ? '~' : ''
                }}{{ item.time }} · {{ duration }}
            </span>
            <span v-if="item.timeType !== 'exact'" class="opacity-80">
                {{ timeType.label }}: {{ timeType.hint }}
            </span>
            <span v-if="item.location" class="flex items-start gap-1.5">
                <MapPin class="mt-0.5 size-3 shrink-0" aria-hidden="true" />
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
</template>

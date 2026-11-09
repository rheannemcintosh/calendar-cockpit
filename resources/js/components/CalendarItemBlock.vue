<script setup lang="ts">
import { Clock, MapPin } from '@lucide/vue';
import { computed } from 'vue';
import CalendarItemDetails from '@/components/CalendarItemDetails.vue';
import CalendarItemTime from '@/components/CalendarItemTime.vue';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    categoryColours,
    DAY_TINT,
    duration,
    PIXELS_PER_MINUTE,
} from '@/lib/calendarTimeline';
import { firmnessFill, secondaryText, titleText } from '@/lib/firmness';
import type { DayItem, FirmnessOption, TimeTypeOption } from '@/types';

// A calendar item as a block on a day's timeline, placed to the minute, with
// its details in a hover.
const props = defineProps<{
    item: DayItem;
    timeType: TimeTypeOption;
    firmness: FirmnessOption;
}>();

// Below this length a block is too short for text, so it shows only its
// colour and the details are in its hover.
const SHORT_MINUTES = 10;
// From this length a block has room for the time on a line of its own.
const STACKED_MINUTES = 25;
// From this length a block also has room for its location.
const LOCATION_MINUTES = 35;

const minutes = computed(() => props.item.endMinute - props.item.startMinute);

const itemDuration = computed(() => duration(minutes.value));

const placement = computed(() => {
    const width = 100 / props.item.columns;

    return {
        top: `${props.item.startMinute * PIXELS_PER_MINUTE}px`,
        height: `${minutes.value * PIXELS_PER_MINUTE}px`,
        left: `${props.item.column * width}%`,
        width: `calc(${width}% - 2px)`,
    };
});

// How firm the calendar item is changes only the tint.
const colours = computed(() => ({
    ...categoryColours(props.item.category),
    ...firmnessFill(
        props.item.firmness,
        props.item.category?.colour ?? null,
        DAY_TINT,
    ),
}));

// Confirmed is the default, so like Exact it isn't announced.
const spokenFirmness = computed(() =>
    props.item.firmness === 'confirmed'
        ? ''
        : `, ${props.firmness.label.toLowerCase()}`,
);
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
                :data-firmness="item.firmness"
                data-test="day-item"
            >
                <span v-if="minutes < SHORT_MINUTES" class="sr-only"
                    >{{ item.title }}{{ spokenFirmness }}, {{ item.time }}</span
                >
                <template v-else>
                    <span
                        :class="[
                            'truncate font-medium',
                            titleText(item.firmness),
                        ]"
                    >
                        <span
                            v-if="item.subCategory"
                            class="mr-1"
                            aria-hidden="true"
                            >{{ item.subCategory.emoji }}</span
                        >{{ item.title
                        }}<span v-if="spokenFirmness" class="sr-only">{{
                            spokenFirmness
                        }}</span>
                    </span>
                    <span
                        :class="[
                            'flex shrink-0 items-center gap-1 tabular-nums',
                            secondaryText(item.firmness),
                        ]"
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
                        :class="[
                            'hover:text-foreground flex max-w-full items-center gap-1 self-start underline-offset-4 hover:underline',
                            secondaryText(item.firmness),
                        ]"
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
            <CalendarItemDetails
                :item="item"
                :time-type="timeType"
                :firmness="firmness"
                :duration="itemDuration"
            />
        </TooltipContent>
    </Tooltip>
</template>

<script setup lang="ts">
import { Clock, MapPin } from '@lucide/vue';
import { computed } from 'vue';
import CalendarItemDetails from '@/components/CalendarItemDetails.vue';
import CalendarItemTime from '@/components/CalendarItemTime.vue';
import FlagIcons from '@/components/FlagIcons.vue';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useEditCalendarItem } from '@/composables/useEditCalendarItem';
import {
    categoryColours,
    DAY_TINT,
    duration,
    PIXELS_PER_MINUTE,
} from '@/lib/calendarTimeline';
import { firmnessFill, secondaryText, titleText } from '@/lib/firmness';
import type { DayItem, FirmnessOption, TimeTypeOption } from '@/types';

// A calendar item as a block on a day's timeline, placed to the minute, with
// its details in a hover. Time before and after it is a lighter band joined
// to it above and below, with a dotted edge, so it doesn't look like the item.
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

// How much of the time before and after is on this day.
const minutesBefore = computed(
    () => props.item.startMinute - props.item.beforeMinute,
);
const minutesAfter = computed(
    () => props.item.afterMinute - props.item.endMinute,
);

// From this height a band has room to say how long it is.
const BAND_LABEL_MINUTES = 8;

const placement = computed(() => {
    const width = 100 / props.item.columns;

    return {
        top: `${props.item.beforeMinute * PIXELS_PER_MINUTE}px`,
        height: `${(props.item.afterMinute - props.item.beforeMinute) * PIXELS_PER_MINUTE}px`,
        left: `${props.item.column * width}%`,
        width: `calc(${width}% - 2px)`,
    };
});

// A fainter tint of the category's colour, or grey when uncategorised.
const bandColours = computed(() => {
    const colour = props.item.category?.colour ?? 'var(--muted-foreground)';
    const tint = props.item.category ? DAY_TINT / 2.5 : DAY_TINT / 4;

    return {
        backgroundColor: `color-mix(in srgb, ${colour} ${tint}%, var(--background))`,
        borderColor: `color-mix(in srgb, ${colour} 45%, transparent)`,
    };
});

const spokenTimeAround = computed(() =>
    [
        props.item.timeBefore &&
            `, ${duration(props.item.timeBefore.minutes)} before`,
        props.item.timeAfter &&
            `, ${duration(props.item.timeAfter.minutes)} after`,
    ]
        .filter(Boolean)
        .join(''),
);

// How firm the calendar item is changes only the tint.
const colours = computed(() => ({
    ...categoryColours(props.item.category),
    ...firmnessFill(
        props.item.firmness,
        props.item.category?.colour ?? null,
        DAY_TINT,
    ),
}));

const spokenFlags = computed(() =>
    props.item.flags.map((flag) => `, ${flag.name}`).join(''),
);

// Confirmed is the default, so like Exact it isn't announced.
const spokenFirmness = computed(() =>
    props.item.firmness === 'confirmed'
        ? ''
        : `, ${props.firmness.label.toLowerCase()}`,
);

// Clicking the calendar item, or pressing Enter on it, opens its edit page.
const { openEdit } = useEditCalendarItem();
</script>

<template>
    <Tooltip :delay-duration="150">
        <TooltipTrigger as-child>
            <li
                tabindex="0"
                class="focus-visible:ring-ring absolute flex cursor-pointer flex-col rounded-md outline-none focus-visible:ring-2"
                :style="placement"
                :data-time-type="item.timeType"
                :data-firmness="item.firmness"
                data-test="day-item"
                @click="openEdit(item.id)"
                @keydown.enter.self="openEdit(item.id)"
            >
                <div
                    v-if="minutesBefore > 0"
                    class="text-muted-foreground flex shrink-0 items-start overflow-hidden rounded-t-md border border-b-0 border-l-4 border-dotted px-2 text-[0.625rem] leading-3"
                    :style="{
                        ...bandColours,
                        height: `${minutesBefore * PIXELS_PER_MINUTE}px`,
                    }"
                    aria-hidden="true"
                    data-test="time-before"
                >
                    <span
                        v-if="
                            item.timeBefore &&
                            minutesBefore >= BAND_LABEL_MINUTES
                        "
                        class="truncate pt-0.5"
                        >{{ duration(item.timeBefore.minutes) }} before</span
                    >
                </div>
                <div
                    :class="[
                        'flex shrink-0 overflow-hidden rounded-md border border-l-4 px-2 text-xs leading-4',
                        !item.category &&
                            'bg-muted border-l-muted-foreground/40',
                        item.timeType === 'block'
                            ? 'border-foreground/40 border-dashed'
                            : 'border-black/10 dark:border-white/20',
                        minutes >= STACKED_MINUTES
                            ? 'flex-col gap-0.5 py-1'
                            : 'items-start gap-1.5',
                        minutesBefore > 0 && 'rounded-t-none',
                        minutesAfter > 0 && 'rounded-b-none',
                        minutes === 0 && 'border-0',
                    ]"
                    :style="{
                        ...colours,
                        height: `${minutes * PIXELS_PER_MINUTE}px`,
                    }"
                    data-test="day-item-block"
                >
                    <span v-if="minutes < SHORT_MINUTES" class="sr-only"
                        >{{ item.title }}{{ spokenFlags }}{{ spokenFirmness }},
                        {{ item.time }}{{ spokenTimeAround }}</span
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
                            }}<FlagIcons
                                :flags="item.flags"
                                class="ml-1 align-[-0.125em]"
                            /><span v-if="spokenFirmness" class="sr-only">{{
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
                            @click.stop
                        >
                            <MapPin
                                class="size-3 shrink-0"
                                aria-hidden="true"
                            />
                            <span class="truncate">{{
                                item.location.name
                            }}</span>
                            <span class="sr-only"
                                >(opens in maps in a new tab)</span
                            >
                        </a>
                        <span v-if="spokenTimeAround" class="sr-only">{{
                            spokenTimeAround
                        }}</span>
                    </template>
                </div>
                <div
                    v-if="minutesAfter > 0"
                    class="text-muted-foreground flex shrink-0 items-end overflow-hidden rounded-b-md border border-t-0 border-l-4 border-dotted px-2 text-[0.625rem] leading-3"
                    :style="{
                        ...bandColours,
                        height: `${minutesAfter * PIXELS_PER_MINUTE}px`,
                    }"
                    aria-hidden="true"
                    data-test="time-after"
                >
                    <span
                        v-if="
                            item.timeAfter && minutesAfter >= BAND_LABEL_MINUTES
                        "
                        class="truncate pb-0.5"
                        >{{ duration(item.timeAfter.minutes) }} after</span
                    >
                </div>
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

<script setup lang="ts">
import { computed } from 'vue';
import CalendarItemDetails from '@/components/CalendarItemDetails.vue';
import FlagIcons from '@/components/FlagIcons.vue';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useEditCalendarItem } from '@/composables/useEditCalendarItem';
import { categoryColours, DAY_TINT } from '@/lib/calendarTimeline';
import { firmnessFill, titleText } from '@/lib/firmness';
import type { FirmnessOption, TimelineItem, TimeTypeOption } from '@/types';

// An all-day calendar item as a bar in the row above a day's timeline, with
// its details in a hover.
const props = defineProps<{
    item: TimelineItem;
    timeType: TimeTypeOption;
    firmness: FirmnessOption;
}>();

// How firm the calendar item is changes only the tint, as on the timeline.
const colours = computed(() => ({
    ...categoryColours(props.item.category),
    ...firmnessFill(
        props.item.firmness,
        props.item.category?.colour ?? null,
        DAY_TINT,
    ),
}));

// Confirmed is the default, so it isn't announced.
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
                :class="[
                    'focus-visible:ring-ring flex min-w-0 cursor-pointer items-center rounded-md border border-l-4 border-black/10 px-2 py-0.5 text-xs leading-4 outline-none focus-visible:ring-2 dark:border-white/20',
                    !item.category && 'bg-muted border-l-muted-foreground/40',
                ]"
                :style="colours"
                :data-firmness="item.firmness"
                data-test="all-day-item"
                @click="openEdit(item.id)"
                @keydown.enter.self="openEdit(item.id)"
            >
                <span
                    :class="['truncate font-medium', titleText(item.firmness)]"
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
                    /><span class="sr-only"
                        >{{ spokenFirmness }}, {{ item.time }}</span
                    >
                </span>
            </li>
        </TooltipTrigger>
        <TooltipContent
            side="top"
            align="start"
            :collision-padding="8"
            class="flex max-w-64 flex-col gap-1 py-2"
            data-test="all-day-item-details"
        >
            <CalendarItemDetails
                :item="item"
                :time-type="timeType"
                :firmness="firmness"
            />
        </TooltipContent>
    </Tooltip>
</template>

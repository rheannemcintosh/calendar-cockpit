<script setup lang="ts">
import { computed } from 'vue';
import CalendarItemBlock from '@/components/CalendarItemBlock.vue';
import { HOURS, PIXELS_PER_MINUTE } from '@/lib/calendarTimeline';
import type { DayItem, FirmnessOption, TimeTypeOption } from '@/types';

// One day on a timeline: its hour lines, its calendar items as blocks placed
// to the minute, and a line for now when `nowMinute` is given.
const props = defineProps<{
    items: DayItem[];
    timeTypes: TimeTypeOption[];
    firmnesses: FirmnessOption[];
    nowMinute?: number | null;
}>();

const timeTypes = computed(() =>
    Object.fromEntries(
        props.timeTypes.map((timeType) => [timeType.value, timeType]),
    ),
);

const firmnesses = computed(() =>
    Object.fromEntries(
        props.firmnesses.map((firmness) => [firmness.value, firmness]),
    ),
);
</script>

<template>
    <div class="relative flex-1 border-l">
        <div
            v-for="hour in HOURS"
            :key="hour"
            class="absolute inset-x-0 border-t first:border-t-0"
            :style="{ top: `${hour * 60 * PIXELS_PER_MINUTE}px` }"
            aria-hidden="true"
        />

        <ol class="absolute inset-0 mr-1 ml-0.5">
            <CalendarItemBlock
                v-for="item in items"
                :key="item.id"
                :item="item"
                :time-type="timeTypes[item.timeType]"
                :firmness="firmnesses[item.firmness]"
            />
        </ol>

        <div
            v-if="nowMinute != null"
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
</template>

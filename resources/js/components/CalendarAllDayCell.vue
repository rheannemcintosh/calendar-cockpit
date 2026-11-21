<script setup lang="ts">
import { computed } from 'vue';
import CalendarAllDayItem from '@/components/CalendarAllDayItem.vue';
import type { FirmnessOption, TimelineItem, TimeTypeOption } from '@/types';

// One day's all-day calendar items, stacked in the row above its timeline.
const props = defineProps<{
    items: TimelineItem[];
    timeTypes: TimeTypeOption[];
    firmnesses: FirmnessOption[];
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
    <div class="min-w-0 flex-1 border-l px-1 py-1">
        <ol v-if="items.length" class="flex flex-col gap-1">
            <CalendarAllDayItem
                v-for="item in items"
                :key="item.id"
                :item="item"
                :time-type="timeTypes[item.timeType]"
                :firmness="firmnesses[item.firmness]"
            />
        </ol>
    </div>
</template>

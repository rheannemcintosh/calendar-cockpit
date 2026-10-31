<script setup lang="ts">
import { computed } from 'vue';
import type { TimeTypeOption } from '@/types';

// A calendar item's times, with its time type in a small pill underneath.
// Exact is the default, so it has no pill. An approximate time also starts
// with "~". Screen readers would read "~" as "tilde", so they hear the time
// type in words instead.
const props = defineProps<{
    time: string;
    timeType: TimeTypeOption;
}>();

const spoken = computed(() => {
    const range = props.time.replace('–', ' to ');

    return {
        exact: null,
        window: `booked slot, ${range}`,
        block: `blocked-out time, ${range}`,
        approximate: `about ${range}`,
    }[props.timeType.value];
});
</script>

<template>
    <span v-if="spoken" class="sr-only">{{ spoken }}</span>
    <span
        :aria-hidden="spoken ? 'true' : undefined"
        class="flex flex-col items-start gap-1"
        :data-test="`time-${props.timeType.value}`"
    >
        <span
            >{{ props.timeType.value === 'approximate' ? '~' : ''
            }}{{ props.time }}</span
        >
        <span
            v-if="props.timeType.value !== 'exact'"
            class="bg-muted text-foreground/80 rounded-full px-1.5 py-0.5 text-[0.6875rem] leading-none font-medium"
            data-test="time-type-pill"
            >{{ props.timeType.label }}</span
        >
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { TimeTypeValue } from '@/types';

// A calendar item's times, styled by its time type: a window is shown in a
// shaded pill and an approximate time starts with "~". Screen readers would
// read "~" as "tilde", so they hear the time type in words instead.
const props = defineProps<{
    time: string;
    timeType: TimeTypeValue;
}>();

const spoken = computed(() => {
    const range = props.time.replace('–', ' to ');

    return {
        exact: null,
        window: `booked slot, ${range}`,
        block: `blocked-out time, ${range}`,
        approximate: `about ${range}`,
    }[props.timeType];
});
</script>

<template>
    <span v-if="spoken" class="sr-only">{{ spoken }}</span>
    <span
        :aria-hidden="spoken ? 'true' : undefined"
        :class="
            cn(
                props.timeType === 'window' &&
                    'bg-muted -mx-2 rounded-full px-2 py-0.5',
            )
        "
        :data-test="`time-${props.timeType}`"
        >{{ props.timeType === 'approximate' ? '~' : '' }}{{ props.time }}</span
    >
</template>

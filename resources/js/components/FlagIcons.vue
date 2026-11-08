<script setup lang="ts">
import { flagIcons } from '@/lib/flags';
import { cn } from '@/lib/utils';
import type { ItemFlag } from '@/types';

// A calendar item's flags as a row of icons. The icons are hidden from screen
// readers, which hear the flags' names instead.
const props = defineProps<{
    flags: ItemFlag[];
    class?: string;
}>();
</script>

<template>
    <span
        v-if="props.flags.length"
        :class="cn('inline-flex items-center gap-0.5', props.class)"
        data-test="flag-icons"
    >
        <component
            :is="flagIcons[flag.icon]"
            v-for="flag in props.flags"
            :key="flag.name"
            class="size-[1em] shrink-0"
            aria-hidden="true"
            :data-test="`flag-icon-${flag.icon}`"
        />
        <span class="sr-only"
            >, {{ props.flags.map((flag) => flag.name).join(', ') }}</span
        >
    </span>
</template>

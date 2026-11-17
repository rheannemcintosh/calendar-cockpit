<script setup lang="ts">
import { HOURS, hourLabel, PIXELS_PER_MINUTE } from '@/lib/calendarTimeline';
</script>

<template>
    <div class="relative w-14 shrink-0" aria-hidden="true">
        <span
            v-for="hour in HOURS"
            :key="hour"
            class="text-muted-foreground absolute right-2 -translate-y-1/2 text-xs tabular-nums first:translate-y-0.5"
            :style="{ top: `${hour * 60 * PIXELS_PER_MINUTE}px` }"
            >{{ hourLabel(hour) }}</span
        >
    </div>
</template>

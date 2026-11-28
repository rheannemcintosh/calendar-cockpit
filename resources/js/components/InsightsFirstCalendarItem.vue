<script setup lang="ts">
import type { InsightsCalendarItem } from '@/types';

// The first calendar item created in a category or sub-category on the
// Insights page, or a note that it has none yet.
defineProps<{
    calendarItem: InsightsCalendarItem | null;
}>();
</script>

<template>
    <p
        class="text-muted-foreground truncate text-xs"
        data-test="first-calendar-item-in"
    >
        <template v-if="calendarItem">
            First calendar item:
            <span class="text-foreground">{{ calendarItem.title }}</span>
            ·
            <span class="tabular-nums">{{ calendarItem.created }}</span>
        </template>
        <template v-else>No calendar items yet</template>
    </p>
</template>

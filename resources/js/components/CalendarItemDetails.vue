<script setup lang="ts">
import { Clock, MapPin } from '@lucide/vue';
import type { TimelineItem, TimeTypeOption } from '@/types';

// A calendar item's details, as shown in the hover of its block on the day,
// week and month views.
defineProps<{
    item: TimelineItem;
    timeType: TimeTypeOption;
    // How long it is on the day shown, e.g. "1 hr 30 min", when known.
    duration?: string;
}>();
</script>

<template>
    <span class="font-medium">
        <span v-if="item.subCategory" class="mr-1" aria-hidden="true">{{
            item.subCategory.emoji
        }}</span
        >{{ item.title }}
    </span>
    <span class="flex items-center gap-1.5 tabular-nums">
        <Clock class="size-3 shrink-0" aria-hidden="true" />
        {{ item.timeType === 'approximate' ? '~' : '' }}{{ item.time
        }}<template v-if="duration"> · {{ duration }}</template>
    </span>
    <span v-if="item.timeType !== 'exact'" class="opacity-80">
        {{ timeType.label }}: {{ timeType.hint }}
    </span>
    <span v-if="item.location" class="flex items-start gap-1.5">
        <MapPin class="mt-0.5 size-3 shrink-0" aria-hidden="true" />
        <span>
            {{ item.location.name }}
            <span v-if="item.location.address" class="block opacity-80">{{
                item.location.address
            }}</span>
        </span>
    </span>
</template>

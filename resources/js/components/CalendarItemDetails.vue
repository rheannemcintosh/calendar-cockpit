<script setup lang="ts">
import { Clock, MapPin } from '@lucide/vue';
import CreatedUpdated from '@/components/CreatedUpdated.vue';
import { flagIcons } from '@/lib/flags';
import type { FirmnessOption, TimelineItem, TimeTypeOption } from '@/types';

// A calendar item's details, as shown in the hover of its block on the day,
// week and month views.
defineProps<{
    item: TimelineItem;
    timeType: TimeTypeOption;
    // Shown when it isn't Confirmed, when the view knows the firmnesses.
    firmness?: FirmnessOption;
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
    <span
        v-if="item.flags.length"
        class="flex flex-wrap items-center gap-x-2.5 gap-y-1"
        data-test="flags"
    >
        <span
            v-for="flag in item.flags"
            :key="flag.name"
            class="flex items-center gap-1"
        >
            <component
                :is="flagIcons[flag.icon]"
                class="size-3 shrink-0"
                aria-hidden="true"
            />
            {{ flag.name }}
        </span>
    </span>
    <span
        v-if="firmness && item.firmness !== 'confirmed'"
        class="opacity-80"
        data-test="firmness"
    >
        {{ firmness.label }}: {{ firmness.hint }}
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
    <CreatedUpdated :timestamps="item.timestamps" class="opacity-80" />
</template>

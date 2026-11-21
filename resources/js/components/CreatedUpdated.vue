<script setup lang="ts">
import type { Timestamps } from '@/types';

// When something was created and, if it has been changed since, last updated.
defineProps<{
    timestamps: Timestamps;
}>();
</script>

<template>
    <span class="tabular-nums" data-test="timestamps">
        Created {{ timestamps.created
        }}<template v-if="timestamps.updated">
            · Updated {{ timestamps.updated }}</template
        >
    </span>
</template>

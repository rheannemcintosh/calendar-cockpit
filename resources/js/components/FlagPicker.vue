<script setup lang="ts">
import { flagIcons } from '@/lib/flags';
import { cn } from '@/lib/utils';
import type { FlagOption } from '@/types';

// Choose any number of flags, including none, each a toggle with its icon and
// name. The chosen ids are submitted through the checkboxes named `name[]`.
const props = defineProps<{
    name: string;
    legend: string;
    options: FlagOption[];
    invalid?: boolean;
}>();

const value = defineModel<number[]>({ default: () => [] });
</script>

<template>
    <fieldset
        class="grid gap-2"
        :aria-invalid="props.invalid || undefined"
        data-test="flag-picker"
    >
        <legend class="mb-2 text-sm font-medium">
            {{ props.legend }}
            <span class="text-muted-foreground font-normal">(optional)</span>
        </legend>

        <div class="flex flex-wrap gap-2">
            <label
                v-for="option in props.options"
                :key="option.id"
                :class="
                    cn(
                        'border-input has-[:focus-visible]:border-ring has-[:focus-visible]:ring-ring/50 flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium shadow-xs transition-[color,box-shadow] select-none has-[:focus-visible]:ring-[3px]',
                        value.includes(option.id)
                            ? 'border-primary bg-accent'
                            : 'text-muted-foreground hover:text-foreground',
                        props.invalid && 'border-destructive',
                    )
                "
                :data-test="`flag-${option.id}`"
            >
                <input
                    v-model="value"
                    type="checkbox"
                    :name="`${props.name}[]`"
                    :value="option.id"
                    class="sr-only"
                />
                <component
                    :is="flagIcons[option.icon]"
                    class="size-4 shrink-0"
                    aria-hidden="true"
                />
                {{ option.name }}
            </label>
        </div>
    </fieldset>
</template>

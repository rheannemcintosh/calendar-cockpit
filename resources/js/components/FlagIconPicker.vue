<script setup lang="ts">
import { flagIcons } from '@/lib/flags';
import { cn } from '@/lib/utils';
import type { FlagIconKey, FlagIconOption } from '@/types';

// Choose one icon from a grid of radio buttons, each showing the icon with its
// label for screen readers and as a tooltip. The chosen icon is submitted
// through the radio buttons named `name`.
const props = defineProps<{
    name: string;
    legend: string;
    options: FlagIconOption[];
    invalid?: boolean;
}>();

const value = defineModel<FlagIconKey | ''>({ default: '' });
</script>

<template>
    <fieldset
        class="grid gap-2"
        :aria-invalid="props.invalid || undefined"
        data-test="flag-icon-picker"
    >
        <legend class="mb-2 text-sm font-medium">{{ props.legend }}</legend>

        <div class="grid grid-cols-6 gap-2 sm:grid-cols-8">
            <label
                v-for="option in props.options"
                :key="option.value"
                :title="option.label"
                :class="
                    cn(
                        'border-input has-[:focus-visible]:border-ring has-[:focus-visible]:ring-ring/50 flex aspect-square cursor-pointer items-center justify-center rounded-md border shadow-xs transition-[color,box-shadow] has-[:focus-visible]:ring-[3px]',
                        value === option.value
                            ? 'border-primary bg-accent text-foreground'
                            : 'text-muted-foreground hover:text-foreground',
                        props.invalid && 'border-destructive',
                    )
                "
                :data-test="`flag-icon-option-${option.value}`"
            >
                <input
                    v-model="value"
                    type="radio"
                    :name="props.name"
                    :value="option.value"
                    class="sr-only"
                />
                <component
                    :is="flagIcons[option.value]"
                    class="size-5"
                    aria-hidden="true"
                />
                <span class="sr-only">{{ option.label }}</span>
            </label>
        </div>
    </fieldset>
</template>

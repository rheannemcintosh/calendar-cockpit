<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { TimeTypeOption, TimeTypeValue } from '@/types';

// Choose one time type from a list of radio buttons, each with its label and
// hint. The chosen value is submitted through the radio buttons named `name`.
const props = defineProps<{
    name: string;
    legend: string;
    options: TimeTypeOption[];
    invalid?: boolean;
}>();

const value = defineModel<TimeTypeValue>({ required: true });
</script>

<template>
    <fieldset
        class="grid gap-2"
        :aria-invalid="props.invalid || undefined"
        :data-test="`${props.name}-picker`"
    >
        <legend class="mb-2 text-sm font-medium">{{ props.legend }}</legend>

        <div class="grid gap-2 sm:grid-cols-2">
            <label
                v-for="option in props.options"
                :key="option.value"
                :class="
                    cn(
                        'border-input has-[:focus-visible]:border-ring has-[:focus-visible]:ring-ring/50 flex cursor-pointer items-start gap-3 rounded-md border px-3 py-2 shadow-xs transition-[color,box-shadow] has-[:focus-visible]:ring-[3px]',
                        value === option.value && 'border-primary bg-accent',
                        props.invalid && 'border-destructive',
                    )
                "
                :data-test="`${props.name}-${option.value}`"
            >
                <input
                    v-model="value"
                    type="radio"
                    :name="props.name"
                    :value="option.value"
                    class="accent-primary mt-0.5 size-4 shrink-0 cursor-pointer"
                />
                <span class="grid gap-0.5">
                    <span class="text-sm font-medium">{{ option.label }}</span>
                    <span class="text-muted-foreground text-sm">
                        {{ option.hint }}
                    </span>
                </span>
            </label>
        </div>
    </fieldset>
</template>

<script setup lang="ts">
import { Check, ChevronDown } from '@lucide/vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxPortal,
    ComboboxRoot,
    ComboboxTrigger,
    ComboboxViewport,
} from 'reka-ui';
import { computed } from 'vue';
import { timezoneLabel, timezoneOffset } from '@/lib/timezone';
import { cn } from '@/lib/utils';

// Choose a timezone, with how far it is from UTC right now. Typing filters
// the list by name, such as "London" or "New York". The chosen timezone is
// submitted through a hidden input named `name`.
const props = defineProps<{
    id: string;
    name: string;
    // In alphabetical order.
    timezones: string[];
    invalid?: boolean;
}>();

const value = defineModel<string>({ required: true });

const options = computed(() =>
    props.timezones.map((timezone) => ({
        value: timezone,
        label: timezoneLabel(timezone),
        offset: timezoneOffset(timezone),
    })),
);

function display(timezone: string | undefined): string {
    return timezone ? timezoneLabel(timezone) : '';
}
</script>

<template>
    <ComboboxRoot v-model="value" open-on-click>
        <input type="hidden" :name="name" :value="value" />

        <ComboboxAnchor
            :aria-invalid="invalid || undefined"
            :class="
                cn(
                    'border-input dark:bg-input/30 flex h-9 w-full min-w-0 items-center gap-2 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] md:text-sm',
                    'has-[:focus-visible]:border-ring has-[:focus-visible]:ring-ring/50 has-[:focus-visible]:ring-[3px]',
                    'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                )
            "
        >
            <ComboboxInput
                :id="id"
                :display-value="display"
                placeholder="Search for a timezone"
                class="placeholder:text-muted-foreground min-w-0 flex-1 bg-transparent outline-none"
                :aria-invalid="invalid || undefined"
                data-test="timezone-picker-input"
            />
            <span
                v-if="value"
                class="text-muted-foreground shrink-0 text-xs"
                data-test="timezone-picker-offset"
                >{{ timezoneOffset(value) }}</span
            >
            <ComboboxTrigger
                class="text-muted-foreground hover:text-foreground shrink-0 cursor-pointer"
                aria-label="Show timezones"
            >
                <ChevronDown class="size-4 opacity-50" />
            </ComboboxTrigger>
        </ComboboxAnchor>

        <ComboboxPortal>
            <ComboboxContent
                position="popper"
                :side-offset="4"
                class="bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 z-50 w-(--reka-combobox-trigger-width) rounded-md border p-1 shadow-md"
            >
                <ComboboxViewport class="max-h-72">
                    <ComboboxEmpty
                        class="text-muted-foreground px-2 py-1.5 text-sm"
                    >
                        No timezones match.
                    </ComboboxEmpty>

                    <ComboboxItem
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                        :text-value="option.label"
                        class="data-highlighted:bg-accent data-highlighted:text-accent-foreground relative flex w-full cursor-pointer items-center gap-2 rounded-sm py-1.5 pr-8 pl-2 text-sm outline-none select-none"
                    >
                        <span class="truncate">{{ option.label }}</span>
                        <span
                            class="text-muted-foreground ml-auto shrink-0 text-xs"
                            >{{ option.offset }}</span
                        >
                        <ComboboxItemIndicator
                            class="absolute right-2 flex size-3.5 items-center justify-center"
                        >
                            <Check class="size-4" />
                        </ComboboxItemIndicator>
                    </ComboboxItem>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>
</template>

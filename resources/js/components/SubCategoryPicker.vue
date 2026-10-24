<script setup lang="ts">
import { Check, ChevronDown } from '@lucide/vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxLabel,
    ComboboxPortal,
    ComboboxRoot,
    ComboboxTrigger,
    ComboboxViewport,
} from 'reka-ui';
import { computed, ref } from 'vue';
import { cn } from '@/lib/utils';
import type { CategoryListItem } from '@/types';

// Choose one of the user's sub-categories, listed under its category, or
// none. Typing filters the list by category or sub-category name. The chosen
// sub-category's id is submitted through a hidden input named `name`, and is
// empty when none is chosen.
const props = defineProps<{
    id: string;
    name: string;
    // The user's categories that have sub-categories, in alphabetical order.
    categories: CategoryListItem[];
    invalid?: boolean;
}>();

// The value of the "None" option, since a sub-category id is never 0.
const NONE = 0;

const value = ref<number>(NONE);

const chosen = computed(() => {
    for (const category of props.categories) {
        const subCategory = category.subCategories.find(
            (subCategory) => subCategory.id === value.value,
        );

        if (subCategory) {
            return { category, subCategory };
        }
    }

    return undefined;
});

function display(): string {
    return chosen.value
        ? `${chosen.value.subCategory.emoji} ${chosen.value.category.name} · ${chosen.value.subCategory.name}`
        : '';
}
</script>

<template>
    <ComboboxRoot v-model="value" open-on-click>
        <input
            type="hidden"
            :name="name"
            :value="chosen ? chosen.subCategory.id : ''"
        />

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
            <span
                v-if="chosen"
                class="size-3 shrink-0 rounded-full border border-black/10 dark:border-white/20"
                :style="{ backgroundColor: chosen.category.colour }"
                aria-hidden="true"
                data-test="sub-category-picker-swatch"
            />
            <ComboboxInput
                :id="id"
                :display-value="display"
                placeholder="None"
                class="placeholder:text-muted-foreground min-w-0 flex-1 bg-transparent outline-none"
                :aria-invalid="invalid || undefined"
            />
            <ComboboxTrigger
                class="text-muted-foreground hover:text-foreground shrink-0"
                aria-label="Show categories"
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
                        No categories match.
                    </ComboboxEmpty>

                    <ComboboxItem
                        :value="NONE"
                        text-value="None"
                        class="data-highlighted:bg-accent data-highlighted:text-accent-foreground relative flex w-full cursor-default items-center gap-2 rounded-sm py-1.5 pr-8 pl-2 text-sm outline-none select-none"
                    >
                        <span class="text-muted-foreground">None</span>
                        <ComboboxItemIndicator
                            class="absolute right-2 flex size-3.5 items-center justify-center"
                        >
                            <Check class="size-4" />
                        </ComboboxItemIndicator>
                    </ComboboxItem>

                    <ComboboxGroup
                        v-for="category in categories"
                        :key="category.id"
                    >
                        <ComboboxLabel
                            class="text-muted-foreground flex items-center gap-2 px-2 pt-2 pb-1 text-xs font-medium"
                        >
                            <span
                                class="size-3 shrink-0 rounded-full border border-black/10 dark:border-white/20"
                                :style="{ backgroundColor: category.colour }"
                                aria-hidden="true"
                            />
                            <span class="truncate">{{ category.name }}</span>
                        </ComboboxLabel>

                        <ComboboxItem
                            v-for="subCategory in category.subCategories"
                            :key="subCategory.id"
                            :value="subCategory.id"
                            :text-value="`${category.name} ${subCategory.name}`"
                            class="data-highlighted:bg-accent data-highlighted:text-accent-foreground relative flex w-full cursor-default items-center gap-2 rounded-sm py-1.5 pr-8 pl-2 text-sm outline-none select-none"
                        >
                            <span aria-hidden="true">{{
                                subCategory.emoji
                            }}</span>
                            <span class="truncate">{{ subCategory.name }}</span>
                            <ComboboxItemIndicator
                                class="absolute right-2 flex size-3.5 items-center justify-center"
                            >
                                <Check class="size-4" />
                            </ComboboxItemIndicator>
                        </ComboboxItem>
                    </ComboboxGroup>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>
</template>

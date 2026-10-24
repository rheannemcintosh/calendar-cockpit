<script setup lang="ts">
import { computed } from 'vue';
import ColourPicker from '@/components/ColourPicker.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

// The name, colour and preview fields shared by the create and edit category
// forms. The parent wraps them in its own form.
defineProps<{
    errors: Partial<Record<string, string>>;
}>();

const name = defineModel<string>('name', { default: '' });
const colour = defineModel<string>('colour', { default: '' });

const previewColour = computed(() =>
    /^#?[0-9A-Fa-f]{6}$/.test(colour.value.trim())
        ? `#${colour.value.trim().replace('#', '')}`
        : undefined,
);
</script>

<template>
    <div class="grid gap-2">
        <Label for="name">Name</Label>
        <Input
            id="name"
            v-model="name"
            name="name"
            maxlength="50"
            placeholder="Work, Family, Health…"
            :aria-invalid="errors.name ? true : undefined"
        />
        <InputError :message="errors.name" />
    </div>

    <div class="grid gap-2">
        <Label for="colour">Colour</Label>
        <ColourPicker
            id="colour"
            v-model="colour"
            name="colour"
            :invalid="!!errors.colour"
        />
        <InputError :message="errors.colour" />
    </div>

    <div class="grid gap-2">
        <span class="text-sm font-medium">Preview</span>
        <div
            class="flex items-center gap-3 rounded-md border px-3 py-2"
            data-test="category-preview"
        >
            <span
                class="size-4 shrink-0 rounded-full border border-black/10 dark:border-white/20"
                :class="{ 'border-dashed': !previewColour }"
                :style="{ backgroundColor: previewColour }"
                aria-hidden="true"
            />
            <span
                :class="{ 'text-muted-foreground': !name.trim() }"
                class="truncate text-sm"
            >
                {{ name.trim() || 'Category name' }}
            </span>
        </div>
    </div>
</template>

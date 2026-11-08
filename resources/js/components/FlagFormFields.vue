<script setup lang="ts">
import FlagIconPicker from '@/components/FlagIconPicker.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { flagIcons } from '@/lib/flags';
import type { FlagIconKey, FlagIconOption } from '@/types';

// The name, icon and preview fields shared by the add and edit flag forms.
// The parent wraps them in its own form.
defineProps<{
    errors: Partial<Record<string, string>>;
    icons: FlagIconOption[];
}>();

const name = defineModel<string>('name', { default: '' });
const icon = defineModel<FlagIconKey | ''>('icon', { default: '' });
</script>

<template>
    <div class="grid gap-2">
        <Label for="name">Name</Label>
        <Input
            id="name"
            v-model="name"
            name="name"
            maxlength="50"
            placeholder="Pre-Paid, Booked, Can't miss…"
            :aria-invalid="errors.name ? true : undefined"
        />
        <InputError :message="errors.name" />
    </div>

    <div class="grid gap-2">
        <FlagIconPicker
            v-model="icon"
            name="icon"
            legend="Icon"
            :options="icons"
            :invalid="!!errors.icon"
        />
        <InputError :message="errors.icon" />
    </div>

    <div class="grid gap-2">
        <span class="text-sm font-medium">Preview</span>
        <div
            class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm"
            data-test="flag-preview"
        >
            <component
                :is="flagIcons[icon]"
                v-if="icon"
                class="size-4 shrink-0"
                aria-hidden="true"
            />
            <span
                v-else
                class="size-4 shrink-0 rounded-sm border border-dashed"
                aria-hidden="true"
            />
            <span
                :class="{ 'text-muted-foreground': !name.trim() }"
                class="truncate"
            >
                {{ name.trim() || 'Flag name' }}
            </span>
        </div>
    </div>
</template>

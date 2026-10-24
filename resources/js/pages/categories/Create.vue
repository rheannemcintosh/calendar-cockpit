<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import ColourPicker from '@/components/ColourPicker.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create } from '@/routes/categories';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: create(),
            },
            {
                title: 'New category',
                href: create(),
            },
        ],
    },
});

const name = ref('');
const colour = ref('');

const previewColour = computed(() =>
    /^#?[0-9A-Fa-f]{6}$/.test(colour.value.trim())
        ? `#${colour.value.trim().replace('#', '')}`
        : undefined,
);
</script>

<template>
    <Head title="New category" />

    <div class="mx-auto w-full max-w-3xl p-4">
        <Heading
            title="New category"
            description="Give it a name and a colour you will recognise at a glance"
        />

        <Form
            v-bind="CategoryController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
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

            <Button :disabled="processing" data-test="create-category-button">
                Create category
            </Button>
        </Form>
    </div>
</template>

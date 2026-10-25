<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { ref } from 'vue';
import SubCategoryController from '@/actions/App/Http/Controllers/SubCategoryController';
import EmojiPicker from '@/components/EmojiPicker.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import categories from '@/routes/categories';
import type { CategoryOption, SubCategoryListItem } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: categories.index(),
            },
            {
                // The last breadcrumb is not a link, so it needs no URL with
                // the sub-category in it.
                title: 'Edit sub-category',
                href: categories.index(),
            },
        ],
    },
});

const props = defineProps<{
    // The sub-category being changed, as it is now.
    subCategory: SubCategoryListItem;
    // The category it is in, which cannot be changed.
    category: CategoryOption;
}>();

const emoji = ref(props.subCategory.emoji);
const name = ref(props.subCategory.name);
</script>

<template>
    <Head title="Edit sub-category" />

    <div class="mx-auto w-full max-w-3xl p-4">
        <Link
            :href="categories.index()"
            class="text-muted-foreground hover:text-foreground mb-4 inline-flex items-center gap-1.5 text-sm"
            data-test="back-to-categories-link"
        >
            <ArrowLeft class="size-4" />
            Back to categories
        </Link>

        <Heading
            title="Edit sub-category"
            description="Change its emoji or name. Calendar items in it show the change too"
        />

        <Form
            v-bind="SubCategoryController.update.form(props.subCategory.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <span class="text-sm font-medium">Category</span>
                <div
                    class="bg-muted/50 flex items-center gap-2 rounded-md border px-3 py-2 text-sm"
                    data-test="sub-category-category"
                >
                    <span
                        class="size-3 shrink-0 rounded-full border border-black/10 dark:border-white/20"
                        :style="{ backgroundColor: props.category.colour }"
                        aria-hidden="true"
                    />
                    <span class="truncate">{{ props.category.name }}</span>
                </div>
                <p class="text-muted-foreground text-sm">
                    A sub-category can't be moved to another category.
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="emoji">Emoji</Label>
                <EmojiPicker
                    id="emoji"
                    v-model="emoji"
                    name="emoji"
                    :invalid="!!errors.emoji"
                />
                <InputError :message="errors.emoji" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    v-model="name"
                    name="name"
                    maxlength="50"
                    placeholder="Meetings, Deep work, Admin…"
                    :aria-invalid="errors.name ? true : undefined"
                />
                <InputError :message="errors.name" />
            </div>

            <Button
                :disabled="processing"
                data-test="update-sub-category-button"
            >
                Save changes
            </Button>
        </Form>
    </div>
</template>

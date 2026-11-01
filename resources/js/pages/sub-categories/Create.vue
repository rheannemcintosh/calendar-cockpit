<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, ref } from 'vue';
import SubCategoryController from '@/actions/App/Http/Controllers/SubCategoryController';
import EmojiPicker from '@/components/EmojiPicker.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import TimeTypePicker from '@/components/TimeTypePicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useEmojiSuggestion } from '@/composables/useEmojiSuggestion';
import categories from '@/routes/categories';
import { create } from '@/routes/sub-categories';
import type { CategoryOption, TimeTypeOption, TimeTypeValue } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: categories.index(),
            },
            {
                title: 'New sub-category',
                href: create(),
            },
        ],
    },
});

const props = defineProps<{
    // The user's categories, in alphabetical order.
    categories: CategoryOption[];
    // The category to start with chosen, or null to leave it to the user.
    categoryId: number | null;
    // Every time type, in order, to choose the default from.
    timeTypes: TimeTypeOption[];
}>();

const categoryId = ref(props.categoryId?.toString() ?? '');
const name = ref('');
const emoji = ref('');
const defaultTimeType = ref<TimeTypeValue>('exact');

const { loading: suggestingEmoji } = useEmojiSuggestion(
    name,
    emoji,
    () => Number(categoryId.value) || null,
);

const chosenCategory = computed(() =>
    props.categories.find(
        (category) => category.id.toString() === categoryId.value,
    ),
);
</script>

<template>
    <Head title="New sub-category" />

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
            title="New sub-category"
            description="Split a category into finer pieces, each with its own emoji"
        />

        <div
            v-if="props.categories.length === 0"
            class="rounded-md border border-dashed p-6 text-center"
            data-test="no-categories"
        >
            <p class="text-muted-foreground text-sm">
                You need a category before you can add a sub-category to it.
            </p>
            <Button as-child class="mt-4">
                <Link :href="categories.create()">Create a category</Link>
            </Button>
        </div>

        <Form
            v-else
            v-bind="SubCategoryController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="category_id">Category</Label>
                <Select v-model="categoryId" name="category_id">
                    <SelectTrigger
                        id="category_id"
                        class="w-full"
                        :aria-invalid="errors.category_id ? true : undefined"
                    >
                        <span class="flex min-w-0 items-center gap-2">
                            <span
                                v-if="chosenCategory"
                                class="size-3 shrink-0 rounded-full border border-black/10 dark:border-white/20"
                                :style="{
                                    backgroundColor: chosenCategory.colour,
                                }"
                                aria-hidden="true"
                            />
                            <SelectValue placeholder="Choose a category" />
                        </span>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="category in props.categories"
                            :key="category.id"
                            :value="category.id.toString()"
                        >
                            <span
                                class="size-3 shrink-0 rounded-full border border-black/10 dark:border-white/20"
                                :style="{ backgroundColor: category.colour }"
                                aria-hidden="true"
                            />
                            <span class="truncate">{{ category.name }}</span>
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.category_id" />
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

            <div class="grid gap-2">
                <Label for="emoji">Emoji</Label>
                <EmojiPicker
                    id="emoji"
                    v-model="emoji"
                    name="emoji"
                    :invalid="!!errors.emoji"
                    :loading="suggestingEmoji"
                />
                <InputError :message="errors.emoji" />
            </div>

            <div class="grid gap-2">
                <TimeTypePicker
                    v-model="defaultTimeType"
                    name="default_time_type"
                    legend="Default time type"
                    :options="props.timeTypes"
                    :invalid="!!errors.default_time_type"
                />
                <p class="text-muted-foreground text-sm">
                    New calendar items in this sub-category start with this time
                    type.
                </p>
                <InputError :message="errors.default_time_type" />
            </div>

            <Button
                :disabled="processing"
                data-test="create-sub-category-button"
            >
                Create sub-category
            </Button>
        </Form>
    </div>
</template>

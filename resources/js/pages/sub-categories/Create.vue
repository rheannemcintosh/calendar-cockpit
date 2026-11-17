<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, LoaderCircle, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import SubCategoryController from '@/actions/App/Http/Controllers/SubCategoryController';
import EmojiPicker from '@/components/EmojiPicker.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import ChoicePicker from '@/components/ChoicePicker.vue';
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
import { useCategorySuggestion } from '@/composables/useCategorySuggestion';
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

const name = ref('');
const categoryId = ref(props.categoryId?.toString() ?? '');
const emoji = ref('');
const defaultTimeType = ref<TimeTypeValue>('exact');

const {
    loading: suggestingCategory,
    hint: categoryHint,
    acceptHint: acceptCategoryHint,
    dismissHint: dismissCategoryHint,
} = useCategorySuggestion(name, categoryId);

const hintedCategory = computed(() =>
    props.categories.find(
        (category) => category.id.toString() === categoryHint.value,
    ),
);

// The hint's buttons go away once used, so focus returns to the category.
function focusCategory(): void {
    document.getElementById('category_id')?.focus();
}

function acceptHintedCategory(): void {
    acceptCategoryHint();
    focusCategory();
}

function dismissHintedCategory(): void {
    dismissCategoryHint();
    focusCategory();
}

const {
    loading: suggestingEmoji,
    hint: emojiHint,
    acceptHint: acceptEmojiHint,
    dismissHint: dismissEmojiHint,
} = useEmojiSuggestion(name, emoji, () => Number(categoryId.value) || null);

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
                <Label for="category_id">Category</Label>
                <Select v-model="categoryId" name="category_id">
                    <SelectTrigger
                        id="category_id"
                        class="w-full"
                        :aria-invalid="errors.category_id ? true : undefined"
                        :aria-busy="suggestingCategory || undefined"
                        :data-loading="suggestingCategory || undefined"
                    >
                        <span class="flex min-w-0 items-center gap-2">
                            <span
                                v-if="chosenCategory"
                                :class="[
                                    'size-3 shrink-0 rounded-full border border-black/10 dark:border-white/20',
                                    { 'animate-pulse': suggestingCategory },
                                ]"
                                :style="{
                                    backgroundColor: chosenCategory.colour,
                                }"
                                aria-hidden="true"
                            />
                            <LoaderCircle
                                v-else-if="suggestingCategory"
                                class="text-muted-foreground size-3 shrink-0 animate-spin"
                                aria-hidden="true"
                                data-test="category-suggestion-loading"
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
                <div
                    v-if="hintedCategory"
                    class="bg-muted/50 flex w-fit max-w-full items-center rounded-md border text-sm"
                    data-test="category-suggestion-hint"
                    @keydown.esc.prevent.stop="dismissHintedCategory"
                >
                    <button
                        type="button"
                        class="hover:bg-accent focus-visible:ring-ring/50 flex h-8 min-w-0 cursor-pointer items-center gap-2 rounded-l-md px-2 outline-none focus-visible:ring-[3px]"
                        data-test="accept-category-suggestion"
                        @click="acceptHintedCategory"
                    >
                        <span
                            class="size-3 shrink-0 rounded-full border border-black/10 dark:border-white/20"
                            :style="{ backgroundColor: hintedCategory.colour }"
                            aria-hidden="true"
                        />
                        <span class="truncate">
                            Put it in {{ hintedCategory.name }} instead?
                        </span>
                    </button>
                    <button
                        type="button"
                        aria-label="Dismiss suggested category"
                        title="Dismiss"
                        class="text-muted-foreground hover:bg-accent hover:text-foreground focus-visible:ring-ring/50 flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-r-md outline-none focus-visible:ring-[3px]"
                        data-test="dismiss-category-suggestion"
                        @click="dismissHintedCategory"
                    >
                        <X class="size-4" />
                    </button>
                </div>
                <p role="status" class="sr-only">
                    {{
                        hintedCategory
                            ? `Suggested category: ${hintedCategory.name}. Put it there instead, or press Escape to dismiss it.`
                            : ''
                    }}
                </p>
                <InputError :message="errors.category_id" />
            </div>

            <div class="grid gap-2">
                <Label for="emoji">Emoji</Label>
                <EmojiPicker
                    id="emoji"
                    v-model="emoji"
                    name="emoji"
                    :invalid="!!errors.emoji"
                    :loading="suggestingEmoji"
                    :hint="emojiHint"
                    @accept-hint="acceptEmojiHint"
                    @dismiss-hint="dismissEmojiHint"
                />
                <InputError :message="errors.emoji" />
            </div>

            <div class="grid gap-2">
                <ChoicePicker
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

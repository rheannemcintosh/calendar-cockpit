<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CalendarItemController from '@/actions/App/Http/Controllers/CalendarItemController';
import DatePicker from '@/components/DatePicker.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import SubCategoryPicker from '@/components/SubCategoryPicker.vue';
import TimePicker from '@/components/TimePicker.vue';
import TimeTypePicker from '@/components/TimeTypePicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/calendar-items';
import categories from '@/routes/categories';
import type { CategoryListItem, TimeTypeOption, TimeTypeValue } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Calendar items',
                href: index(),
            },
            {
                title: 'New calendar item',
                href: create(),
            },
        ],
    },
});

const props = defineProps<{
    // The user's categories that have sub-categories, in alphabetical order.
    categories: CategoryListItem[];
    // Every time type, in order, to choose from.
    timeTypes: TimeTypeOption[];
}>();

const subCategoryId = ref(0);
const timeType = ref<TimeTypeValue>('exact');

const chosenSubCategory = computed(() =>
    props.categories
        .flatMap((category) => category.subCategories)
        .find((subCategory) => subCategory.id === subCategoryId.value),
);

// Choosing a sub-category always starts the time type from its default.
watch(chosenSubCategory, (subCategory) => {
    if (subCategory) {
        timeType.value = subCategory.defaultTimeType;
    }
});

// A reminder, not an error, when the time type is not the sub-category's
// default. The calendar item can still be created.
const timeTypeWarning = computed(() => {
    const subCategory = chosenSubCategory.value;

    if (!subCategory || subCategory.defaultTimeType === timeType.value) {
        return undefined;
    }

    const label = props.timeTypes.find(
        (option) => option.value === subCategory.defaultTimeType,
    )?.label;

    return `${subCategory.name} is usually ${label}`;
});
</script>

<template>
    <Head title="New calendar item" />

    <div class="mx-auto w-full max-w-3xl p-4">
        <Heading
            title="New calendar item"
            description="Give it a title, then choose the day and when it starts and ends"
        />

        <Form
            v-bind="CalendarItemController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="title">Title</Label>
                <Input
                    id="title"
                    name="title"
                    maxlength="255"
                    placeholder="What is happening?"
                    :aria-invalid="errors.title ? true : undefined"
                />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="date">Date</Label>
                <DatePicker id="date" name="date" :invalid="!!errors.date" />
                <InputError :message="errors.date" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="start_time">Start time</Label>
                    <TimePicker
                        id="start_time"
                        name="start_time"
                        :invalid="!!errors.start_time"
                    />
                    <InputError :message="errors.start_time" />
                </div>

                <div class="grid gap-2">
                    <Label for="end_time">End time</Label>
                    <TimePicker
                        id="end_time"
                        name="end_time"
                        :invalid="!!errors.end_time"
                    />
                    <InputError :message="errors.end_time" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="sub_category_id">Category</Label>
                <template v-if="props.categories.length > 0">
                    <SubCategoryPicker
                        id="sub_category_id"
                        v-model="subCategoryId"
                        name="sub_category_id"
                        :categories="props.categories"
                        :invalid="!!errors.sub_category_id"
                    />
                    <InputError :message="errors.sub_category_id" />
                </template>
                <p
                    v-else
                    class="text-muted-foreground text-sm"
                    data-test="no-sub-categories"
                >
                    Add a sub-category to one of your categories to sort
                    calendar items.
                    <Link
                        :href="categories.index()"
                        class="text-foreground underline underline-offset-4"
                    >
                        Set up your categories
                    </Link>
                </p>
            </div>

            <div class="grid gap-2">
                <TimeTypePicker
                    v-model="timeType"
                    name="time_type"
                    legend="Time type"
                    :options="props.timeTypes"
                    :invalid="!!errors.time_type"
                />
                <p
                    v-if="timeTypeWarning"
                    class="flex items-center gap-1.5 text-sm text-amber-700 dark:text-amber-400"
                    role="status"
                    data-test="time-type-warning"
                >
                    <TriangleAlert class="size-4 shrink-0" aria-hidden="true" />
                    {{ timeTypeWarning }}
                </p>
                <InputError :message="errors.time_type" />
            </div>

            <Button
                :disabled="processing"
                data-test="create-calendar-item-button"
            >
                Create calendar item
            </Button>
        </Form>
    </div>
</template>

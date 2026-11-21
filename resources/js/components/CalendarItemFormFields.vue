<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ChoicePicker from '@/components/ChoicePicker.vue';
import DatePicker from '@/components/DatePicker.vue';
import FlagPicker from '@/components/FlagPicker.vue';
import InputError from '@/components/InputError.vue';
import LocationPicker from '@/components/LocationPicker.vue';
import SubCategoryPicker from '@/components/SubCategoryPicker.vue';
import TimePicker from '@/components/TimePicker.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import categories from '@/routes/categories';
import type {
    CategoryListItem,
    EditableCalendarItem,
    FirmnessOption,
    FirmnessValue,
    FlagOption,
    TimeTypeOption,
    TimeTypeValue,
} from '@/types';

// The fields of the create and edit calendar item forms. They start empty,
// or filled in with a calendar item's current details when one is given.
const props = defineProps<{
    // The user's categories that have sub-categories, in alphabetical order.
    categories: CategoryListItem[];
    // Every time type, in order, to choose from.
    timeTypes: TimeTypeOption[];
    // Every firmness, in order, to choose from.
    firmnesses: FirmnessOption[];
    // The user's flags, in the order they are shown, to choose any of.
    flags: FlagOption[];
    // Whether a location can be searched for. It is off without a Mapbox token.
    locationSearch: boolean;
    errors: Record<string, string>;
    // The calendar item being edited, as it is now.
    item?: EditableCalendarItem;
}>();

const allDay = ref(props.item?.allDay ?? false);
const startDate = ref<string | undefined>(props.item?.date);
const endDate = ref<string | undefined>(props.item?.endDate);

// The end date starts as the start date, and keeps following it until a
// different end date is chosen, so a same-day item only needs one date.
watch(startDate, (start, previous) => {
    if (!endDate.value || endDate.value === previous) {
        endDate.value = start;
    }
});

// Time before and after, in minutes, is optional. Left empty, there is none.
// It is checked here as it is typed, the same way the server checks it, and
// a problem is shown where the server's would be.
const MAX_EXTRA_MINUTES = 720;
const minutesBefore = ref<string | number>(props.item?.minutesBefore || '');
const minutesAfter = ref<string | number>(props.item?.minutesAfter || '');

function extraMinutesError(
    minutes: string | number,
    which: 'before' | 'after',
): string | undefined {
    const value = String(minutes).trim();

    if (
        value === '' ||
        (/^\d+$/.test(value) && Number(value) <= MAX_EXTRA_MINUTES)
    ) {
        return undefined;
    }

    return `Please enter the time ${which} in whole minutes, up to 12 hours.`;
}

const minutesBeforeError = computed(
    () =>
        props.errors.minutes_before ??
        extraMinutesError(minutesBefore.value, 'before'),
);
const minutesAfterError = computed(
    () =>
        props.errors.minutes_after ??
        extraMinutesError(minutesAfter.value, 'after'),
);

const subCategoryId = ref(props.item?.subCategoryId ?? 0);
const timeType = ref<TimeTypeValue>(props.item?.timeType ?? 'exact');
const firmness = ref<FirmnessValue>(props.item?.firmness ?? 'confirmed');
const flagIds = ref<number[]>(props.item?.flagIds ?? []);

// When editing, a time type the user chose, before or on this page, is kept.
const timeTypeChosen = ref(props.item?.timeTypeOverridden ?? false);

function chooseTimeType(): void {
    if (props.item) {
        timeTypeChosen.value = true;
    }
}

const chosenSubCategory = computed(() =>
    props.categories
        .flatMap((category) => category.subCategories)
        .find((subCategory) => subCategory.id === subCategoryId.value),
);

// Choosing a sub-category starts the time type from its default, unless the
// time type was chosen while editing.
watch(chosenSubCategory, (subCategory) => {
    if (subCategory && !timeTypeChosen.value) {
        timeType.value = subCategory.defaultTimeType;
    }
});

// A reminder, not an error, when the time type is not the sub-category's
// default. The calendar item can still be saved.
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

// The flags are submitted one by one, so a problem with any of them is shown
// once, under the flags.
const flagError = computed(
    () =>
        Object.entries(props.errors).find(([key]) =>
            key.startsWith('flag_ids'),
        )?.[1],
);

// The location is chosen as a whole, so a problem with any part of it is
// shown once, under the location.
const locationError = computed(
    () =>
        props.errors.location_name ??
        props.errors.location_address ??
        props.errors.location_latitude ??
        props.errors.location_longitude,
);
</script>

<template>
    <div class="grid gap-2">
        <Label for="title">Title</Label>
        <Input
            id="title"
            name="title"
            :default-value="item?.title"
            maxlength="255"
            placeholder="What is happening?"
            :aria-invalid="errors.title ? true : undefined"
        />
        <InputError :message="errors.title" />
    </div>

    <div class="grid gap-2">
        <Label for="all_day" class="cursor-pointer font-normal">
            <Checkbox
                id="all_day"
                :model-value="allDay"
                class="cursor-pointer"
                :aria-invalid="errors.all_day ? true : undefined"
                data-test="all-day-checkbox"
                @update:model-value="(value) => (allDay = value === true)"
            />
            All day
            <span class="text-muted-foreground"> (no start or end time) </span>
        </Label>
        <input type="hidden" name="all_day" :value="allDay ? 1 : 0" />
        <InputError :message="errors.all_day" />
    </div>

    <!-- An all-day calendar item has only dates, so it has no times or time type. -->
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid content-start gap-2">
            <Label for="date">Start date</Label>
            <DatePicker
                id="date"
                v-model="startDate"
                name="date"
                :invalid="!!errors.date"
            />
            <InputError :message="errors.date" />
        </div>

        <div v-show="!allDay" class="grid content-start gap-2">
            <Label for="start_time">Start time</Label>
            <TimePicker
                id="start_time"
                name="start_time"
                :default-value="item?.startTime"
                :invalid="!!errors.start_time"
            />
            <InputError :message="errors.start_time" />
        </div>

        <div class="grid content-start gap-2">
            <Label for="end_date">End date</Label>
            <DatePicker
                id="end_date"
                v-model="endDate"
                name="end_date"
                :invalid="!!errors.end_date"
            />
            <InputError :message="errors.end_date" />
        </div>

        <div v-show="!allDay" class="grid content-start gap-2">
            <Label for="end_time">End time</Label>
            <TimePicker
                id="end_time"
                name="end_time"
                :default-value="item?.endTime"
                :invalid="!!errors.end_time"
            />
            <InputError :message="errors.end_time" />
        </div>
    </div>

    <!-- Time around the calendar item, such as for getting there or running
    over. It doesn't change the item's own times. -->
    <div
        v-show="!allDay"
        class="grid gap-4 sm:grid-cols-2"
        data-test="time-around"
    >
        <div class="grid content-start gap-2">
            <Label for="minutes_before">
                Time before
                <span class="text-muted-foreground font-normal">
                    (minutes, optional)
                </span>
            </Label>
            <Input
                id="minutes_before"
                v-model="minutesBefore"
                name="minutes_before"
                type="number"
                inputmode="numeric"
                step="any"
                placeholder="E.g. 20 to get there"
                :aria-invalid="minutesBeforeError ? true : undefined"
            />
            <InputError :message="minutesBeforeError" />
        </div>

        <div class="grid content-start gap-2">
            <Label for="minutes_after">
                Time after
                <span class="text-muted-foreground font-normal">
                    (minutes, optional)
                </span>
            </Label>
            <Input
                id="minutes_after"
                v-model="minutesAfter"
                name="minutes_after"
                type="number"
                inputmode="numeric"
                step="any"
                placeholder="E.g. 15 if it runs over"
                :aria-invalid="minutesAfterError ? true : undefined"
            />
            <InputError :message="minutesAfterError" />
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
            Add a sub-category to one of your categories to sort calendar items.
            <Link
                :href="categories.index()"
                class="text-foreground underline underline-offset-4"
            >
                Set up your categories
            </Link>
        </p>
    </div>

    <div v-if="!allDay" class="grid gap-2">
        <ChoicePicker
            v-model="timeType"
            name="time_type"
            legend="Time type"
            :options="props.timeTypes"
            :invalid="!!errors.time_type"
            @update:model-value="chooseTimeType"
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

    <div class="grid gap-2">
        <ChoicePicker
            v-model="firmness"
            name="firmness"
            legend="Firmness"
            :options="props.firmnesses"
            :invalid="!!errors.firmness"
        />
        <InputError :message="errors.firmness" />
    </div>

    <div v-if="props.flags.length" class="grid gap-2">
        <FlagPicker
            v-model="flagIds"
            name="flag_ids"
            legend="Flags"
            :options="props.flags"
            :invalid="!!flagError"
        />
        <InputError :message="flagError" />
    </div>

    <div v-if="props.locationSearch" class="grid gap-2">
        <Label for="location">
            Location
            <span class="text-muted-foreground font-normal"> (optional) </span>
        </Label>
        <LocationPicker
            id="location"
            :location="item?.location"
            :invalid="!!locationError"
        />
        <InputError :message="locationError" />
    </div>

    <!-- Without location search the location can't be changed, so keep the
    calendar item's current one. -->
    <template v-else-if="item?.location">
        <input type="hidden" name="location_name" :value="item.location.name" />
        <input
            type="hidden"
            name="location_address"
            :value="item.location.address"
        />
        <input
            type="hidden"
            name="location_latitude"
            :value="item.location.latitude"
        />
        <input
            type="hidden"
            name="location_longitude"
            :value="item.location.longitude"
        />
    </template>
</template>

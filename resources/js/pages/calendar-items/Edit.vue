<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import CalendarItemController from '@/actions/App/Http/Controllers/CalendarItemController';
import CalendarItemFormFields from '@/components/CalendarItemFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/calendar-items';
import type {
    CalendarView,
    CategoryListItem,
    EditableCalendarItem,
    FirmnessOption,
    FlagOption,
    TimeTypeOption,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Calendar items',
                href: index(),
            },
            {
                // The last breadcrumb is not a link, so it needs no URL with
                // the calendar item in it.
                title: 'Edit calendar item',
                href: index(),
            },
        ],
    },
});

const props = defineProps<{
    // The calendar item being changed, as it is now.
    calendarItem: EditableCalendarItem;
    // The same choices as the create calendar item form.
    categories: CategoryListItem[];
    timeTypes: TimeTypeOption[];
    firmnesses: FirmnessOption[];
    flags: FlagOption[];
    locationSearch: boolean;
    // The calendar view it was opened from, which saving returns to.
    view: CalendarView;
    // That view on the day the calendar item starts, which cancelling returns to.
    backUrl: string;
}>();
</script>

<template>
    <Head title="Edit calendar item" />

    <div class="mx-auto w-full max-w-3xl p-4">
        <Heading
            title="Edit calendar item"
            description="Change any of its details. Nothing is changed until you save"
        />

        <Form
            v-bind="CalendarItemController.update.form(props.calendarItem.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <input type="hidden" name="view" :value="props.view" />

            <CalendarItemFormFields
                :categories="props.categories"
                :time-types="props.timeTypes"
                :firmnesses="props.firmnesses"
                :flags="props.flags"
                :location-search="props.locationSearch"
                :errors="errors"
                :item="props.calendarItem"
            />

            <div class="flex items-center gap-2">
                <Button
                    :disabled="processing"
                    data-test="update-calendar-item-button"
                >
                    Save changes
                </Button>
                <Button variant="outline" as-child>
                    <Link
                        :href="props.backUrl"
                        data-test="cancel-edit-calendar-item-link"
                    >
                        Cancel
                    </Link>
                </Button>
            </div>
        </Form>
    </div>
</template>

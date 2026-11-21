<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import CalendarItemController from '@/actions/App/Http/Controllers/CalendarItemController';
import CalendarItemFormFields from '@/components/CalendarItemFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/calendar-items';
import type {
    CategoryListItem,
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
    // Every firmness, in order, to choose from.
    firmnesses: FirmnessOption[];
    // The user's flags, in the order they are shown, to choose any of.
    flags: FlagOption[];
    // Whether a location can be searched for. It is off without a Mapbox token.
    locationSearch: boolean;
}>();
</script>

<template>
    <Head title="New calendar item" />

    <div class="mx-auto w-full max-w-3xl p-4">
        <Heading
            title="New calendar item"
            description="Give it a title, then choose when it starts and ends, or make it all day"
        />

        <Form
            v-bind="CalendarItemController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <CalendarItemFormFields
                :categories="props.categories"
                :time-types="props.timeTypes"
                :firmnesses="props.firmnesses"
                :flags="props.flags"
                :location-search="props.locationSearch"
                :errors="errors"
            />

            <Button
                :disabled="processing"
                data-test="create-calendar-item-button"
            >
                Create calendar item
            </Button>
        </Form>
    </div>
</template>

<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import CalendarItemController from '@/actions/App/Http/Controllers/CalendarItemController';
import DatePicker from '@/components/DatePicker.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import TimePicker from '@/components/TimePicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/calendar-items';

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
</script>

<template>
    <Head title="New calendar item" />

    <div class="mx-auto w-full max-w-xl p-4">
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

            <Button
                :disabled="processing"
                data-test="create-calendar-item-button"
            >
                Create calendar item
            </Button>
        </Form>
    </div>
</template>

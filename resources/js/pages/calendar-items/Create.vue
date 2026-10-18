<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import CalendarItemController from '@/actions/App/Http/Controllers/CalendarItemController';
import DateTimePicker from '@/components/DateTimePicker.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/calendar-items';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Events',
                href: index(),
            },
            {
                title: 'New event',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="New event" />

    <div class="max-w-xl p-4">
        <Heading
            title="New event"
            description="Give your event a title and choose when it starts and ends"
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

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="starts_at">Start</Label>
                    <DateTimePicker
                        id="starts_at"
                        name="starts_at"
                        :invalid="!!errors.starts_at"
                    />
                    <InputError :message="errors.starts_at" />
                </div>

                <div class="grid gap-2">
                    <Label for="ends_at">End</Label>
                    <DateTimePicker
                        id="ends_at"
                        name="ends_at"
                        :invalid="!!errors.ends_at"
                    />
                    <InputError :message="errors.ends_at" />
                </div>
            </div>

            <Button :disabled="processing" data-test="create-event-button">
                Create event
            </Button>
        </Form>
    </div>
</template>

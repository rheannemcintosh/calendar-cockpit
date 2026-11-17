<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import FlagController from '@/actions/App/Http/Controllers/FlagController';
import FlagFormFields from '@/components/FlagFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/flags';
import type { FlagIconKey, FlagIconOption } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Flags',
                href: index(),
            },
            {
                title: 'New flag',
                href: create(),
            },
        ],
    },
});

defineProps<{
    // Every icon a flag can show, in order.
    icons: FlagIconOption[];
}>();

const name = ref('');
const icon = ref<FlagIconKey | ''>('');
</script>

<template>
    <Head title="New flag" />

    <div class="mx-auto w-full max-w-3xl p-4">
        <Heading
            title="New flag"
            description="Give it a name and an icon to mark the commitments that matter to you"
        />

        <Form
            v-bind="FlagController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <FlagFormFields
                v-model:name="name"
                v-model:icon="icon"
                :icons="icons"
                :errors="errors"
            />

            <Button :disabled="processing" data-test="create-flag-button">
                Add flag
            </Button>
        </Form>
    </div>
</template>

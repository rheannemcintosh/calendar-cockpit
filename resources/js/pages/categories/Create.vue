<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import CategoryFormFields from '@/components/CategoryFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/categories';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: index(),
            },
            {
                title: 'New category',
                href: create(),
            },
        ],
    },
});

const name = ref('');
const colour = ref('');
</script>

<template>
    <Head title="New category" />

    <div class="mx-auto w-full max-w-3xl p-4">
        <Heading
            title="New category"
            description="Give it a name and a colour you will recognise at a glance"
        />

        <Form
            v-bind="CategoryController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <CategoryFormFields
                v-model:name="name"
                v-model:colour="colour"
                :errors="errors"
            />

            <Button :disabled="processing" data-test="create-category-button">
                Create category
            </Button>
        </Form>
    </div>
</template>

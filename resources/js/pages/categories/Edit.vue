<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { ref } from 'vue';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import CategoryFormFields from '@/components/CategoryFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/categories';
import type { CategoryOption } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: index(),
            },
            {
                // The last breadcrumb is not a link, so it needs no URL with
                // the category in it.
                title: 'Edit category',
                href: index(),
            },
        ],
    },
});

const props = defineProps<{
    // The category being changed, as it is now.
    category: CategoryOption;
}>();

const name = ref(props.category.name);
const colour = ref(props.category.colour);
</script>

<template>
    <Head title="Edit category" />

    <div class="mx-auto w-full max-w-3xl p-4">
        <Link
            :href="index()"
            class="text-muted-foreground hover:text-foreground mb-4 inline-flex items-center gap-1.5 text-sm"
            data-test="back-to-categories-link"
        >
            <ArrowLeft class="size-4" />
            Back to categories
        </Link>

        <Heading
            title="Edit category"
            description="Change its name or colour. Calendar items in it show the change too"
        />

        <Form
            v-bind="CategoryController.update.form(props.category.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <CategoryFormFields
                v-model:name="name"
                v-model:colour="colour"
                :errors="errors"
            />

            <Button :disabled="processing" data-test="update-category-button">
                Save changes
            </Button>
        </Form>
    </div>
</template>

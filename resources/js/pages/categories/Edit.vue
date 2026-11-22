<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, History } from '@lucide/vue';
import { ref } from 'vue';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import CategoryFormFields from '@/components/CategoryFormFields.vue';
import CreatedUpdated from '@/components/CreatedUpdated.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { history, index } from '@/routes/categories';
import type { CategoryOption, Timestamps } from '@/types';

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
    // When it was created and last updated.
    timestamps: Timestamps;
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

        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Edit category"
                description="Change its name or colour. Calendar items in it show the change too"
            />
            <Button variant="outline" as-child>
                <Link
                    :href="history(props.category.id)"
                    data-test="category-history-link"
                >
                    <History />
                    History
                </Link>
            </Button>
        </div>

        <p class="text-muted-foreground -mt-6 mb-8 text-sm">
            <CreatedUpdated :timestamps="props.timestamps" />
        </p>

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

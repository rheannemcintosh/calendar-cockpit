<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import HistoryEntries from '@/components/HistoryEntries.vue';
import { Button } from '@/components/ui/button';
import { edit, index } from '@/routes/categories';
import type { CategoryHistoryEntry } from '@/types';

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
                title: 'Category history',
                href: index(),
            },
        ],
    },
});

const props = defineProps<{
    category: { id: number; name: string };
    // Newest first, ending with when it was created.
    entries: CategoryHistoryEntry[];
}>();
</script>

<template>
    <Head title="Category history" />

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
                title="Category history"
                :description="`Each change made to ${props.category.name}, newest first`"
            />
            <Button variant="outline" as-child>
                <Link
                    :href="edit(props.category.id)"
                    data-test="edit-from-history-link"
                >
                    <Pencil />
                    Edit
                </Link>
            </Button>
        </div>

        <HistoryEntries :entries="props.entries" :colour-fields="['colour']" />
    </div>
</template>

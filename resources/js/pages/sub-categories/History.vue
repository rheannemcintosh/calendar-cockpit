<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import HistoryEntries from '@/components/HistoryEntries.vue';
import { Button } from '@/components/ui/button';
import categories from '@/routes/categories';
import { edit } from '@/routes/sub-categories';
import type { SubCategoryHistoryEntry } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: categories.index(),
            },
            {
                // The last breadcrumb is not a link, so it needs no URL with
                // the sub-category in it.
                title: 'Sub-category history',
                href: categories.index(),
            },
        ],
    },
});

const props = defineProps<{
    subCategory: { id: number; emoji: string; name: string };
    // Newest first, ending with when it was created.
    entries: SubCategoryHistoryEntry[];
}>();
</script>

<template>
    <Head title="Sub-category history" />

    <div class="mx-auto w-full max-w-3xl p-4">
        <Link
            :href="categories.index()"
            class="text-muted-foreground hover:text-foreground mb-4 inline-flex items-center gap-1.5 text-sm"
            data-test="back-to-categories-link"
        >
            <ArrowLeft class="size-4" />
            Back to categories
        </Link>

        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Sub-category history"
                :description="`Each change made to ${props.subCategory.emoji} ${props.subCategory.name}, newest first`"
            />
            <Button variant="outline" as-child>
                <Link
                    :href="edit(props.subCategory.id)"
                    data-test="edit-from-history-link"
                >
                    <Pencil />
                    Edit
                </Link>
            </Button>
        </div>

        <HistoryEntries :entries="props.entries" />
    </div>
</template>

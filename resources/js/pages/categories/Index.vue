<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronRight, Pencil, Plus, Tags, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { create, destroy, edit, index } from '@/routes/categories';
import subCategories from '@/routes/sub-categories';
import type { ManagedCategory, ManagedSubCategory } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: index(),
            },
        ],
    },
});

defineProps<{
    // The user's categories and their sub-categories, in alphabetical order.
    categories: ManagedCategory[];
}>();

// What the delete dialog is asking to confirm, or null when it is closed.
type PendingDelete = {
    form: ReturnType<typeof destroy.form>;
    title: string;
    description: string;
};

const pendingDelete = ref<PendingDelete | null>(null);

function plural(count: number, one: string, many: string): string {
    return `${count} ${count === 1 ? one : many}`;
}

function subCategoryCount(category: ManagedCategory): string {
    return plural(
        category.subCategories.length,
        'sub-category',
        'sub-categories',
    );
}

// What happens to the calendar items that use what is being deleted.
function calendarItemsNote(count: number): string {
    if (!count) {
        return 'No calendar items are affected.';
    }

    return `${plural(count, 'calendar item', 'calendar items')} will be kept but will no longer have a category.`;
}

function confirmDeleteCategory(category: ManagedCategory): void {
    const withSubCategories = category.subCategories.length
        ? ` and its ${subCategoryCount(category)}`
        : '';

    pendingDelete.value = {
        form: destroy.form(category.id),
        title: `Delete ${category.name}?`,
        description: `This will permanently delete the ${category.name} category${withSubCategories}. ${calendarItemsNote(category.calendarItemsCount)}`,
    };
}

function confirmDeleteSubCategory(subCategory: ManagedSubCategory): void {
    pendingDelete.value = {
        form: subCategories.destroy.form(subCategory.id),
        title: `Delete ${subCategory.name}?`,
        description: `This will permanently delete the ${subCategory.name} sub-category. ${calendarItemsNote(subCategory.calendarItemsCount)}`,
    };
}

function closeDeleteDialog(open: boolean): void {
    if (!open) {
        pendingDelete.value = null;
    }
}
</script>

<template>
    <Head title="Categories" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Categories"
                description="How you sort your calendar items, and the sub-categories in each one"
            />
            <Button as-child>
                <Link :href="create()" data-test="new-category-link">
                    <Plus />
                    New category
                </Link>
            </Button>
        </div>

        <div
            v-if="!categories.length"
            class="flex flex-col items-center gap-4 rounded-xl border border-dashed p-10 text-center"
            data-test="no-categories"
        >
            <Tags class="text-muted-foreground size-10" />
            <div class="space-y-1">
                <p class="font-medium">You have no categories yet</p>
                <p class="text-muted-foreground text-sm">
                    Create your first category to start sorting your calendar
                    items.
                </p>
            </div>
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    Create a category
                </Link>
            </Button>
        </div>

        <ul v-else class="space-y-3">
            <li v-for="category in categories" :key="category.id">
                <Collapsible
                    v-slot="{ open }"
                    :default-open="true"
                    class="rounded-lg border"
                >
                    <div class="flex items-center gap-2 py-2 pr-2 pl-1">
                        <CollapsibleTrigger
                            class="hover:bg-accent focus-visible:ring-ring/50 flex min-w-0 flex-1 items-center gap-3 rounded-md px-2 py-1.5 text-left outline-none focus-visible:ring-[3px]"
                        >
                            <ChevronRight
                                :class="[
                                    'text-muted-foreground size-4 shrink-0 transition-transform motion-reduce:transition-none',
                                    open && 'rotate-90',
                                ]"
                                aria-hidden="true"
                            />
                            <span
                                class="size-4 shrink-0 rounded-full border border-black/10 dark:border-white/20"
                                :style="{ backgroundColor: category.colour }"
                                aria-hidden="true"
                            />
                            <span class="truncate font-medium">
                                {{ category.name }}
                            </span>
                            <span
                                class="text-muted-foreground shrink-0 text-sm"
                            >
                                {{ subCategoryCount(category) }}
                            </span>
                        </CollapsibleTrigger>
                        <Button
                            variant="ghost"
                            size="sm"
                            as-child
                            class="shrink-0"
                        >
                            <Link
                                :href="edit(category.id)"
                                :aria-label="`Edit ${category.name}`"
                                data-test="edit-category-link"
                            >
                                <Pencil />
                                <span class="hidden sm:inline">Edit</span>
                            </Link>
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="text-destructive hover:text-destructive shrink-0"
                            :aria-label="`Delete ${category.name}`"
                            data-test="delete-category-button"
                            @click="confirmDeleteCategory(category)"
                        >
                            <Trash2 />
                            <span class="hidden sm:inline">Delete</span>
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            as-child
                            class="shrink-0"
                        >
                            <Link
                                :href="
                                    subCategories.create({
                                        query: { category: category.id },
                                    })
                                "
                                :aria-label="`Add sub-category to ${category.name}`"
                                data-test="add-sub-category-link"
                            >
                                <Plus />
                                <span class="hidden sm:inline">
                                    Add sub-category
                                </span>
                            </Link>
                        </Button>
                    </div>

                    <CollapsibleContent class="border-t">
                        <table
                            v-if="category.subCategories.length"
                            class="w-full text-sm"
                        >
                            <caption class="sr-only">
                                Sub-categories in
                                {{
                                    category.name
                                }}
                            </caption>
                            <thead class="text-muted-foreground bg-muted/50">
                                <tr>
                                    <th
                                        scope="col"
                                        class="w-20 px-4 py-2 text-left font-medium"
                                    >
                                        Emoji
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-4 py-2 text-left font-medium"
                                    >
                                        Name
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-4 py-2 text-left font-medium"
                                    >
                                        Default time type
                                    </th>
                                    <th scope="col" class="w-px px-2 py-2">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y border-t">
                                <tr
                                    v-for="subCategory in category.subCategories"
                                    :key="subCategory.id"
                                >
                                    <td
                                        class="px-4 py-2.5 text-lg leading-none"
                                    >
                                        {{ subCategory.emoji }}
                                    </td>
                                    <td class="px-4 py-2.5 break-words">
                                        {{ subCategory.name }}
                                    </td>
                                    <td class="px-4 py-2.5">
                                        {{ subCategory.defaultTimeTypeLabel }}
                                    </td>
                                    <td
                                        class="px-2 py-1 text-right whitespace-nowrap"
                                    >
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    subCategories.edit(
                                                        subCategory.id,
                                                    )
                                                "
                                                :aria-label="`Edit ${subCategory.name}`"
                                                data-test="edit-sub-category-link"
                                            >
                                                <Pencil />
                                                <span class="hidden sm:inline">
                                                    Edit
                                                </span>
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="text-destructive hover:text-destructive"
                                            :aria-label="`Delete ${subCategory.name}`"
                                            data-test="delete-sub-category-button"
                                            @click="
                                                confirmDeleteSubCategory(
                                                    subCategory,
                                                )
                                            "
                                        >
                                            <Trash2 />
                                            <span class="hidden sm:inline">
                                                Delete
                                            </span>
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <p
                            v-else
                            class="text-muted-foreground px-4 py-4 text-sm"
                            data-test="no-sub-categories"
                        >
                            No sub-categories yet. This category can't be used
                            on calendar items until it has one.
                        </p>
                    </CollapsibleContent>
                </Collapsible>
            </li>
        </ul>

        <Dialog :open="pendingDelete !== null" @update:open="closeDeleteDialog">
            <DialogContent v-if="pendingDelete">
                <Form
                    v-bind="pendingDelete.form"
                    :options="{ preserveScroll: true }"
                    class="space-y-6"
                    v-slot="{ processing }"
                    @success="pendingDelete = null"
                >
                    <DialogHeader class="space-y-3">
                        <DialogTitle>{{ pendingDelete.title }}</DialogTitle>
                        <DialogDescription data-test="delete-description">
                            {{ pendingDelete.description }}
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Cancel</Button>
                        </DialogClose>

                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                            data-test="confirm-delete-button"
                        >
                            Delete
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Flag, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { flagIcons } from '@/lib/flags';
import { create, destroy, edit, index } from '@/routes/flags';
import type { ManagedFlag } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Flags',
                href: index(),
            },
        ],
    },
});

defineProps<{
    // The user's flags, in the order they are shown on calendar items.
    flags: ManagedFlag[];
}>();

// The flag the delete dialog is asking to confirm, or null when it is closed.
const pendingDelete = ref<ManagedFlag | null>(null);

function calendarItemCount(count: number): string {
    return `${count} ${count === 1 ? 'calendar item' : 'calendar items'}`;
}

// What happens to the calendar items that have the flag being deleted.
function deleteDescription(flag: ManagedFlag): string {
    const items = flag.calendarItemsCount
        ? `${calendarItemCount(flag.calendarItemsCount)} ${flag.calendarItemsCount === 1 ? 'has' : 'have'} it. ${flag.calendarItemsCount === 1 ? 'It' : 'They'} will be kept but will no longer have this flag.`
        : 'No calendar items have it.';

    return `This will permanently delete the ${flag.name} flag. ${items}`;
}

function closeDeleteDialog(open: boolean): void {
    if (!open) {
        pendingDelete.value = null;
    }
}
</script>

<template>
    <Head title="Flags" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Flags"
                description="The kinds of commitment you can mark your calendar items with"
            />
            <Button as-child>
                <Link :href="create()" data-test="new-flag-link">
                    <Plus />
                    New flag
                </Link>
            </Button>
        </div>

        <div
            v-if="!flags.length"
            class="flex flex-col items-center gap-4 rounded-xl border border-dashed p-10 text-center"
            data-test="no-flags"
        >
            <Flag class="text-muted-foreground size-10" />
            <div class="space-y-1">
                <p class="font-medium">You have no flags</p>
                <p class="text-muted-foreground text-sm">
                    Add a flag to mark the commitments that matter to you.
                </p>
            </div>
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    Add a flag
                </Link>
            </Button>
        </div>

        <ul v-else class="divide-y rounded-lg border" data-test="flags-list">
            <li
                v-for="flag in flags"
                :key="flag.id"
                class="flex items-center gap-3 py-2 pr-2 pl-4"
                :data-test="`flag-row-${flag.id}`"
            >
                <component
                    :is="flagIcons[flag.icon]"
                    class="size-4 shrink-0"
                    aria-hidden="true"
                    :data-test="`flag-icon-${flag.icon}`"
                />
                <span class="min-w-0 flex-1 truncate font-medium">
                    {{ flag.name }}
                </span>
                <span class="text-muted-foreground shrink-0 text-sm">
                    {{ calendarItemCount(flag.calendarItemsCount) }}
                </span>
                <Button variant="ghost" size="sm" as-child class="shrink-0">
                    <Link
                        :href="edit(flag.id)"
                        :aria-label="`Edit ${flag.name}`"
                        data-test="edit-flag-link"
                    >
                        <Pencil />
                        <span class="hidden sm:inline">Edit</span>
                    </Link>
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    class="text-destructive hover:text-destructive shrink-0"
                    :aria-label="`Delete ${flag.name}`"
                    data-test="delete-flag-button"
                    @click="pendingDelete = flag"
                >
                    <Trash2 />
                    <span class="hidden sm:inline">Delete</span>
                </Button>
            </li>
        </ul>

        <Dialog :open="pendingDelete !== null" @update:open="closeDeleteDialog">
            <DialogContent v-if="pendingDelete">
                <Form
                    v-bind="destroy.form(pendingDelete.id)"
                    :options="{ preserveScroll: true }"
                    class="space-y-6"
                    v-slot="{ processing }"
                    @success="pendingDelete = null"
                >
                    <DialogHeader class="space-y-3">
                        <DialogTitle
                            >Delete {{ pendingDelete.name }}?</DialogTitle
                        >
                        <DialogDescription data-test="delete-description">
                            {{ deleteDescription(pendingDelete) }}
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

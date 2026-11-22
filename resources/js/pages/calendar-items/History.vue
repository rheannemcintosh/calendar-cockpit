<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import HistoryEntries from '@/components/HistoryEntries.vue';
import { Button } from '@/components/ui/button';
import { edit, index } from '@/routes/calendar-items';
import type { CalendarItemHistoryEntry, CalendarView } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Calendar items',
                href: index(),
            },
            {
                // The last breadcrumb is not a link, so it needs no URL with
                // the calendar item in it.
                title: 'Calendar item history',
                href: index(),
            },
        ],
    },
});

const props = defineProps<{
    calendarItem: { id: number; title: string };
    // Newest first, ending with when it was created.
    entries: CalendarItemHistoryEntry[];
    // The calendar view it was opened from, which editing returns to.
    view: CalendarView;
    // That view on the day the calendar item starts, which going back returns to.
    backUrl: string;
}>();
</script>

<template>
    <Head title="Calendar item history" />

    <div class="mx-auto w-full max-w-3xl p-4">
        <Link
            :href="props.backUrl"
            class="text-muted-foreground hover:text-foreground mb-4 inline-flex items-center gap-1.5 text-sm"
            data-test="back-from-history-link"
        >
            <ArrowLeft class="size-4" />
            Back to calendar
        </Link>

        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Calendar item history"
                :description="`Each change made to ${props.calendarItem.title}, newest first`"
            />
            <Button variant="outline" as-child>
                <Link
                    :href="
                        edit(props.calendarItem.id, {
                            query: { view: props.view },
                        })
                    "
                    data-test="edit-from-history-link"
                >
                    <Pencil />
                    Edit
                </Link>
            </Button>
        </div>

        <!-- A change from its category can change the colour. -->
        <HistoryEntries :entries="props.entries" :colour-fields="['colour']" />
    </div>
</template>

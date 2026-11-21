<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Pencil } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
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

        <ol class="relative space-y-6 border-l pl-6" data-test="history">
            <li
                v-for="entry in props.entries"
                :key="entry.key"
                class="relative"
                :data-event="entry.event"
                data-test="history-entry"
            >
                <span
                    class="bg-background ring-border absolute top-1.5 -left-[1.875rem] size-3 rounded-full ring-2"
                    aria-hidden="true"
                />
                <p class="text-sm font-medium">
                    {{ entry.event === 'created' ? 'Created' : 'Changed' }}
                    <span class="text-muted-foreground font-normal">
                        ·
                        <time :datetime="entry.datetime" class="tabular-nums">{{
                            entry.at
                        }}</time>
                    </span>
                </p>

                <dl
                    v-if="entry.changes.length"
                    class="mt-2 divide-y rounded-lg border text-sm"
                >
                    <div
                        v-for="change in entry.changes"
                        :key="change.field"
                        class="flex flex-col gap-1 px-3 py-2 sm:flex-row sm:gap-4"
                        :data-field="change.field"
                        data-test="history-change"
                    >
                        <dt class="text-muted-foreground shrink-0 sm:w-44">
                            {{ change.label }}
                        </dt>
                        <dd
                            class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1"
                        >
                            <span
                                :class="[
                                    'break-words',
                                    change.old === null
                                        ? 'text-muted-foreground italic'
                                        : 'line-through decoration-1',
                                ]"
                                data-test="old-value"
                                ><span class="sr-only">From </span
                                >{{ change.old ?? 'None' }}</span
                            >
                            <ArrowRight
                                class="text-muted-foreground size-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            <span
                                :class="[
                                    'font-medium break-words',
                                    change.new === null &&
                                        'text-muted-foreground font-normal italic',
                                ]"
                                data-test="new-value"
                                ><span class="sr-only">to </span
                                >{{ change.new ?? 'None' }}</span
                            >
                        </dd>
                    </div>
                </dl>
            </li>
        </ol>
    </div>
</template>

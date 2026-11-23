<script setup lang="ts">
import { ArrowRight } from '@lucide/vue';
import type { HistoryEntry } from '@/types';

const SOURCE_NAMES = { category: 'category', sub_category: 'sub-category' };

// What the entry says happened, e.g. "Changed" or, when its sub-category
// was deleted, "Uncategorised because its sub-category Gym was deleted".
function title(entry: HistoryEntry): string {
    if (entry.event === 'created') {
        return 'Created';
    }

    if (!entry.source) {
        return 'Changed';
    }

    const source = SOURCE_NAMES[entry.source.type];

    return entry.source.action === 'deleted'
        ? `Uncategorised because its ${source} ${entry.source.name} was deleted`
        : `Its ${source} ${entry.source.name} was changed`;
}

const props = withDefaults(
    defineProps<{
        // Newest first, ending with when it was created.
        entries: HistoryEntry[];
        // The fields whose values are colours, which show a swatch beside them.
        colourFields?: string[];
    }>(),
    { colourFields: () => [] },
);
</script>

<template>
    <ol class="relative space-y-6 border-l pl-6" data-test="history">
        <li
            v-for="entry in props.entries"
            :key="entry.key"
            class="relative"
            :data-event="entry.event"
            :data-source="entry.source?.type"
            data-test="history-entry"
        >
            <span
                class="bg-background ring-border absolute top-1.5 -left-[1.875rem] size-3 rounded-full ring-2"
                aria-hidden="true"
            />
            <p class="text-sm font-medium">
                {{ title(entry) }}
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
                                'inline-flex items-center gap-1.5 break-words',
                                change.old === null
                                    ? 'text-muted-foreground italic'
                                    : 'line-through decoration-1',
                            ]"
                            data-test="old-value"
                            ><span
                                v-if="
                                    change.old !== null &&
                                    props.colourFields.includes(change.field)
                                "
                                class="size-3.5 shrink-0 rounded-full border border-black/10 dark:border-white/20"
                                :style="{ backgroundColor: change.old }"
                                aria-hidden="true"
                                data-test="old-swatch"
                            /><span class="sr-only">From </span
                            >{{ change.old ?? 'None' }}</span
                        >
                        <ArrowRight
                            class="text-muted-foreground size-3.5 shrink-0"
                            aria-hidden="true"
                        />
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 font-medium break-words',
                                change.new === null &&
                                    'text-muted-foreground font-normal italic',
                            ]"
                            data-test="new-value"
                            ><span
                                v-if="
                                    change.new !== null &&
                                    props.colourFields.includes(change.field)
                                "
                                class="size-3.5 shrink-0 rounded-full border border-black/10 dark:border-white/20"
                                :style="{ backgroundColor: change.new }"
                                aria-hidden="true"
                                data-test="new-swatch"
                            /><span class="sr-only">to </span
                            >{{ change.new ?? 'None' }}</span
                        >
                    </dd>
                </div>
            </dl>
        </li>
    </ol>
</template>

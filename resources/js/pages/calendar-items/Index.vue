<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarPlus, History } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/calendar-items';
import type { TimelineYear } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Calendar items',
                href: index(),
            },
        ],
    },
});

defineProps<{
    years: TimelineYear[];
    // The day to load from to show earlier calendar items, or null when there are none.
    previousFrom: string | null;
}>();
</script>

<template>
    <Head title="Calendar items" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <Heading
            title="Calendar items"
            description="What you have planned, by year, week and day"
        />

        <Button
            v-if="previousFrom"
            variant="outline"
            class="self-center"
            as-child
        >
            <Link
                :href="index({ query: { from: previousFrom } })"
                preserve-scroll
                data-test="load-previous-button"
            >
                <History />
                Load previous calendar items
            </Link>
        </Button>

        <div
            v-if="!years.length"
            class="flex flex-col items-center gap-4 rounded-xl border border-dashed p-10 text-center"
        >
            <CalendarPlus class="text-muted-foreground size-10" />
            <div v-if="previousFrom" class="space-y-1">
                <p class="font-medium">Nothing coming up</p>
                <p class="text-muted-foreground text-sm">
                    You have no calendar items from today onwards.
                </p>
            </div>
            <div v-else class="space-y-1">
                <p class="font-medium">You have no calendar items yet</p>
                <p class="text-muted-foreground text-sm">
                    Add your first calendar item to start filling your calendar.
                </p>
            </div>
        </div>

        <section
            v-for="year in years"
            :key="year.year"
            :aria-labelledby="`year-${year.year}`"
            class="space-y-6"
        >
            <h2
                :id="`year-${year.year}`"
                class="text-2xl font-semibold tracking-tight"
            >
                {{ year.year }}
            </h2>

            <section
                v-for="week in year.weeks"
                :key="week.key"
                :aria-labelledby="`week-${week.key}`"
                class="space-y-4 border-l pl-4"
            >
                <h3
                    :id="`week-${week.key}`"
                    class="text-muted-foreground text-sm font-medium"
                >
                    Week {{ week.number }} · {{ week.range }}
                </h3>

                <div v-for="day in week.days" :key="day.date" class="space-y-2">
                    <h4 class="text-sm font-semibold">
                        <time :datetime="day.date">{{ day.label }}</time>
                    </h4>
                    <ul class="divide-y rounded-lg border">
                        <li
                            v-for="item in day.items"
                            :key="item.id"
                            class="flex flex-col gap-0.5 px-4 py-3 sm:flex-row sm:items-baseline sm:gap-4"
                        >
                            <span
                                class="text-muted-foreground shrink-0 text-sm tabular-nums sm:w-44"
                            >
                                {{ item.time }}
                            </span>
                            <span class="font-medium break-words">
                                {{ item.title }}
                            </span>
                        </li>
                    </ul>
                </div>
            </section>
        </section>
    </div>
</template>

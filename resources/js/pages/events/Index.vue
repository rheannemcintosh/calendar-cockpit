<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarPlus } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { index } from '@/routes/events';
import type { TimelineYear } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Events',
                href: index(),
            },
        ],
    },
});

defineProps<{
    years: TimelineYear[];
}>();
</script>

<template>
    <Head title="Events" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <Heading
            title="Events"
            description="Everything you have planned, by year, week and day"
        />

        <div
            v-if="!years.length"
            class="flex flex-col items-center gap-4 rounded-xl border border-dashed p-10 text-center"
        >
            <CalendarPlus class="text-muted-foreground size-10" />
            <div class="space-y-1">
                <p class="font-medium">You have no events yet</p>
                <p class="text-muted-foreground text-sm">
                    Add your first event to start filling your calendar.
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

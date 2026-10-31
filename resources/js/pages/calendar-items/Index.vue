<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarPlus, ExternalLink, History, MapPin, Plus } from '@lucide/vue';
import { nextTick, ref } from 'vue';
import CalendarItemTime from '@/components/CalendarItemTime.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { create, index } from '@/routes/calendar-items';
import type { TimelineYear, TimeTypeOption } from '@/types';

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

const props = defineProps<{
    years: TimelineYear[];
    // The day to load from to show earlier calendar items, or null when there are none.
    previousFrom: string | null;
    timeTypes: TimeTypeOption[];
}>();

const timeTypes = Object.fromEntries(
    props.timeTypes.map((timeType) => [timeType.value, timeType]),
);

const loading = ref(false);

// Years, weeks and days added by the last load, so only they animate in.
const freshYears = ref(new Set<number>());
const freshWeeks = ref(new Set<string>());
const freshDays = ref(new Set<string>());

function shownKeys() {
    const weeks = props.years.flatMap((year) => year.weeks);

    return {
        years: new Set(props.years.map((year) => year.year)),
        weeks: new Set(weeks.map((week) => week.key)),
        days: new Set(
            weeks.flatMap((week) => week.days.map((day) => day.date)),
        ),
    };
}

function difference<T>(after: Set<T>, before: Set<T>): Set<T> {
    return new Set([...after].filter((key) => !before.has(key)));
}

// Load earlier calendar items without losing your place: the day that was
// at the top of the list stays where it was, then the page eases up to show
// the days added above it, stopping while that day is still in view.
const KEEP_IN_VIEW = 160;

function loadPrevious(): void {
    if (!props.previousFrom || loading.value) {
        return;
    }

    const anchor = document.querySelector<HTMLElement>('[data-day]');
    const anchorDate = anchor?.dataset.day;
    const anchorTop = anchor?.getBoundingClientRect().top ?? 0;
    const before = shownKeys();

    router.visit(index({ query: { from: props.previousFrom } }), {
        only: ['years', 'previousFrom'],
        preserveState: true,
        preserveScroll: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
        onSuccess: async () => {
            const after = shownKeys();
            freshYears.value = difference(after.years, before.years);
            freshWeeks.value = difference(after.weeks, before.weeks);
            freshDays.value = difference(after.days, before.days);

            await nextTick();

            const moved = anchorDate
                ? document.querySelector<HTMLElement>(
                      `[data-day="${anchorDate}"]`,
                  )
                : null;

            if (!moved) {
                return;
            }

            const added = moved.getBoundingClientRect().top - anchorTop;
            window.scrollBy({ top: added });

            const reduceMotion = window.matchMedia(
                '(prefers-reduced-motion: reduce)',
            ).matches;
            const roomBelow = window.innerHeight - KEEP_IN_VIEW - anchorTop;
            window.scrollBy({
                top: -Math.min(added, Math.max(roomBelow, 0)),
                behavior: reduceMotion ? 'auto' : 'smooth',
            });
        },
    });
}

const appear =
    'animate-in fade-in slide-in-from-top-2 duration-500 motion-reduce:animate-none';
</script>

<template>
    <Head title="Calendar items" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Calendar items"
                description="What you have planned, by year, week and day"
            />
            <Button v-if="years.length" as-child>
                <Link :href="create()">
                    <Plus />
                    New calendar item
                </Link>
            </Button>
        </div>

        <Button
            v-if="previousFrom"
            variant="outline"
            class="self-center"
            :disabled="loading"
            data-test="load-previous-button"
            @click="loadPrevious"
        >
            <Spinner v-if="loading" />
            <History v-else />
            {{ loading ? 'Loading…' : 'Load previous calendar items' }}
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
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    Create a calendar item
                </Link>
            </Button>
        </div>

        <section
            v-for="year in years"
            :key="year.year"
            :aria-labelledby="`year-${year.year}`"
            :class="['space-y-6', freshYears.has(year.year) && appear]"
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
                :class="[
                    'space-y-4 border-l pl-4',
                    freshWeeks.has(week.key) &&
                        !freshYears.has(year.year) &&
                        appear,
                ]"
            >
                <h3
                    :id="`week-${week.key}`"
                    class="text-muted-foreground text-sm font-medium"
                >
                    Week {{ week.number }} · {{ week.range }}
                </h3>

                <div
                    v-for="day in week.days"
                    :key="day.date"
                    :data-day="day.date"
                    :class="[
                        'space-y-2',
                        freshDays.has(day.date) &&
                            !freshWeeks.has(week.key) &&
                            appear,
                    ]"
                >
                    <h4 class="text-sm font-semibold">
                        <time :datetime="day.date">{{ day.label }}</time>
                    </h4>
                    <ul class="divide-y rounded-lg border">
                        <li
                            v-for="item in day.items"
                            :key="item.id"
                            class="relative flex flex-col gap-0.5 py-3 pr-4 pl-6 sm:flex-row sm:items-baseline sm:gap-4"
                            :data-time-type="item.timeType"
                        >
                            <!-- Any colour can be chosen, so the outline keeps the marker visible on either background. -->
                            <span
                                :class="[
                                    'absolute inset-y-2 left-2 w-1 rounded-full ring-1 ring-black/10 dark:ring-white/20',
                                    !item.category && 'bg-muted',
                                ]"
                                :style="
                                    item.category
                                        ? {
                                              backgroundColor:
                                                  item.category.colour,
                                          }
                                        : undefined
                                "
                                aria-hidden="true"
                                data-test="category-marker"
                            />
                            <span
                                class="text-muted-foreground shrink-0 text-sm tabular-nums sm:w-44"
                            >
                                <CalendarItemTime
                                    :time="item.time"
                                    :time-type="timeTypes[item.timeType]"
                                />
                            </span>
                            <span class="flex min-w-0 flex-col gap-0.5">
                                <span class="font-medium break-words">
                                    <span
                                        v-if="item.subCategory"
                                        class="mr-1.5"
                                        aria-hidden="true"
                                        >{{ item.subCategory.emoji }}</span
                                    >{{ item.title }}
                                </span>
                                <span
                                    v-if="item.category && item.subCategory"
                                    class="text-muted-foreground text-sm break-words"
                                >
                                    {{ item.category.name }} ·
                                    {{ item.subCategory.name }}
                                </span>
                                <!-- A long name is cut short; hovering shows it in full with the address. -->
                                <a
                                    v-if="item.location"
                                    :href="item.location.mapsUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    :title="
                                        [
                                            item.location.name,
                                            item.location.address,
                                        ]
                                            .filter(Boolean)
                                            .join(', ')
                                    "
                                    class="text-muted-foreground hover:text-foreground flex max-w-full items-center gap-1.5 self-start text-sm underline-offset-4 hover:underline"
                                    data-test="location"
                                >
                                    <MapPin
                                        class="size-3.5 shrink-0"
                                        aria-hidden="true"
                                    />
                                    <span class="truncate">{{
                                        item.location.name
                                    }}</span>
                                    <span class="sr-only"
                                        >(opens in maps in a new tab)</span
                                    >
                                    <ExternalLink
                                        class="size-3 shrink-0"
                                        aria-hidden="true"
                                    />
                                </a>
                            </span>
                        </li>
                    </ul>
                </div>
            </section>
        </section>
    </div>
</template>

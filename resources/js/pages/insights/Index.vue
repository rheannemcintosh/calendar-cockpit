<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import FirstCalendarItem from '@/components/InsightsFirstCalendarItem.vue';
import { Button } from '@/components/ui/button';
import categoriesRoutes from '@/routes/categories';
import { index } from '@/routes/insights';
import type { InsightsCategory, InsightsFirsts } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Insights',
                href: index(),
            },
        ],
    },
});

defineProps<{
    firsts: InsightsFirsts;
    // Oldest first, each with its sub-categories oldest first.
    categories: InsightsCategory[];
}>();
</script>

<template>
    <Head title="Insights" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4">
        <Heading
            title="Insights"
            description="How your calendar is set up and how it has grown"
        />

        <section aria-labelledby="firsts-heading" class="space-y-3">
            <h3 id="firsts-heading" class="font-medium">When it started</h3>

            <dl class="grid gap-3 sm:grid-cols-3">
                <div
                    class="space-y-1 rounded-lg border p-4"
                    data-test="first-category"
                >
                    <dt class="text-muted-foreground text-sm">
                        First category
                    </dt>
                    <template v-if="firsts.category">
                        <dd class="text-lg font-semibold tabular-nums">
                            {{ firsts.category.created }}
                        </dd>
                        <dd class="flex min-w-0 items-center gap-2 text-sm">
                            <span
                                class="size-3 shrink-0 rounded-full border border-black/10 dark:border-white/20"
                                :style="{
                                    backgroundColor: firsts.category.colour,
                                }"
                                aria-hidden="true"
                            />
                            <span class="truncate">
                                {{ firsts.category.name }}
                            </span>
                        </dd>
                    </template>
                    <dd v-else class="text-muted-foreground text-sm">
                        None yet
                    </dd>
                </div>

                <div
                    class="space-y-1 rounded-lg border p-4"
                    data-test="first-sub-category"
                >
                    <dt class="text-muted-foreground text-sm">
                        First sub-category
                    </dt>
                    <template v-if="firsts.subCategory">
                        <dd class="text-lg font-semibold tabular-nums">
                            {{ firsts.subCategory.created }}
                        </dd>
                        <dd class="flex min-w-0 items-center gap-1.5 text-sm">
                            <span class="shrink-0" aria-hidden="true">
                                {{ firsts.subCategory.emoji }}
                            </span>
                            <span class="truncate">
                                {{ firsts.subCategory.name }}
                                <span class="text-muted-foreground">
                                    in {{ firsts.subCategory.categoryName }}
                                </span>
                            </span>
                        </dd>
                    </template>
                    <dd v-else class="text-muted-foreground text-sm">
                        None yet
                    </dd>
                </div>

                <div
                    class="space-y-1 rounded-lg border p-4"
                    data-test="first-calendar-item"
                >
                    <dt class="text-muted-foreground text-sm">
                        First calendar item
                    </dt>
                    <template v-if="firsts.calendarItem">
                        <dd class="text-lg font-semibold tabular-nums">
                            {{ firsts.calendarItem.created }}
                        </dd>
                        <dd class="truncate text-sm">
                            {{ firsts.calendarItem.title }}
                        </dd>
                    </template>
                    <dd v-else class="text-muted-foreground text-sm">
                        None yet
                    </dd>
                </div>
            </dl>
        </section>

        <section aria-labelledby="created-heading" class="space-y-3">
            <div>
                <h3 id="created-heading" class="font-medium">
                    When each category and sub-category was created
                </h3>
                <p class="text-muted-foreground text-sm">
                    Oldest first, with the first calendar item created in each
                </p>
            </div>

            <div
                v-if="!categories.length"
                class="flex flex-col items-center gap-4 rounded-xl border border-dashed p-8 text-center"
                data-test="no-categories"
            >
                <p class="text-muted-foreground text-sm">
                    You have no categories yet.
                </p>
                <Button as-child>
                    <Link :href="categoriesRoutes.create()">
                        <Plus />
                        Create a category
                    </Link>
                </Button>
            </div>

            <ul v-else class="divide-y rounded-lg border">
                <li
                    v-for="category in categories"
                    :key="category.id"
                    data-test="created-category"
                >
                    <div class="flex items-start gap-3 px-4 py-2.5">
                        <span
                            class="mt-1.5 size-3 shrink-0 rounded-full border border-black/10 dark:border-white/20"
                            :style="{ backgroundColor: category.colour }"
                            aria-hidden="true"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">
                                {{ category.name }}
                            </p>
                            <FirstCalendarItem
                                :calendar-item="category.firstCalendarItem"
                            />
                        </div>
                        <span
                            class="text-muted-foreground shrink-0 pt-0.5 text-sm tabular-nums"
                        >
                            {{ category.created }}
                        </span>
                    </div>

                    <ul
                        v-if="category.subCategories.length"
                        class="bg-muted/30 divide-y border-t"
                        :aria-label="`Sub-categories in ${category.name}`"
                    >
                        <li
                            v-for="subCategory in category.subCategories"
                            :key="subCategory.id"
                            class="flex items-start gap-3 py-2 pr-4 pl-10 text-sm"
                            data-test="created-sub-category"
                        >
                            <span class="shrink-0" aria-hidden="true">
                                {{ subCategory.emoji }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate">
                                    {{ subCategory.name }}
                                </p>
                                <FirstCalendarItem
                                    :calendar-item="
                                        subCategory.firstCalendarItem
                                    "
                                />
                            </div>
                            <span
                                class="text-muted-foreground shrink-0 tabular-nums"
                            >
                                {{ subCategory.created }}
                            </span>
                        </li>
                    </ul>
                </li>
            </ul>
        </section>
    </div>
</template>

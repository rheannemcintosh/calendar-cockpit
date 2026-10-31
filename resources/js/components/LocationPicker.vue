<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { Check, LoaderCircle, MapPin, X } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxPortal,
    ComboboxRoot,
    ComboboxViewport,
} from 'reka-ui';
import { ref } from 'vue';
import { cn } from '@/lib/utils';
import locations from '@/routes/locations';
import type { LocationMatch } from '@/types';

// Search for a real place by name or address and choose one of the matches,
// or none. The chosen place's name, address, latitude and longitude are
// submitted through hidden inputs named location_name, location_address,
// location_latitude and location_longitude, which are empty when none is
// chosen.
defineProps<{
    id: string;
    invalid?: boolean;
}>();

// Searching starts once this many characters are typed, and waits until
// typing pauses, so every keystroke does not ask Mapbox.
const MIN_QUERY_LENGTH = 3;
const DEBOUNCE_MS = 300;

const chosen = ref<LocationMatch>();
const searchTerm = ref('');
const matches = ref<LocationMatch[]>([]);
const searching = ref(false);
const failed = ref(false);

const http = useHttp();

// Only the latest search may show its matches, so a slow earlier answer
// never replaces a newer one.
let latestSearch = 0;

watchDebounced(
    searchTerm,
    async (term) => {
        const query = term.trim();
        const search = ++latestSearch;

        failed.value = false;

        // Choosing a match fills the input with its name, which needs no search.
        if (query.length < MIN_QUERY_LENGTH || query === chosen.value?.name) {
            searching.value = false;

            if (query.length < MIN_QUERY_LENGTH) {
                matches.value = [];
            }

            return;
        }

        searching.value = true;

        try {
            const response = (await http.get(
                locations.search.url({ query: { q: query } }),
            )) as { matches: LocationMatch[] };

            if (search === latestSearch) {
                matches.value = response.matches;
            }
        } catch {
            if (search === latestSearch) {
                matches.value = [];
                failed.value = true;
            }
        } finally {
            if (search === latestSearch) {
                searching.value = false;
            }
        }
    },
    { debounce: DEBOUNCE_MS },
);

function display(location: LocationMatch | undefined): string {
    return location?.name ?? '';
}

function clear(): void {
    chosen.value = undefined;
    searchTerm.value = '';
    matches.value = [];
}
</script>

<template>
    <ComboboxRoot v-model="chosen" by="id" ignore-filter>
        <input type="hidden" name="location_name" :value="chosen?.name ?? ''" />
        <input
            type="hidden"
            name="location_address"
            :value="chosen?.address ?? ''"
        />
        <input
            type="hidden"
            name="location_latitude"
            :value="chosen?.latitude ?? ''"
        />
        <input
            type="hidden"
            name="location_longitude"
            :value="chosen?.longitude ?? ''"
        />

        <ComboboxAnchor
            :aria-invalid="invalid || undefined"
            :class="
                cn(
                    'border-input dark:bg-input/30 flex h-9 w-full min-w-0 items-center gap-2 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] md:text-sm',
                    'has-[:focus-visible]:border-ring has-[:focus-visible]:ring-ring/50 has-[:focus-visible]:ring-[3px]',
                    'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                )
            "
        >
            <MapPin
                class="text-muted-foreground size-4 shrink-0"
                aria-hidden="true"
            />
            <ComboboxInput
                :id="id"
                v-model="searchTerm"
                :display-value="display"
                placeholder="Search for a place or address"
                autocomplete="off"
                class="placeholder:text-muted-foreground min-w-0 flex-1 bg-transparent outline-none"
                :aria-invalid="invalid || undefined"
            />
            <LoaderCircle
                v-if="searching"
                class="text-muted-foreground size-4 shrink-0 animate-spin"
                aria-hidden="true"
            />
            <button
                v-else-if="chosen"
                type="button"
                class="text-muted-foreground hover:text-foreground shrink-0"
                aria-label="Remove location"
                data-test="location-picker-clear"
                @click="clear"
            >
                <X class="size-4" />
            </button>
        </ComboboxAnchor>

        <ComboboxPortal>
            <ComboboxContent
                position="popper"
                :side-offset="4"
                class="bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 z-50 w-(--reka-combobox-trigger-width) rounded-md border p-1 shadow-md"
            >
                <ComboboxViewport class="max-h-72">
                    <p
                        v-if="failed"
                        class="text-destructive px-2 py-1.5 text-sm"
                        role="alert"
                    >
                        Location search is not available right now. Try again
                        later.
                    </p>
                    <p
                        v-else-if="matches.length === 0"
                        class="text-muted-foreground px-2 py-1.5 text-sm"
                    >
                        <template v-if="searching">Searching…</template>
                        <template
                            v-else-if="
                                searchTerm.trim().length < MIN_QUERY_LENGTH
                            "
                        >
                            Type a place name or address.
                        </template>
                        <template v-else>No places match.</template>
                    </p>

                    <ComboboxItem
                        v-for="match in matches"
                        :key="match.id"
                        :value="match"
                        :text-value="match.name"
                        class="data-highlighted:bg-accent data-highlighted:text-accent-foreground relative flex w-full cursor-default flex-col items-start rounded-sm py-1.5 pr-8 pl-2 text-sm outline-none select-none"
                    >
                        <span class="w-full truncate">{{ match.name }}</span>
                        <span
                            v-if="match.address"
                            class="text-muted-foreground w-full truncate text-xs"
                        >
                            {{ match.address }}
                        </span>
                        <ComboboxItemIndicator
                            class="absolute top-2 right-2 flex size-3.5 items-center justify-center"
                        >
                            <Check class="size-4" />
                        </ComboboxItemIndicator>
                    </ComboboxItem>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxPortal>
    </ComboboxRoot>

    <p
        v-if="chosen?.address"
        class="text-muted-foreground text-sm"
        data-test="location-picker-address"
    >
        {{ chosen.address }}
    </p>
</template>

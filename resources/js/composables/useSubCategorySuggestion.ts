import { usePage } from '@inertiajs/vue3';
import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import { useNameSuggestion } from '@/composables/useNameSuggestion';
import calendarItems from '@/routes/calendar-items';

export type UseSubCategorySuggestionReturn = {
    loading: Ref<boolean>;
    // A sub-category recommended in place of the one already chosen, or null.
    hint: ComputedRef<number | null>;
    acceptHint: () => void;
    dismissHint: () => void;
};

// Choose the sub-category suggested for the calendar item's title once the
// user stops typing it, when no sub-category is chosen yet. A sub-category
// chosen this way follows the title as it changes, but one the user chose,
// or the page opened with, is never replaced: the suggestion is offered as a
// hint instead.
//
// The sub-category is the id of one of the user's sub-categories, or 0 when
// none is chosen, as used by the sub-category picker.
export function useSubCategorySuggestion(
    title: Ref<string>,
    subCategoryId: Ref<number>,
): UseSubCategorySuggestionReturn {
    // The sub-category most recently chosen for the user, if they haven't
    // changed it since.
    let chosenForUser: number | null = null;

    // The suggestion for the chosen sub-category, and the title it was for.
    const suggestion = ref<{ title: string; subCategoryId: number } | null>(
        null,
    );

    // Titles whose hint the user dismissed, so it isn't offered again.
    const dismissedTitles = new Set<string>();

    const hint = computed(() =>
        suggestion.value &&
        suggestion.value.subCategoryId !== subCategoryId.value
            ? suggestion.value.subCategoryId
            : null,
    );

    function canChoose(): boolean {
        return (
            subCategoryId.value === 0 || subCategoryId.value === chosenForUser
        );
    }

    function canHint(title: string): boolean {
        return !dismissedTitles.has(title.toLowerCase());
    }

    function acceptHint(): void {
        if (hint.value) {
            subCategoryId.value = hint.value;
        }

        suggestion.value = null;
    }

    function dismissHint(): void {
        if (suggestion.value) {
            dismissedTitles.add(suggestion.value.title.toLowerCase());
        }

        suggestion.value = null;
    }

    const { loading } = useNameSuggestion<{ sub_category_id: number | null }>(
        title,
        {
            enabled: usePage().props.subCategorySuggestionsEnabled,
            wanted: (query) => canChoose() || canHint(query),
            url: (query) =>
                calendarItems.subCategorySuggestion.url({
                    query: { title: query },
                }),
            apply: (response, query) => {
                if (!response.sub_category_id) {
                    return;
                }

                const suggested = response.sub_category_id;

                if (canChoose()) {
                    subCategoryId.value = suggested;
                    chosenForUser = suggested;
                } else if (canHint(query)) {
                    suggestion.value = {
                        title: query,
                        subCategoryId: suggested,
                    };
                }
            },
            onCancel: () => {
                suggestion.value = null;
            },
        },
    );

    return { loading, hint, acceptHint, dismissHint };
}

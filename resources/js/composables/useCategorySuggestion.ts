import { usePage } from '@inertiajs/vue3';
import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import { useNameSuggestion } from '@/composables/useNameSuggestion';
import subCategories from '@/routes/sub-categories';

export type UseCategorySuggestionReturn = {
    loading: Ref<boolean>;
    // A category recommended in place of the one already chosen, or null.
    hint: ComputedRef<string | null>;
    acceptHint: () => void;
    dismissHint: () => void;
};

// Choose the category suggested for the sub-category's name once the user
// stops typing it, when no category is chosen yet. A category chosen this
// way follows the name as it changes, but one the user chose, or the page
// opened with, is never replaced: the suggestion is offered as a hint
// instead.
//
// The category is the id of one of the user's categories as a string, as
// used by the category select, or '' when none is chosen.
export function useCategorySuggestion(
    name: Ref<string>,
    categoryId: Ref<string>,
): UseCategorySuggestionReturn {
    // The category most recently chosen for the user, if they haven't
    // changed it since.
    let chosenForUser: string | null = null;

    // The suggestion for the chosen category, and the name it was for.
    const suggestion = ref<{ name: string; categoryId: string } | null>(null);

    // Names whose hint the user dismissed, so it isn't offered again.
    const dismissedNames = new Set<string>();

    const hint = computed(() =>
        suggestion.value && suggestion.value.categoryId !== categoryId.value
            ? suggestion.value.categoryId
            : null,
    );

    function canChoose(): boolean {
        return categoryId.value === '' || categoryId.value === chosenForUser;
    }

    function canHint(name: string): boolean {
        return !dismissedNames.has(name.toLowerCase());
    }

    function acceptHint(): void {
        if (hint.value) {
            categoryId.value = hint.value;
        }

        suggestion.value = null;
    }

    function dismissHint(): void {
        if (suggestion.value) {
            dismissedNames.add(suggestion.value.name.toLowerCase());
        }

        suggestion.value = null;
    }

    const { loading } = useNameSuggestion<{ category_id: number | null }>(
        name,
        {
            enabled: usePage().props.categorySuggestionsEnabled,
            wanted: (query) => canChoose() || canHint(query),
            url: (query) =>
                subCategories.categorySuggestion.url({
                    query: { name: query },
                }),
            apply: (response, query) => {
                if (!response.category_id) {
                    return;
                }

                const suggested = response.category_id.toString();

                if (canChoose()) {
                    categoryId.value = suggested;
                    chosenForUser = suggested;
                } else if (canHint(query)) {
                    suggestion.value = { name: query, categoryId: suggested };
                }
            },
            onCancel: () => {
                suggestion.value = null;
            },
        },
    );

    return { loading, hint, acceptHint, dismissHint };
}

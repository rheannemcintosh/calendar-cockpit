import { usePage } from '@inertiajs/vue3';
import type { Ref } from 'vue';
import { useNameSuggestion } from '@/composables/useNameSuggestion';
import subCategories from '@/routes/sub-categories';

export type UseCategorySuggestionReturn = {
    loading: Ref<boolean>;
};

// Choose the category suggested for the sub-category's name once the user
// stops typing it, when no category is chosen yet. A category chosen this
// way follows the name as it changes, but one the user chose, or the page
// opened with, is never replaced.
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

    function canChoose(): boolean {
        return categoryId.value === '' || categoryId.value === chosenForUser;
    }

    const { loading } = useNameSuggestion<{ category_id: number | null }>(
        name,
        {
            enabled: usePage().props.categorySuggestionsEnabled,
            wanted: canChoose,
            url: (query) =>
                subCategories.categorySuggestion.url({
                    query: { name: query },
                }),
            apply: (response) => {
                if (response.category_id && canChoose()) {
                    categoryId.value = response.category_id.toString();
                    chosenForUser = categoryId.value;
                }
            },
        },
    );

    return { loading };
}

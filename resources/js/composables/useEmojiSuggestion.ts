import { usePage } from '@inertiajs/vue3';
import type { ComputedRef, MaybeRefOrGetter, Ref } from 'vue';
import { computed, ref, toValue, watch } from 'vue';
import { useNameSuggestion } from '@/composables/useNameSuggestion';
import subCategories from '@/routes/sub-categories';

export type UseEmojiSuggestionReturn = {
    loading: Ref<boolean>;
    // An emoji recommended in place of the one the user chose, or null.
    hint: ComputedRef<string | null>;
    acceptHint: () => void;
    dismissHint: () => void;
};

// Fill in an empty emoji with one suggested for the sub-category's name once
// the user stops typing it. An emoji filled in this way follows the name, and
// the category, as they change, but one the user picked or typed is never
// replaced: the suggestion is offered as a hint instead.
export function useEmojiSuggestion(
    name: Ref<string>,
    emoji: Ref<string>,
    categoryId: MaybeRefOrGetter<number | null>,
): UseEmojiSuggestionReturn {
    // The emoji most recently filled in for the user, if they haven't
    // changed it since.
    let filledIn: string | null = null;

    // The suggestion for the chosen emoji, and the name it was for.
    const suggestion = ref<{ name: string; emoji: string } | null>(null);

    // Names whose hint the user dismissed, so it isn't offered again.
    const dismissedNames = new Set<string>();

    const hint = computed(() =>
        suggestion.value && suggestion.value.emoji !== emoji.value.trim()
            ? suggestion.value.emoji
            : null,
    );

    function canFillIn(): boolean {
        return emoji.value.trim() === '' || emoji.value === filledIn;
    }

    function canHint(name: string): boolean {
        return !dismissedNames.has(name.toLowerCase());
    }

    function acceptHint(): void {
        if (hint.value) {
            emoji.value = hint.value;
        }

        suggestion.value = null;
    }

    function dismissHint(): void {
        if (suggestion.value) {
            dismissedNames.add(suggestion.value.name.toLowerCase());
        }

        suggestion.value = null;
    }

    const { loading, ask } = useNameSuggestion<{ emoji: string | null }>(name, {
        enabled: usePage().props.emojiSuggestionsEnabled,
        wanted: (query) => canFillIn() || canHint(query),
        url: (query) => {
            const category = toValue(categoryId);

            return subCategories.emojiSuggestion.url({
                query: {
                    name: query,
                    ...(category ? { category_id: category } : {}),
                },
            });
        },
        apply: (response, query) => {
            if (!response.emoji) {
                return;
            }

            if (canFillIn()) {
                emoji.value = response.emoji;
                filledIn = response.emoji;
            } else if (canHint(query)) {
                suggestion.value = { name: query, emoji: response.emoji };
            }
        },
        onCancel: () => {
            suggestion.value = null;
        },
    });

    // An emoji filled in for the user is asked for again when the category
    // changes, such as when a suggested category is chosen, so it fits both.
    watch(
        () => toValue(categoryId),
        () => {
            if (canFillIn()) {
                void ask();
            }
        },
    );

    return { loading, hint, acceptHint, dismissHint };
}

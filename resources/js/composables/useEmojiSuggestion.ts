import { useHttp, usePage } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import type { ComputedRef, MaybeRefOrGetter, Ref } from 'vue';
import { computed, onBeforeUnmount, ref, toValue, watch } from 'vue';
import subCategories from '@/routes/sub-categories';

// Suggestions are asked for once typing pauses, and only for names long
// enough to mean something.
const DEBOUNCE_MS = 600;
const MIN_NAME_LENGTH = 2;

export type UseEmojiSuggestionReturn = {
    loading: Ref<boolean>;
    // An emoji recommended in place of the one the user chose, or null.
    hint: ComputedRef<string | null>;
    acceptHint: () => void;
    dismissHint: () => void;
};

// Fill in an empty emoji with one suggested for the sub-category's name once
// the user stops typing it. An emoji filled in this way follows the name as
// it changes, but one the user picked or typed is never replaced: the
// suggestion is offered as a hint instead.
export function useEmojiSuggestion(
    name: Ref<string>,
    emoji: Ref<string>,
    categoryId: MaybeRefOrGetter<number | null>,
): UseEmojiSuggestionReturn {
    const loading = ref(false);
    const http = useHttp();
    const enabled = usePage().props.emojiSuggestionsEnabled;

    // The emoji most recently filled in for the user, if they haven't
    // changed it since.
    let filledIn: string | null = null;

    // Only the latest request may fill in its emoji, so a slow earlier
    // answer never replaces a newer one.
    let latestRequest = 0;

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

    function cancel(): void {
        latestRequest++;
        loading.value = false;
        suggestion.value = null;
        http.cancel();
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

    // Typing again means any suggestion still on its way is for an old name.
    watch(name, cancel);

    watchDebounced(
        name,
        async (value) => {
            const query = value.trim();

            if (
                !enabled ||
                query.length < MIN_NAME_LENGTH ||
                (!canFillIn() && !canHint(query))
            ) {
                return;
            }

            const request = ++latestRequest;
            const category = toValue(categoryId);

            loading.value = true;

            try {
                const response = (await http.get(
                    subCategories.emojiSuggestion.url({
                        query: {
                            name: query,
                            ...(category ? { category_id: category } : {}),
                        },
                    }),
                )) as { emoji: string | null };

                if (request !== latestRequest || !response.emoji) {
                    return;
                }

                if (canFillIn()) {
                    emoji.value = response.emoji;
                    filledIn = response.emoji;
                } else if (canHint(query)) {
                    suggestion.value = { name: query, emoji: response.emoji };
                }
            } catch {
                // With no suggestion the emoji is left as it is.
            } finally {
                if (request === latestRequest) {
                    loading.value = false;
                }
            }
        },
        { debounce: DEBOUNCE_MS },
    );

    onBeforeUnmount(cancel);

    return { loading, hint, acceptHint, dismissHint };
}

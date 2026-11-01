import { useHttp, usePage } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import type { MaybeRefOrGetter, Ref } from 'vue';
import { onBeforeUnmount, ref, toValue, watch } from 'vue';
import subCategories from '@/routes/sub-categories';

// Suggestions are asked for once typing pauses, and only for names long
// enough to mean something.
const DEBOUNCE_MS = 600;
const MIN_NAME_LENGTH = 2;

export type UseEmojiSuggestionReturn = {
    loading: Ref<boolean>;
};

// Fill in an empty emoji with one suggested for the sub-category's name once
// the user stops typing it. An emoji filled in this way follows the name as
// it changes, but one the user picked or typed is never replaced.
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

    function canFillIn(): boolean {
        return emoji.value.trim() === '' || emoji.value === filledIn;
    }

    function cancel(): void {
        latestRequest++;
        loading.value = false;
        http.cancel();
    }

    // Typing again means any suggestion still on its way is for an old name.
    watch(name, cancel);

    watchDebounced(
        name,
        async (value) => {
            const query = value.trim();

            if (!enabled || query.length < MIN_NAME_LENGTH || !canFillIn()) {
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

                if (
                    request === latestRequest &&
                    response.emoji &&
                    canFillIn()
                ) {
                    emoji.value = response.emoji;
                    filledIn = response.emoji;
                }
            } catch {
                // With no suggestion the emoji is left for the user to pick.
            } finally {
                if (request === latestRequest) {
                    loading.value = false;
                }
            }
        },
        { debounce: DEBOUNCE_MS },
    );

    onBeforeUnmount(cancel);

    return { loading };
}

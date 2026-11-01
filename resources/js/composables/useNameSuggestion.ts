import { useHttp } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import type { Ref } from 'vue';
import { onBeforeUnmount, ref, watch } from 'vue';

// Suggestions are asked for once typing pauses, and only for names long
// enough to mean something.
const DEBOUNCE_MS = 600;
const MIN_NAME_LENGTH = 2;

export type NameSuggestionOptions<TResponse> = {
    enabled: boolean;
    // Whether a suggestion is still wanted for the name.
    wanted: (name: string) => boolean;
    // Where to ask for a suggestion for the name.
    url: (name: string) => string;
    // Use the suggestion that came back for the name.
    apply: (response: TResponse, name: string) => void;
    // Called whenever a suggestion still on its way is given up on.
    onCancel?: () => void;
};

export type UseNameSuggestionReturn = {
    loading: Ref<boolean>;
    // Ask for a suggestion for the current name straight away.
    ask: () => Promise<void>;
};

// Ask for a suggestion for a sub-category's name once the user stops typing
// it. Typing again cancels a request still on its way, so a slow earlier
// answer never replaces a newer one.
export function useNameSuggestion<TResponse>(
    name: Ref<string>,
    options: NameSuggestionOptions<TResponse>,
): UseNameSuggestionReturn {
    const loading = ref(false);
    const http = useHttp();

    // Only the latest request may use its suggestion.
    let latestRequest = 0;

    function cancel(): void {
        latestRequest++;
        loading.value = false;
        http.cancel();
        options.onCancel?.();
    }

    async function ask(): Promise<void> {
        const query = name.value.trim();

        if (
            !options.enabled ||
            query.length < MIN_NAME_LENGTH ||
            !options.wanted(query)
        ) {
            return;
        }

        cancel();

        const request = latestRequest;

        loading.value = true;

        try {
            const response = (await http.get(options.url(query))) as TResponse;

            if (request === latestRequest) {
                options.apply(response, query);
            }
        } catch {
            // With no suggestion everything is left as it is.
        } finally {
            if (request === latestRequest) {
                loading.value = false;
            }
        }
    }

    // Typing again means any suggestion still on its way is for an old name.
    watch(name, cancel);

    watchDebounced(name, ask, { debounce: DEBOUNCE_MS });

    onBeforeUnmount(cancel);

    return { loading, ask };
}

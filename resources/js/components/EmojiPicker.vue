<script setup lang="ts">
import { LoaderCircle, SmilePlus } from '@lucide/vue';
import type { EmojiClickEvent } from 'emoji-picker-element/shared';
import dataSource from 'emoji-picker-element-data/en/emojibase/data.json?url';
import {
    PopoverContent,
    PopoverPortal,
    PopoverRoot,
    PopoverTrigger,
} from 'reka-ui';
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { useAppearance } from '@/composables/useAppearance';

// An emoji picked from a searchable emoji picker, or typed or pasted in.
// The emoji is submitted through the text input named `name`.
const props = defineProps<{
    id: string;
    name: string;
    invalid?: boolean;
    // Whether an emoji is being suggested, shown subtly on the button.
    loading?: boolean;
}>();

const emoji = defineModel<string>({ default: '' });

const open = ref(false);
const container = ref<HTMLElement | null>(null);
const { resolvedAppearance } = useAppearance();

let picker: HTMLElement | null = null;

// Match the picker to the app's light or dark appearance.
function applyAppearance(): void {
    const dark = resolvedAppearance.value === 'dark';

    picker?.classList.toggle('dark', dark);
    picker?.classList.toggle('light', !dark);
}

// The picker is a custom element that needs the browser, so it is loaded
// the first time it is opened, with its emoji data served by the app.
async function mountPicker(element: HTMLElement): Promise<void> {
    if (!picker) {
        const { Picker } = await import('emoji-picker-element');

        picker = new Picker({ dataSource });
        picker.style.setProperty('--background', 'var(--popover)');
        picker.style.setProperty('--border-size', '0');
        picker.addEventListener('emoji-click', (event) => {
            emoji.value = (event as EmojiClickEvent).detail.unicode ?? '';
            open.value = false;
        });
    }

    applyAppearance();
    element.append(picker);
    picker.shadowRoot?.querySelector<HTMLInputElement>('input')?.focus();
}

watch(resolvedAppearance, applyAppearance);

watch(container, (element) => {
    if (element) {
        void mountPicker(element);
    }
});
</script>

<template>
    <div class="flex items-center gap-2">
        <PopoverRoot v-model:open="open">
            <PopoverTrigger
                type="button"
                aria-label="Pick an emoji"
                :aria-busy="props.loading || undefined"
                :data-loading="props.loading || undefined"
                class="border-input dark:bg-input/30 hover:bg-accent focus-visible:border-ring focus-visible:ring-ring/50 flex h-9 w-12 shrink-0 items-center justify-center rounded-md border bg-transparent text-xl shadow-xs outline-none focus-visible:ring-[3px]"
            >
                <span
                    v-if="emoji.trim()"
                    :class="[
                        'max-w-full truncate px-1',
                        { 'animate-pulse': props.loading },
                    ]"
                    aria-hidden="true"
                >
                    {{ emoji.trim() }}
                </span>
                <LoaderCircle
                    v-else-if="props.loading"
                    class="text-muted-foreground size-4 animate-spin"
                    data-test="emoji-suggestion-loading"
                />
                <SmilePlus v-else class="text-muted-foreground size-4" />
            </PopoverTrigger>

            <PopoverPortal>
                <PopoverContent
                    :side-offset="4"
                    align="start"
                    class="bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 z-50 overflow-hidden rounded-md border shadow-md"
                    @open-auto-focus.prevent
                >
                    <div ref="container" data-test="emoji-picker" />
                </PopoverContent>
            </PopoverPortal>
        </PopoverRoot>

        <Input
            :id="props.id"
            v-model="emoji"
            :name="props.name"
            placeholder="Type or paste an emoji"
            autocomplete="off"
            :aria-invalid="props.invalid || undefined"
        />
    </div>
</template>

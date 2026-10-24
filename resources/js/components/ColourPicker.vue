<script setup lang="ts">
import { Check } from '@lucide/vue';
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

// A colour chosen from 18 suggested swatches, a colour picker or a typed
// hex code. The hex code is submitted through the text input named `name`.
const props = defineProps<{
    id: string;
    name: string;
    invalid?: boolean;
}>();

const colour = defineModel<string>({ default: '' });

const swatches = [
    { name: 'Red', hex: '#EF4444' },
    { name: 'Orange', hex: '#F97316' },
    { name: 'Yellow', hex: '#EAB308' },
    { name: 'Lime', hex: '#84CC16' },
    { name: 'Green', hex: '#22C55E' },
    { name: 'Teal', hex: '#14B8A6' },
    { name: 'Cyan', hex: '#06B6D4' },
    { name: 'Blue', hex: '#3B82F6' },
    { name: 'Indigo', hex: '#6366F1' },
    { name: 'Violet', hex: '#8B5CF6' },
    { name: 'Purple', hex: '#A855F7' },
    { name: 'Pink', hex: '#EC4899' },
    { name: 'Rose', hex: '#F43F5E' },
    { name: 'Maroon', hex: '#9F1239' },
    { name: 'Brown', hex: '#92400E' },
    { name: 'Navy', hex: '#1E3A8A' },
    { name: 'Grey', hex: '#6B7280' },
    { name: 'Black', hex: '#171717' },
];

// The typed hex code as #RRGGBB, or undefined while it is not a valid colour.
const normalised = computed(() =>
    /^#?[0-9A-Fa-f]{6}$/.test(colour.value.trim())
        ? `#${colour.value.trim().replace('#', '').toUpperCase()}`
        : undefined,
);

// Black or white, whichever stands out more against the given colour.
function checkColour(hex: string): string {
    const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16));

    return r * 0.299 + g * 0.587 + b * 0.114 > 160 ? '#000000' : '#FFFFFF';
}
</script>

<template>
    <div class="@container grid gap-3">
        <!--
            18, 9, 6 or 3 across, whichever fits the form. 18 waits for the
            sidebar to show, or the form would fit 18 just below that width
            and then drop back as the sidebar takes up room. The sidebar
            shows from 769px, one pixel past md.
        -->
        <div
            role="radiogroup"
            aria-label="Suggested colours"
            class="grid w-fit grid-cols-3 gap-2 @min-[14.5rem]:grid-cols-6 @min-[22rem]:grid-cols-9 min-[769px]:@min-[44.5rem]:grid-cols-18"
        >
            <button
                v-for="swatch in swatches"
                :key="swatch.hex"
                type="button"
                role="radio"
                :aria-checked="normalised === swatch.hex"
                :aria-label="swatch.name"
                :title="swatch.name"
                :class="
                    cn(
                        'ring-offset-background focus-visible:ring-ring flex size-8 items-center justify-center rounded-full border border-black/10 transition-shadow outline-none focus-visible:ring-2 focus-visible:ring-offset-2 dark:border-white/20',
                        normalised === swatch.hex &&
                            'ring-foreground ring-2 ring-offset-2',
                    )
                "
                :style="{ backgroundColor: swatch.hex }"
                @click="colour = swatch.hex"
            >
                <Check
                    v-if="normalised === swatch.hex"
                    class="size-4"
                    :style="{ color: checkColour(swatch.hex) }"
                    aria-hidden="true"
                />
            </button>
        </div>

        <div class="flex items-center gap-2">
            <input
                type="color"
                aria-label="Pick any colour"
                class="border-input h-9 w-12 shrink-0 cursor-pointer rounded-md border bg-transparent p-1"
                :value="normalised ?? '#000000'"
                @input="
                    colour = (
                        $event.target as HTMLInputElement
                    ).value.toUpperCase()
                "
            />
            <Input
                :id="props.id"
                v-model="colour"
                :name="props.name"
                maxlength="7"
                placeholder="#3B82F6"
                autocomplete="off"
                spellcheck="false"
                class="font-mono uppercase"
                :aria-invalid="props.invalid || undefined"
            />
        </div>
    </div>
</template>

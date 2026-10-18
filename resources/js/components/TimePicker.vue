<script setup lang="ts">
import { Time } from '@internationalized/date';
import { Clock } from '@lucide/vue';
import type { TimeValue } from 'reka-ui';
import {
    PopoverContent,
    PopoverPortal,
    PopoverRoot,
    PopoverTrigger,
    TimeFieldInput,
    TimeFieldRoot,
} from 'reka-ui';
import { nextTick, ref, watch } from 'vue';
import { cn } from '@/lib/utils';

// A 24-hour time field. Type any time to the minute, or pick one from a
// list in 15-minute steps. The value is submitted through a hidden input
// named `name`, as HH:mm:ss.
defineProps<{
    id: string;
    name: string;
    invalid?: boolean;
}>();

const value = ref<TimeValue>();
const open = ref(false);
const list = ref<HTMLElement>();

const times = Array.from(
    { length: 24 * 4 },
    (_, index) => new Time(Math.floor(index / 4), (index % 4) * 15),
);

function format(time: TimeValue): string {
    return `${String(time.hour).padStart(2, '0')}:${String(time.minute).padStart(2, '0')}`;
}

function isSelected(time: Time): boolean {
    return (
        value.value?.hour === time.hour && value.value?.minute === time.minute
    );
}

function select(time: Time): void {
    value.value = time;
    open.value = false;
}

// Open the list at the chosen time, or at 09:00 when none is chosen yet.
watch(open, async (isOpen) => {
    if (!isOpen) {
        return;
    }

    await nextTick();

    const target = value.value
        ? new Time(
              value.value.hour,
              value.value.minute - (value.value.minute % 15),
          )
        : new Time(9, 0);

    list.value
        ?.querySelector(`[data-time="${format(target)}"]`)
        ?.scrollIntoView({ block: 'center' });
});
</script>

<template>
    <PopoverRoot v-model:open="open">
        <TimeFieldRoot
            :id="id"
            v-slot="{ segments }"
            v-model="value"
            :name="name"
            granularity="minute"
            :hour-cycle="24"
            locale="en-GB"
            :aria-invalid="invalid || undefined"
            :class="
                cn(
                    'border-input dark:bg-input/30 flex h-9 w-full min-w-0 items-center rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] md:text-sm',
                    'has-[:focus-visible]:border-ring has-[:focus-visible]:ring-ring/50 has-[:focus-visible]:ring-[3px]',
                    'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                )
            "
        >
            <template v-for="item in segments" :key="item.part">
                <TimeFieldInput
                    v-if="item.part === 'literal'"
                    :part="item.part"
                    class="text-muted-foreground"
                >
                    {{ item.value }}
                </TimeFieldInput>
                <TimeFieldInput
                    v-else
                    :part="item.part"
                    class="focus:bg-accent focus:text-accent-foreground data-placeholder:text-muted-foreground rounded-sm px-0.5 tabular-nums outline-none"
                >
                    {{ item.value }}
                </TimeFieldInput>
            </template>

            <PopoverTrigger
                class="text-muted-foreground hover:text-foreground focus-visible:ring-ring/50 ml-auto rounded-sm outline-none focus-visible:ring-[3px]"
                aria-label="Choose a time"
            >
                <Clock class="size-4" />
            </PopoverTrigger>
        </TimeFieldRoot>

        <PopoverPortal>
            <PopoverContent
                :side-offset="4"
                align="end"
                class="bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 z-50 w-28 rounded-md border p-1 shadow-md"
            >
                <div ref="list" class="max-h-60 overflow-y-auto" role="listbox">
                    <button
                        v-for="time in times"
                        :key="format(time)"
                        type="button"
                        role="option"
                        :data-time="format(time)"
                        :aria-selected="isSelected(time)"
                        :data-selected="isSelected(time) || undefined"
                        class="hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent data-selected:bg-primary data-selected:text-primary-foreground w-full rounded-sm px-2 py-1.5 text-left text-sm tabular-nums outline-none"
                        @click="select(time)"
                    >
                        {{ format(time) }}
                    </button>
                </div>
            </PopoverContent>
        </PopoverPortal>
    </PopoverRoot>
</template>

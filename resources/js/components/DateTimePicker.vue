<script setup lang="ts">
import { CalendarDays, ChevronLeft, ChevronRight } from '@lucide/vue';
import {
    DatePickerCalendar,
    DatePickerCell,
    DatePickerCellTrigger,
    DatePickerContent,
    DatePickerField,
    DatePickerGrid,
    DatePickerGridBody,
    DatePickerGridHead,
    DatePickerGridRow,
    DatePickerHeadCell,
    DatePickerHeader,
    DatePickerHeading,
    DatePickerInput,
    DatePickerNext,
    DatePickerPrev,
    DatePickerRoot,
    DatePickerTrigger,
} from 'reka-ui';
import { cn } from '@/lib/utils';

// A date and time field, to the minute, with a calendar whose weeks run
// Sunday to Saturday to match the events list. The value is submitted
// through a hidden input named `name`, as YYYY-MM-DDTHH:mm.
defineProps<{
    id: string;
    name: string;
    invalid?: boolean;
}>();
</script>

<template>
    <DatePickerRoot
        :id="id"
        :name="name"
        granularity="minute"
        :hour-cycle="24"
        :week-starts-on="0"
        locale="en-GB"
        hide-time-zone
    >
        <DatePickerField
            v-slot="{ segments }"
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
                <DatePickerInput
                    v-if="item.part === 'literal'"
                    :part="item.part"
                    class="text-muted-foreground"
                >
                    {{ item.value }}
                </DatePickerInput>
                <DatePickerInput
                    v-else
                    :part="item.part"
                    class="focus:bg-accent focus:text-accent-foreground data-placeholder:text-muted-foreground rounded-sm px-0.5 tabular-nums outline-none"
                >
                    {{ item.value }}
                </DatePickerInput>
            </template>

            <DatePickerTrigger
                class="text-muted-foreground hover:text-foreground focus-visible:ring-ring/50 ml-auto rounded-sm outline-none focus-visible:ring-[3px]"
                aria-label="Open calendar"
            >
                <CalendarDays class="size-4" />
            </DatePickerTrigger>
        </DatePickerField>

        <DatePickerContent
            :side-offset="4"
            align="start"
            class="bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 z-50 rounded-md border p-3 shadow-md"
        >
            <DatePickerCalendar v-slot="{ weekDays, grid }">
                <DatePickerHeader
                    class="flex items-center justify-between pb-2"
                >
                    <DatePickerPrev
                        class="hover:bg-accent inline-flex size-7 items-center justify-center rounded-md"
                    >
                        <ChevronLeft class="size-4" />
                    </DatePickerPrev>
                    <DatePickerHeading class="text-sm font-medium" />
                    <DatePickerNext
                        class="hover:bg-accent inline-flex size-7 items-center justify-center rounded-md"
                    >
                        <ChevronRight class="size-4" />
                    </DatePickerNext>
                </DatePickerHeader>

                <DatePickerGrid
                    v-for="month in grid"
                    :key="month.value.toString()"
                    class="w-full border-collapse select-none"
                >
                    <DatePickerGridHead>
                        <DatePickerGridRow class="flex">
                            <DatePickerHeadCell
                                v-for="day in weekDays"
                                :key="day"
                                class="text-muted-foreground w-8 text-xs font-normal"
                            >
                                {{ day }}
                            </DatePickerHeadCell>
                        </DatePickerGridRow>
                    </DatePickerGridHead>
                    <DatePickerGridBody>
                        <DatePickerGridRow
                            v-for="(weekDates, index) in month.rows"
                            :key="`week-${index}`"
                            class="mt-1 flex"
                        >
                            <DatePickerCell
                                v-for="weekDate in weekDates"
                                :key="weekDate.toString()"
                                :date="weekDate"
                            >
                                <DatePickerCellTrigger
                                    :day="weekDate"
                                    :month="month.value"
                                    class="hover:bg-accent data-selected:bg-primary data-selected:text-primary-foreground data-outside-view:text-muted-foreground/50 focus-visible:ring-ring/50 inline-flex size-8 items-center justify-center rounded-md text-sm outline-none focus-visible:ring-[3px] data-today:font-semibold"
                                />
                            </DatePickerCell>
                        </DatePickerGridRow>
                    </DatePickerGridBody>
                </DatePickerGrid>
            </DatePickerCalendar>
        </DatePickerContent>
    </DatePickerRoot>
</template>

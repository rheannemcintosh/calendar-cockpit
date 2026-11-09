import { usePage } from '@inertiajs/vue3';
import { useIntervalFn } from '@vueuse/core';
import type { ComputedRef } from 'vue';
import { computed, ref } from 'vue';
import { wallClock } from '@/lib/calendarTimeline';

// Today's date, as YYYY-MM-DD, and the minute of the day it is now, in the
// user's timezone rather than this device's, kept up to date each minute.
export function useUserNow(): {
    today: ComputedRef<string>;
    nowMinute: ComputedRef<number>;
} {
    const page = usePage();
    const now = ref(new Date());
    useIntervalFn(() => (now.value = new Date()), 60_000);

    const clock = computed(() => wallClock(now.value, page.props.timezone));

    return {
        today: computed(() => clock.value.date),
        nowMinute: computed(() => clock.value.minute),
    };
}

import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { edit, history } from '@/routes/calendar-items';
import type { CalendarView } from '@/types';

const VIEWS: Record<string, CalendarView> = {
    'calendar-items/Index': 'list',
    'calendar-items/Day': 'day',
    'calendar-items/Week': 'week',
    'calendar-items/Month': 'month',
};

// Open a calendar item's edit or history page, remembering the calendar view it was
// opened from so that saving or cancelling returns to it.
export function useEditCalendarItem() {
    const page = usePage();
    const view = computed<CalendarView>(() => VIEWS[page.component] ?? 'list');

    function editUrl(id: number) {
        return edit(id, { query: { view: view.value } });
    }

    function historyUrl(id: number) {
        return history(id, { query: { view: view.value } });
    }

    function openEdit(id: number): void {
        router.visit(editUrl(id));
    }

    return { editUrl, historyUrl, openEdit };
}

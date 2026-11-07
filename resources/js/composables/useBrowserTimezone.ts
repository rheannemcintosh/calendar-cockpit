import { router, usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { storeTimezone } from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { browserTimezone } from '@/lib/timezone';

// Set the user's timezone from this browser when they have none yet, such
// as when they registered before timezones were kept, and show the page
// again so today is worked out in it.
export function useBrowserTimezone(): void {
    const page = usePage();

    onMounted(() => {
        if (!page.props.auth.user || page.props.auth.user.timezone) {
            return;
        }

        router.post(
            storeTimezone.url(),
            { timezone: browserTimezone() },
            { preserveScroll: true, preserveState: true },
        );
    });
}

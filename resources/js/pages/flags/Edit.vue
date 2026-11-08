<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { ref } from 'vue';
import FlagController from '@/actions/App/Http/Controllers/FlagController';
import FlagFormFields from '@/components/FlagFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/flags';
import type { FlagIconKey, FlagIconOption, FlagOption } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Flags',
                href: index(),
            },
            {
                // The last breadcrumb is not a link, so it needs no URL with
                // the flag in it.
                title: 'Edit flag',
                href: index(),
            },
        ],
    },
});

const props = defineProps<{
    // The flag being changed, as it is now.
    flag: FlagOption;
    // Every icon a flag can show, in order.
    icons: FlagIconOption[];
}>();

const name = ref(props.flag.name);
const icon = ref<FlagIconKey | ''>(props.flag.icon);
</script>

<template>
    <Head title="Edit flag" />

    <div class="mx-auto w-full max-w-3xl p-4">
        <Link
            :href="index()"
            class="text-muted-foreground hover:text-foreground mb-4 inline-flex items-center gap-1.5 text-sm"
            data-test="back-to-flags-link"
        >
            <ArrowLeft class="size-4" />
            Back to flags
        </Link>

        <Heading
            title="Edit flag"
            description="Change its name or icon. Calendar items with it show the change too"
        />

        <Form
            v-bind="FlagController.update.form(props.flag.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <FlagFormFields
                v-model:name="name"
                v-model:icon="icon"
                :icons="icons"
                :errors="errors"
            />

            <Button :disabled="processing" data-test="update-flag-button">
                Save changes
            </Button>
        </Form>
    </div>
</template>

<script setup lang="ts">
import { index as overviewRoute } from '@/actions/App/Http/Controllers/Admin/AdministrationController';
import { index as pilotsRoute } from '@/actions/App/Http/Controllers/Admin/PilotController';
import { edit as settingsRoute } from '@/actions/App/Http/Controllers/Admin/SettingsController';
import { index as requestsRoute } from '@/actions/App/Http/Controllers/Admin/StandingRequestController';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
    description?: string;
}>();

const tabs = [
    { title: 'Overview', href: overviewRoute() },
    { title: 'Standing requests', href: requestsRoute() },
    { title: 'Pilots', href: pilotsRoute() },
    { title: 'Settings', href: settingsRoute() },
];

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="flex flex-1 flex-col">
        <div class="border-b px-4 pt-6 sm:px-6">
            <p
                class="text-xs font-medium tracking-[0.2em] text-muted-foreground uppercase"
            >
                Administration
            </p>
            <div class="mt-1.5 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="mt-1 text-sm text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>
                <div class="pb-1">
                    <slot name="actions" />
                </div>
            </div>
            <nav
                class="mt-4 -mb-px flex gap-1 overflow-x-auto"
                aria-label="Administration sections"
            >
                <Link
                    v-for="tab in tabs"
                    :key="toUrl(tab.href)"
                    :href="tab.href"
                    class="border-b-2 px-3 py-2 text-sm whitespace-nowrap transition-colors"
                    :class="
                        isCurrentUrl(tab.href)
                            ? 'border-primary font-medium text-foreground'
                            : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground'
                    "
                >
                    {{ tab.title }}
                </Link>
            </nav>
        </div>
        <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6">
            <slot />
        </div>
    </div>
</template>

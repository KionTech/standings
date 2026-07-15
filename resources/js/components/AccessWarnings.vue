<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { KeyRound, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();
const auth = computed(() => page.props.auth);

const sourceUnreadable = computed(
    () =>
        auth.value.user && auth.value.is_admin && auth.value.source_unreadable,
);

const reauthCharacters = computed(() =>
    auth.value.user ? (auth.value.reauth_characters ?? []) : [],
);

const reauthNames = computed(() =>
    reauthCharacters.value.map((character) => character.name).join(', '),
);
</script>

<template>
    <div
        v-if="sourceUnreadable || reauthCharacters.length > 0"
        class="space-y-3 p-4 pb-0"
    >
        <div
            v-if="sourceUnreadable"
            class="flex flex-col gap-3 rounded-lg border border-destructive/40 bg-destructive/10 p-4 text-destructive sm:flex-row sm:items-center"
        >
            <TriangleAlert class="h-5 w-5 shrink-0" />
            <div class="flex-1">
                <p class="font-medium">The standings source can't be read.</p>
                <p class="text-sm opacity-90">
                    No admin character has a valid token for it &mdash; syncing
                    is paused until it's re-authenticated.
                </p>
            </div>
            <a
                :href="auth.admin_scopes_url ?? undefined"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground transition hover:bg-destructive/90"
            >
                <KeyRound class="h-4 w-4" />
                Re-authenticate
            </a>
        </div>

        <div
            v-if="reauthCharacters.length > 0"
            class="flex flex-col gap-3 rounded-lg border border-amber-500/40 bg-amber-500/10 p-4 text-amber-700 sm:flex-row sm:items-center dark:text-amber-400"
        >
            <TriangleAlert class="h-5 w-5 shrink-0" />
            <div class="flex-1">
                <p class="font-medium">
                    {{ reauthCharacters.length }} character(s) need
                    re-authentication.
                </p>
                <p class="text-sm opacity-90">
                    <span class="font-medium">{{ reauthNames }}</span> can't
                    sync standings until you grant contact permissions.
                </p>
            </div>
            <a
                :href="auth.sync_scopes_url"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-md border border-amber-500/50 px-4 py-2 text-sm font-medium transition hover:bg-amber-500/10"
            >
                <KeyRound class="h-4 w-4" />
                Grant access
            </a>
        </div>
    </div>
</template>

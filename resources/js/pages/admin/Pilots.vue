<script setup lang="ts">
import { index as overviewRoute } from '@/actions/App/Http/Controllers/Admin/AdministrationController';
import { index as pilotsRoute } from '@/actions/App/Http/Controllers/Admin/PilotController';
import Pagination from '@/components/Pagination.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/composables/useInitials';
import { eveImage } from '@/lib/eve';
import AdminLayout from '@/layouts/admin/Layout.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Paginated } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { Search, Star } from '@lucide/vue';
import { computed, ref } from 'vue';

type EntitySummary = {
    id: number;
    name: string | null;
    ticker: string | null;
};

type PilotCharacter = {
    id: number;
    name: string | null;
    is_main: boolean;
    corporation: EntitySummary | null;
    alliance: EntitySummary | null;
};

type PilotAccount = {
    id: number;
    name: string;
    characters: PilotCharacter[];
};

type AffiliatedAccount = {
    id: number;
    name: string | null;
    avatar_character_id: number;
    via: string[];
};

type AffiliationGroup = {
    id: number;
    name: string | null;
    ticker: string | null;
    accounts: AffiliatedAccount[];
};

const props = defineProps<{
    users: Paginated<PilotAccount> | null;
    groups: Paginated<AffiliationGroup> | null;
    filters: { search: string; view: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: overviewRoute() },
    { title: 'Pilots', href: pilotsRoute() },
];

const { getInitials } = useInitials();

const VIEWS = [
    { value: 'accounts', label: 'Accounts' },
    { value: 'corporations', label: 'Corporations' },
    { value: 'alliances', label: 'Alliances' },
] as const;

type View = (typeof VIEWS)[number]['value'];

const currentView = computed<View>(() =>
    VIEWS.some((view) => view.value === props.filters.view)
        ? (props.filters.view as View)
        : 'accounts',
);

const search = ref(props.filters.search);

function visitWith(view: View, searchTerm: string): void {
    router.get(
        pilotsRoute.url(),
        {
            ...(view !== 'accounts' ? { view } : {}),
            ...(searchTerm ? { search: searchTerm } : {}),
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'groups', 'filters'],
        },
    );
}

function setView(view: View): void {
    visitWith(view, search.value);
}

watchDebounced(search, (value) => visitWith(currentView.value, value), {
    debounce: 300,
});

const description = computed(() => {
    if (currentView.value === 'corporations') {
        return 'Corporations that registered characters belong to, and the accounts affiliated through them.';
    }
    if (currentView.value === 'alliances') {
        return 'Alliances that registered characters belong to, and the accounts affiliated through them.';
    }
    return 'Every registered account with its main character and alts.';
});

const groupEntityType = computed(() =>
    currentView.value === 'alliances' ? 'alliance' : 'corporation',
);

function mainCharacterOf(user: PilotAccount): PilotCharacter | undefined {
    return user.characters.find((character) => character.is_main);
}
</script>

<template>
    <Head title="Pilots" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <AdminLayout title="Pilots" :description="description">
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <div
                        class="flex items-center gap-1 rounded-lg border bg-card p-1"
                        role="group"
                        aria-label="Group pilots by"
                    >
                        <Button
                            v-for="view in VIEWS"
                            :key="view.value"
                            type="button"
                            size="sm"
                            :variant="
                                currentView === view.value
                                    ? 'secondary'
                                    : 'ghost'
                            "
                            class="h-7 px-2.5 text-xs"
                            @click="setView(view.value)"
                        >
                            {{ view.label }}
                        </Button>
                    </div>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-2.5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            type="search"
                            placeholder="Search pilots and characters"
                            class="w-64 pl-8"
                            aria-label="Search pilots and characters"
                        />
                    </div>
                </div>
            </template>

            <!-- Accounts view -->
            <template v-if="currentView === 'accounts' && users">
                <p
                    v-if="users.data.length === 0"
                    class="py-16 text-center text-sm text-muted-foreground"
                >
                    <template v-if="filters.search">
                        No pilots match "{{ filters.search }}". Try a different
                        name.
                    </template>
                    <template v-else>
                        No pilots registered yet. Accounts appear here after
                        their first login.
                    </template>
                </p>
                <ul v-else class="grid gap-4 lg:grid-cols-2">
                    <li
                        v-for="user in users.data"
                        :key="user.id"
                        class="self-start rounded-xl border bg-card"
                    >
                        <div
                            class="flex items-center justify-between gap-4 border-b bg-muted/40 px-3 py-2"
                        >
                            <p
                                class="flex items-center gap-2 text-sm font-medium"
                            >
                                <Star
                                    class="h-3.5 w-3.5 text-muted-foreground"
                                />
                                <template v-if="mainCharacterOf(user)">
                                    {{ mainCharacterOf(user)!.name }}
                                </template>
                                <template v-else>
                                    {{ user.name }}
                                    <span
                                        class="text-xs font-normal text-muted-foreground"
                                    >
                                        no main selected
                                    </span>
                                </template>
                            </p>
                            <span class="text-xs text-muted-foreground">
                                {{ user.characters.length }}
                                {{
                                    user.characters.length === 1
                                        ? 'character'
                                        : 'characters'
                                }}
                            </span>
                        </div>
                        <ul class="p-2">
                            <li
                                v-for="character in user.characters"
                                :key="character.id"
                                class="flex items-center gap-4 rounded-md px-2 py-2 transition-colors odd:bg-muted/40 hover:bg-muted"
                            >
                                <Avatar class="h-8 w-8 overflow-hidden rounded">
                                    <AvatarImage
                                        :src="
                                            eveImage('character', character.id)!
                                        "
                                        :alt="character.name ?? ''"
                                    />
                                    <AvatarFallback class="rounded text-xs">
                                        {{ getInitials(character.name ?? '?') }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex-1">
                                    <p
                                        class="flex items-center gap-2 text-sm font-medium"
                                    >
                                        {{ character.name ?? character.id }}
                                        <Badge
                                            v-if="character.is_main"
                                            variant="secondary"
                                        >
                                            Main
                                        </Badge>
                                    </p>
                                    <div
                                        class="mt-0.5 flex items-center gap-2 text-xs text-muted-foreground"
                                    >
                                        <span v-if="character.corporation">
                                            {{ character.corporation.name }}
                                        </span>
                                        <span v-if="character.alliance">
                                            {{ character.alliance.name }}
                                        </span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </li>
                </ul>
                <Pagination v-if="users.last_page > 1" :links="users.links" />
            </template>

            <!-- Corporation / alliance views -->
            <template v-else-if="groups">
                <p
                    v-if="groups.data.length === 0"
                    class="py-16 text-center text-sm text-muted-foreground"
                >
                    <template v-if="filters.search">
                        No {{ currentView }} match "{{ filters.search }}". Try a
                        different name.
                    </template>
                    <template v-else>
                        No {{ currentView }} with registered characters yet.
                    </template>
                </p>
                <ul v-else class="grid gap-4 lg:grid-cols-2">
                    <li
                        v-for="group in groups.data"
                        :key="group.id"
                        class="self-start rounded-xl border bg-card"
                    >
                        <div
                            class="flex items-center justify-between gap-4 border-b bg-muted/40 px-3 py-2"
                        >
                            <p
                                class="flex min-w-0 items-center gap-2 text-sm font-medium"
                            >
                                <Avatar
                                    class="h-6 w-6 shrink-0 overflow-hidden rounded"
                                >
                                    <AvatarImage
                                        :src="
                                            eveImage(groupEntityType, group.id)!
                                        "
                                        :alt="group.name ?? ''"
                                    />
                                    <AvatarFallback
                                        class="rounded text-[10px] uppercase"
                                    >
                                        {{ groupEntityType.slice(0, 2) }}
                                    </AvatarFallback>
                                </Avatar>
                                <span class="truncate">
                                    {{ group.name ?? group.id }}
                                </span>
                                <span
                                    v-if="group.ticker"
                                    class="shrink-0 text-xs font-normal text-muted-foreground"
                                >
                                    [{{ group.ticker }}]
                                </span>
                            </p>
                            <span
                                class="shrink-0 text-xs text-muted-foreground"
                            >
                                {{ group.accounts.length }}
                                {{
                                    group.accounts.length === 1
                                        ? 'account'
                                        : 'accounts'
                                }}
                            </span>
                        </div>
                        <ul class="p-2">
                            <li
                                v-for="account in group.accounts"
                                :key="account.id"
                                class="flex items-center gap-4 rounded-md px-2 py-2 transition-colors odd:bg-muted/40 hover:bg-muted"
                            >
                                <Avatar class="h-8 w-8 overflow-hidden rounded">
                                    <AvatarImage
                                        :src="
                                            eveImage(
                                                'character',
                                                account.avatar_character_id,
                                            )!
                                        "
                                        :alt="account.name ?? ''"
                                    />
                                    <AvatarFallback class="rounded text-xs">
                                        {{ getInitials(account.name ?? '?') }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">
                                        {{ account.name ?? account.id }}
                                    </p>
                                    <p
                                        class="mt-0.5 truncate text-xs text-muted-foreground"
                                    >
                                        via {{ account.via.join(', ') }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </li>
                </ul>
                <Pagination v-if="groups.last_page > 1" :links="groups.links" />
            </template>
        </AdminLayout>
    </AppLayout>
</template>

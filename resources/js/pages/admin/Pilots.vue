<script setup lang="ts">
import { index as overviewRoute } from '@/actions/App/Http/Controllers/Admin/AdministrationController';
import { index as pilotsRoute } from '@/actions/App/Http/Controllers/Admin/PilotController';
import Pagination from '@/components/Pagination.vue';
import PilotCharacterRow from '@/components/PilotCharacterRow.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { effectiveStandingChipClass, eveImage, standingLabel } from '@/lib/eve';
import AdminLayout from '@/layouts/admin/Layout.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Paginated } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import {
    ArrowDown,
    ArrowUp,
    ChevronDown,
    ChevronsUpDown,
    Search,
} from '@lucide/vue';
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
    account: { id: number | null; name: string | null };
    standing: { value: number; source: string } | null;
    corporation: EntitySummary | null;
    alliance: EntitySummary | null;
};

type AffiliationGroup = {
    id: number;
    name: string | null;
    ticker: string | null;
    standing: { value: number; source: string } | null;
    characters: PilotCharacter[];
};

const props = defineProps<{
    characters: PilotCharacter[] | null;
    groups: Paginated<AffiliationGroup> | null;
    filters: { search: string; view: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: overviewRoute() },
    { title: 'Pilots', href: pilotsRoute() },
];

const VIEWS = [
    { value: 'characters', label: 'Characters' },
    { value: 'corporations', label: 'Corporations' },
    { value: 'alliances', label: 'Alliances' },
] as const;

type View = (typeof VIEWS)[number]['value'];

const currentView = computed<View>(() =>
    VIEWS.some((view) => view.value === props.filters.view)
        ? (props.filters.view as View)
        : 'characters',
);

const search = ref(props.filters.search);

function visitWith(view: View, searchTerm: string): void {
    router.get(
        pilotsRoute.url(),
        {
            ...(view !== 'characters' ? { view } : {}),
            ...(searchTerm ? { search: searchTerm } : {}),
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['characters', 'groups', 'filters'],
        },
    );
}

function setView(view: View): void {
    visitWith(view, search.value);
}

// The characters view filters client-side; only the paginated group views
// need the server to search.
watchDebounced(
    search,
    (value) => {
        if (currentView.value !== 'characters') {
            visitWith(currentView.value, value);
        }
    },
    { debounce: 300 },
);

const description = computed(() => {
    if (currentView.value === 'corporations') {
        return 'Corporations that registered characters belong to, their standing and why it applies.';
    }
    if (currentView.value === 'alliances') {
        return 'Alliances that registered characters belong to, their standing and why it applies.';
    }
    return 'Every registered character, the account it belongs to, and its effective standing.';
});

const groupEntityType = computed(() =>
    currentView.value === 'alliances' ? 'alliance' : 'corporation',
);

// --- Characters view: client-side search and sort ---

type SortKey = 'name' | 'account' | 'corporation' | 'alliance' | 'standing';

const sortKey = ref<SortKey>('name');
const sortAsc = ref(true);

function sortBy(key: SortKey): void {
    if (sortKey.value === key) {
        sortAsc.value = !sortAsc.value;
    } else {
        sortKey.value = key;
        // Standings read best blue-first.
        sortAsc.value = key !== 'standing';
    }
}

function sortText(character: PilotCharacter, key: SortKey): string {
    if (key === 'account') {
        return character.account.name ?? '';
    }
    if (key === 'corporation' || key === 'alliance') {
        return character[key]?.name ?? '';
    }
    return character.name ?? '';
}

const visibleCharacters = computed(() => {
    const term = search.value.trim().toLowerCase();

    const rows = (props.characters ?? []).filter(
        (character) =>
            term === '' ||
            [
                character.name,
                character.account.name,
                character.corporation?.name,
                character.alliance?.name,
            ].some((value) => value?.toLowerCase().includes(term)),
    );

    const direction = sortAsc.value ? 1 : -1;

    return rows.toSorted((a, b) => {
        if (sortKey.value === 'standing') {
            // Characters without a standing sort last either way.
            if (a.standing === null || b.standing === null) {
                return (
                    Number(a.standing === null) - Number(b.standing === null)
                );
            }

            return direction * (a.standing.value - b.standing.value);
        }

        return (
            direction *
            sortText(a, sortKey.value).localeCompare(sortText(b, sortKey.value))
        );
    });
});

const SORTABLE_COLUMNS: { key: SortKey; label: string }[] = [
    { key: 'name', label: 'Character' },
    { key: 'account', label: 'Account' },
    { key: 'corporation', label: 'Corporation' },
    { key: 'alliance', label: 'Alliance' },
];

// --- Group views: collapsible sections, expanded by default ---

const collapsedGroups = ref(new Set<number>());

function toggleGroup(id: number): void {
    const next = new Set(collapsedGroups.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    collapsedGroups.value = next;
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

            <!-- Characters view: one flat, sortable list -->
            <template v-if="currentView === 'characters' && characters">
                <p
                    v-if="visibleCharacters.length === 0"
                    class="py-16 text-center text-sm text-muted-foreground"
                >
                    <template v-if="search">
                        No characters match "{{ search }}". Try a different
                        name.
                    </template>
                    <template v-else>
                        No characters registered yet. They appear here after
                        their first login.
                    </template>
                </p>
                <div v-else class="overflow-x-auto">
                    <div
                        class="grid min-w-[48rem] grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_5rem] rounded-xl border bg-card"
                    >
                        <div
                            class="col-span-full grid grid-cols-subgrid gap-4 border-b px-3 py-2 text-xs font-medium text-muted-foreground"
                        >
                            <button
                                v-for="column in SORTABLE_COLUMNS"
                                :key="column.key"
                                type="button"
                                class="flex cursor-pointer items-center gap-1 text-left transition-colors hover:text-foreground"
                                @click="sortBy(column.key)"
                            >
                                {{ column.label }}
                                <ArrowUp
                                    v-if="sortKey === column.key && sortAsc"
                                    class="h-3 w-3"
                                />
                                <ArrowDown
                                    v-else-if="sortKey === column.key"
                                    class="h-3 w-3"
                                />
                                <ChevronsUpDown
                                    v-else
                                    class="h-3 w-3 opacity-50"
                                />
                            </button>
                            <button
                                type="button"
                                class="flex cursor-pointer items-center justify-end gap-1 text-right transition-colors hover:text-foreground"
                                @click="sortBy('standing')"
                            >
                                Standing
                                <ArrowUp
                                    v-if="sortKey === 'standing' && sortAsc"
                                    class="h-3 w-3"
                                />
                                <ArrowDown
                                    v-else-if="sortKey === 'standing'"
                                    class="h-3 w-3"
                                />
                                <ChevronsUpDown
                                    v-else
                                    class="h-3 w-3 opacity-50"
                                />
                            </button>
                        </div>
                        <PilotCharacterRow
                            v-for="character in visibleCharacters"
                            :key="character.id"
                            :character="character"
                        />
                    </div>
                </div>
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
                <div v-else class="overflow-x-auto">
                    <div
                        class="grid min-w-[40rem] rounded-xl border bg-card"
                        :class="
                            currentView === 'alliances'
                                ? 'grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)_5rem]'
                                : 'grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_5rem]'
                        "
                    >
                        <div
                            class="col-span-full grid grid-cols-subgrid gap-4 border-b px-3 py-2 text-xs font-medium text-muted-foreground"
                        >
                            <span>Character</span>
                            <span>Account</span>
                            <span v-if="currentView === 'alliances'">
                                Corporation
                            </span>
                            <span class="text-right">Standing</span>
                        </div>
                        <template v-for="group in groups.data" :key="group.id">
                            <button
                                type="button"
                                class="col-span-full flex cursor-pointer items-center justify-between gap-4 border-b bg-muted/60 px-3 py-1.5 text-left transition-colors hover:bg-muted"
                                :aria-expanded="!collapsedGroups.has(group.id)"
                                @click="toggleGroup(group.id)"
                            >
                                <span
                                    class="flex min-w-0 items-center gap-2 text-sm font-semibold"
                                >
                                    <ChevronDown
                                        class="h-3.5 w-3.5 shrink-0 text-muted-foreground transition-transform"
                                        :class="{
                                            '-rotate-90': collapsedGroups.has(
                                                group.id,
                                            ),
                                        }"
                                    />
                                    <Avatar
                                        class="h-7 w-7 shrink-0 overflow-hidden rounded"
                                    >
                                        <AvatarImage
                                            :src="
                                                eveImage(
                                                    groupEntityType,
                                                    group.id,
                                                )!
                                            "
                                            :alt="group.name ?? ''"
                                        />
                                        <AvatarFallback
                                            class="rounded text-[9px] uppercase"
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
                                </span>
                                <span
                                    class="flex shrink-0 items-center gap-3 text-xs"
                                >
                                    <span
                                        v-if="group.standing"
                                        class="flex items-center gap-1 rounded-full px-2 py-0.5 font-semibold tabular-nums"
                                        :class="
                                            effectiveStandingChipClass(
                                                group.standing,
                                            )
                                        "
                                    >
                                        {{
                                            standingLabel(group.standing.value)
                                        }}
                                        <span class="font-normal opacity-75">
                                            via {{ group.standing.source }}
                                        </span>
                                    </span>
                                    <span
                                        v-else
                                        class="text-muted-foreground italic"
                                    >
                                        no standing
                                    </span>
                                    <span class="text-muted-foreground">
                                        {{ group.characters.length }}
                                        {{
                                            group.characters.length === 1
                                                ? 'character'
                                                : 'characters'
                                        }}
                                    </span>
                                </span>
                            </button>
                            <template v-if="!collapsedGroups.has(group.id)">
                                <PilotCharacterRow
                                    v-for="character in group.characters"
                                    :key="character.id"
                                    :character="character"
                                    :show-corporation="
                                        currentView === 'alliances'
                                    "
                                    :show-alliance="false"
                                />
                            </template>
                        </template>
                    </div>
                </div>
                <Pagination v-if="groups.last_page > 1" :links="groups.links" />
            </template>
        </AdminLayout>
    </AppLayout>
</template>

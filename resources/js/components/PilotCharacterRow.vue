<script setup lang="ts">
import EveEntityCell from '@/components/EveEntityCell.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { useInitials } from '@/composables/useInitials';
import { effectiveStandingTextClass, eveImage, standingLabel } from '@/lib/eve';

type EntitySummary = {
    id: number;
    name: string | null;
    ticker: string | null;
};

withDefaults(
    defineProps<{
        character: {
            id: number;
            name: string | null;
            is_main: boolean;
            account: { id: number | null; name: string | null };
            standing: { value: number; source: string } | null;
            corporation: EntitySummary | null;
            alliance: EntitySummary | null;
        };
        showAccount?: boolean;
        showCorporation?: boolean;
        showAlliance?: boolean;
    }>(),
    {
        showAccount: true,
        showCorporation: true,
        showAlliance: true,
    },
);

const { getInitials } = useInitials();
</script>

<template>
    <div
        class="col-span-full grid grid-cols-subgrid items-center gap-4 border-b px-3 py-2 transition-colors last:border-b-0 hover:bg-muted"
    >
        <div class="flex min-w-0 items-center gap-3">
            <Avatar class="h-7 w-7 shrink-0 overflow-hidden rounded">
                <AvatarImage
                    :src="eveImage('character', character.id)!"
                    :alt="character.name ?? ''"
                />
                <AvatarFallback class="rounded text-xs">
                    {{ getInitials(character.name ?? '?') }}
                </AvatarFallback>
            </Avatar>
            <span class="truncate text-sm font-medium">
                {{ character.name ?? character.id }}
            </span>
            <Badge
                v-if="character.is_main"
                variant="secondary"
                class="shrink-0"
            >
                Main
            </Badge>
        </div>
        <span v-if="showAccount" class="truncate text-sm text-muted-foreground">
            {{ character.account.name ?? character.account.id }}
        </span>
        <EveEntityCell
            v-if="showCorporation"
            type="corporation"
            :entity="character.corporation"
        />
        <EveEntityCell
            v-if="showAlliance"
            type="alliance"
            :entity="character.alliance"
        />
        <div v-if="character.standing" class="text-right">
            <p
                class="text-sm font-semibold tabular-nums"
                :class="effectiveStandingTextClass(character.standing)"
            >
                {{ standingLabel(character.standing.value) }}
            </p>
            <p class="text-[10px] leading-tight text-muted-foreground">
                {{ character.standing.source }}
            </p>
        </div>
        <span v-else class="text-right text-sm text-muted-foreground">-</span>
    </div>
</template>

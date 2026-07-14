<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { eveImage } from '@/lib/eve';

defineProps<{
    type: 'corporation' | 'alliance';
    entity: { id: number; name: string | null; ticker: string | null } | null;
}>();
</script>

<template>
    <div class="flex min-w-0 items-center gap-2 text-sm">
        <template v-if="entity">
            <Avatar class="h-5 w-5 shrink-0 overflow-hidden rounded">
                <AvatarImage
                    :src="eveImage(type, entity.id)!"
                    :alt="entity.name ?? ''"
                />
                <AvatarFallback class="rounded text-[9px] uppercase">
                    {{ type.slice(0, 2) }}
                </AvatarFallback>
            </Avatar>
            <span class="truncate">{{ entity.name ?? entity.id }}</span>
            <span
                v-if="entity.ticker"
                class="shrink-0 text-xs text-muted-foreground"
            >
                [{{ entity.ticker }}]
            </span>
        </template>
        <span v-else class="text-muted-foreground">-</span>
    </div>
</template>

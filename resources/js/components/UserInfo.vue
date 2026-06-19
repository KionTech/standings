<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { characterPortrait } from '@/lib/eve';
import type { Character } from '@/types';
import { computed } from 'vue';

type Props = {
    character: Character;
};

const props = defineProps<Props>();

const { getInitials } = useInitials();

const portraitUrl = computed(() => characterPortrait(props.character.id));
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarImage :src="portraitUrl" :alt="character.name" />
        <AvatarFallback class="rounded-lg text-black dark:text-white">
            {{ getInitials(character.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ character.name }}</span>
    </div>
</template>

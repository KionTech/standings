<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { PaginationLink } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: PaginationLink[];
}>();

function linkLabel(label: string): string {
    return label.replace('&laquo;', '«').replace('&raquo;', '»');
}
</script>

<template>
    <nav
        v-if="links.length > 3"
        class="flex flex-wrap items-center justify-center gap-1"
        aria-label="Pagination"
    >
        <template v-for="(link, index) in links" :key="index">
            <Button
                v-if="link.url"
                :variant="link.active ? 'secondary' : 'ghost'"
                size="sm"
                class="min-w-8 tabular-nums"
                as-child
            >
                <Link :href="link.url" preserve-state>
                    {{ linkLabel(link.label) }}
                </Link>
            </Button>
            <span
                v-else
                class="px-2 text-sm text-muted-foreground tabular-nums"
            >
                {{ linkLabel(link.label) }}
            </span>
        </template>
    </nav>
</template>

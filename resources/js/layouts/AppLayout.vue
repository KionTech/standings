<script setup lang="ts">
import AccessWarnings from '@/components/AccessWarnings.vue';
import Toaster from '@/components/Toaster.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <AccessWarnings />
        <slot />
    </AppLayout>
    <Toaster />
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        :class="className"
        v-bind="$attrs"
    >
        <path d="M12 2L4 7v10l8 5 8-5V7l-8-5Z" />
        <path d="M12 22V12" />
        <path d="M20 7l-8 5" />
        <path d="M4 7l8 5" />
    </svg>
</template>

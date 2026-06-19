<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CircleCheck, CircleX, Info, X } from '@lucide/vue';
import { ref, watch } from 'vue';

type ToastType = 'success' | 'error' | 'info';
type Toast = { id: number; type: ToastType; message: string };

const page = usePage();
const toasts = ref<Toast[]>([]);
let counter = 0;

function add(type: ToastType, message: string): void {
    const id = ++counter;
    toasts.value.push({ id, type, message });
    window.setTimeout(() => dismiss(id), 4000);
}

function dismiss(id: number): void {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

const icon: Record<ToastType, typeof Info> = {
    success: CircleCheck,
    error: CircleX,
    info: Info,
};

const accent: Record<ToastType, string> = {
    success: 'text-emerald-500',
    error: 'text-destructive',
    info: 'text-sky-500',
};

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) {
            return;
        }

        (['success', 'error', 'info'] as ToastType[]).forEach((type) => {
            if (flash[type]) {
                add(type, flash[type] as string);
            }
        });
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <div
        class="pointer-events-none fixed top-4 left-1/2 z-[100] flex w-full max-w-sm -translate-x-1/2 flex-col items-center gap-2"
    >
        <TransitionGroup
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="-translate-y-2 opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="-translate-y-2 opacity-0"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto flex items-start gap-3 rounded-lg border bg-background p-3 shadow-lg"
            >
                <component
                    :is="icon[toast.type]"
                    class="mt-0.5 h-5 w-5 shrink-0"
                    :class="accent[toast.type]"
                />
                <p class="flex-1 text-sm">{{ toast.message }}</p>
                <button
                    type="button"
                    class="text-muted-foreground transition-colors hover:text-foreground"
                    aria-label="Dismiss"
                    @click="dismiss(toast.id)"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

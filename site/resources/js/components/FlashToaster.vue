<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { TransitionGroup } from 'vue';

interface Flash {
    status?: string;
    error?: string;
}

const page = usePage();

const toasts = ref<Array<{ id: number; type: 'success' | 'error'; message: string }>>([]);
let nextId = 0;
const TOAST_DURATION = 5000;

watch(
    () => (page.props as { flash?: Flash })?.flash,
    (flash) => {
        if (!flash) return;
        if (flash.error) {
            const id = ++nextId;
            toasts.value.push({ id, type: 'error', message: flash.error });
            setTimeout(() => dismiss(id), TOAST_DURATION);
        }
        if (flash.status) {
            const id = ++nextId;
            toasts.value.push({ id, type: 'success', message: flash.status });
            setTimeout(() => dismiss(id), TOAST_DURATION);
        }
    },
    { immediate: true, deep: true }
);

function dismiss(id: number) {
    toasts.value = toasts.value.filter((t) => t.id !== id);
}
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-0 z-[10000] flex flex-col items-center gap-2 p-4 pt-6"
            aria-live="polite"
        >
            <TransitionGroup name="toast" tag="div" class="flex flex-col items-center gap-2">
                <div
                    v-for="t in toasts"
                    :key="t.id"
                    class="pointer-events-auto flex min-w-[280px] max-w-md items-start gap-3 rounded-xl border px-4 py-3 shadow-lg backdrop-blur-sm"
                    :class="{
                        'border-red-200 bg-red-50/95 text-red-900 dark:border-red-900 dark:bg-red-950/95 dark:text-red-100':
                            t.type === 'error',
                        'border-emerald-200 bg-emerald-50/95 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/95 dark:text-emerald-100':
                            t.type === 'success',
                    }"
                >
                    <p class="flex-1 text-sm font-medium">{{ t.message }}</p>
                    <button
                        type="button"
                        class="-m-1 rounded p-1 opacity-70 transition hover:opacity-100 focus:outline-none focus:ring-2"
                        :aria-label="'Закрыть'"
                        @click="dismiss(t.id)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.2s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>

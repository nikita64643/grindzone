<script setup lang="ts">
import SiteHeader from '@/components/SiteHeader.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="min-h-screen overflow-x-hidden bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <SiteHeader />

        <main class="flex flex-col items-center justify-center px-4 py-10 sm:py-16">
            <div class="w-full max-w-sm">
                <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
                    <div class="mb-6 text-center sm:mb-8">
                        <h1 class="text-xl font-bold text-zinc-900 dark:text-white sm:text-2xl">
                            {{ title }}
                        </h1>
                        <p v-if="description" class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ description }}
                        </p>
                    </div>
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>

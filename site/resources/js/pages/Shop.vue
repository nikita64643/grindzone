<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Construction } from 'lucide-vue-next';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';
</script>

<template>
    <Head title="Магазин — GrindZone" />

    <div class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <SiteHeader />

        <main class="mx-auto max-w-2xl px-4 py-12 sm:py-20">
            <div class="rounded-2xl border border-zinc-200 bg-white p-8 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-12">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-500">
                    <Construction class="h-8 w-8" />
                </div>
                <h1 class="mb-2 text-xl font-bold text-zinc-900 dark:text-white sm:text-2xl">
                    Магазин
                </h1>
                <p class="text-zinc-600 dark:text-zinc-400">
                    Раздел в разработке. Скоро здесь можно будет покупать предметы и услуги для серверов.
                </p>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>

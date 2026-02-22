<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';

</script>

<template>
    <Head title="Платёж в обработке — GrindZone" />

    <div class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <SiteHeader />

        <main class="mx-auto max-w-xl px-4 py-16 text-center">
            <div class="mb-6 flex justify-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/40">
                    <Loader2 class="h-10 w-10 animate-spin text-amber-600 dark:text-amber-400" />
                </div>
            </div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                Платёж в обработке
            </h1>
            <p class="mt-3 text-zinc-600 dark:text-zinc-400">
                Ваш платёж обрабатывается. Обычно это занимает несколько минут. Средства появятся на балансе после подтверждения.
            </p>
            <Link
                href="/profile"
                class="mt-8 inline-flex items-center justify-center rounded-xl bg-amber-500 px-6 py-3 font-medium text-zinc-950 transition hover:bg-amber-400"
            >
                Вернуться в профиль
            </Link>
        </main>

        <SiteFooter />
    </div>
</template>

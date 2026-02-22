<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CheckCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';

const page = usePage();
const flashStatus = computed(() => (page.props.flash as { status?: string })?.status);
</script>

<template>
    <Head title="Оплата успешна — GrindZone" />

    <div class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <SiteHeader />

        <main class="mx-auto max-w-xl px-4 py-16 text-center">
            <p
                v-if="flashStatus"
                class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200"
            >
                {{ flashStatus }}
            </p>
            <div class="mb-6 flex justify-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                    <CheckCircle class="h-10 w-10 text-emerald-600 dark:text-emerald-400" />
                </div>
            </div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                Оплата прошла успешно
            </h1>
            <p class="mt-3 text-zinc-600 dark:text-zinc-400">
                Спасибо за оплату! Средства зачислены или привилегия активирована.
            </p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <Link
                    href="/donate"
                    class="inline-flex items-center justify-center rounded-xl bg-amber-500 px-6 py-3 font-medium text-zinc-950 transition hover:bg-amber-400"
                >
                    К привилегиям
                </Link>
                <Link
                    href="/profile"
                    class="inline-flex items-center justify-center rounded-xl border border-zinc-200 px-6 py-3 font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                >
                    В профиль
                </Link>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>

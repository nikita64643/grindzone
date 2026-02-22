<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';
</script>

<template>
    <Head title="Реквизиты — GrindZone" />

    <div class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <SiteHeader />

        <main class="mx-auto max-w-4xl px-4 py-8 sm:py-12">
            <h1 class="mb-2 text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">
                Реквизиты
            </h1>
            <p class="mb-6 text-sm text-zinc-600 dark:text-zinc-400">
                Информация для оплаты и оформления документов.
            </p>

            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Получатель
                        </dt>
                        <dd class="mt-1 text-zinc-900 dark:text-white">
                            Тац Никита Владимирович
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            ИНН
                        </dt>
                        <dd class="mt-1 font-mono text-zinc-900 dark:text-white">
                            662344873322
                        </dd>
                    </div>
                </dl>

                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
                    Если вам нужны дополнительные реквизиты (банк/счёт/назначение платежа) — напишите в поддержку.
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>


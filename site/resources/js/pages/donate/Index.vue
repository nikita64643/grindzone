<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Server } from 'lucide-vue-next';
import { computed } from 'vue';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';

interface ServerItem {
    id: number;
    name: string;
    slug: string;
    version: string;
    port: number;
}

const props = defineProps<{
    servers: ServerItem[];
}>();

const versionOrder = [
    '1.16.5 Paper',
    '1.21.10 Paper',
    '1.16.5',
    '1.21.10',
] as const;
const versionPriority = new Map<string, number>(versionOrder.map((v, i) => [v, i]));

const serverGroups = computed(() => {
    const byVersion: Record<string, ServerItem[]> = {};
    for (const s of props.servers) {
        if (!byVersion[s.version]) byVersion[s.version] = [];
        byVersion[s.version].push(s);
    }
    const sortedVersions = Object.keys(byVersion).sort((a, b) => {
        const pa = versionPriority.get(a) ?? Number.POSITIVE_INFINITY;
        const pb = versionPriority.get(b) ?? Number.POSITIVE_INFINITY;
        if (pa !== pb) return pa - pb;
        return a.localeCompare(b, 'ru');
    });
    return sortedVersions.map((version) => ({ version, servers: byVersion[version]! }));
});
</script>

<template>
    <Head title="Донат — Выбор сервера | GrindZone" />

    <div class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <SiteHeader />

        <main class="mx-auto max-w-4xl px-4 py-8 sm:py-12">
            <h1 class="mb-2 text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">
                Донат
            </h1>
            <p class="mb-6 text-zinc-600 dark:text-zinc-400">
                Выберите сервер, на который хотите задонатить. Затем выберите привилегию (VIP, Premium или Legend) и оплатите с баланса.
            </p>

            <section class="mb-8 rounded-xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-zinc-700 dark:text-zinc-300">
                    Привилегии
                </h2>
                <p class="mb-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <strong>VIP</strong> — цветной ник, приоритет входа, /hat, /nick. <strong>Premium</strong> — плюс /home, кит, защита 5 участков. <strong>Legend</strong> — плюс 20 участков, цвет в табе, бонусы в ивентах.
                </p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Полный список возможностей и способ выдачи — на странице выбранного сервера.
                </p>
            </section>

            <div class="space-y-8">
                <section
                    v-for="group in serverGroups"
                    :key="group.version"
                    class="rounded-2xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-900/30 sm:p-5"
                >
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">
                        {{ group.version }} — {{ group.servers.length }} {{ group.servers.length === 1 ? 'мир' : 'миров' }}
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <Link
                            v-for="server in group.servers"
                            :key="server.slug"
                            :href="`/donate/${server.slug}`"
                            class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-amber-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900/50 dark:hover:border-amber-500/30"
                        >
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-500">
                                <Server class="h-6 w-6" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-zinc-900 dark:text-white">
                                    {{ server.name }}
                                </div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ server.version }}
                                </div>
                            </div>
                            <span class="text-zinc-400 dark:text-zinc-500">→</span>
                        </Link>
                    </div>
                </section>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>

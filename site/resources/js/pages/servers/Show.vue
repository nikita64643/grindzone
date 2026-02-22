<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Package, Wallet } from 'lucide-vue-next';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';

interface Server {
    id: number;
    name: string;
    slug: string;
    version: string;
    port: number;
    description: string | null;
}

interface ServerStatus {
    online: boolean;
    players_online: number;
    players_max: number;
}

interface Mod {
    name: string;
    description: string;
}

const props = withDefaults(
    defineProps<{
        server: Server;
        status: ServerStatus | null;
        mods?: Mod[];
    }>(),
    { mods: () => [] },
);
</script>

<template>
    <Head :title="`${props.server.name} — ${props.server.version} | GrindZone`" />

    <div class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <SiteHeader />

        <main class="mx-auto max-w-4xl px-4 py-8 sm:py-12">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
                <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">
                            {{ props.server.name }}
                        </h1>
                        <p class="mt-1 text-sm text-amber-600 dark:text-amber-400">
                            {{ props.server.version }}
                        </p>
                    </div>
                    <Link
                        :href="`/donate/${props.server.slug}`"
                        class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-amber-500 px-4 py-2 font-medium text-zinc-950 shadow transition hover:bg-amber-400"
                    >
                        <Wallet class="h-4 w-4" />
                        Купить привилегию
                    </Link>
                </div>

                <div class="mb-6 flex flex-wrap items-center gap-3">
                    <span
                        v-if="props.status !== null"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium"
                        :class="props.status.online
                            ? 'bg-emerald-500/15 text-emerald-700 dark:bg-emerald-400/20 dark:text-emerald-400'
                            : 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400'"
                    >
                        <span
                            class="h-2 w-2 shrink-0 rounded-full"
                            :class="props.status.online ? 'bg-emerald-500' : 'bg-zinc-400'"
                        />
                        {{ props.status.online ? `Онлайн ${props.status.players_online} из ${props.status.players_max}` : 'Офлайн' }}
                    </span>
                </div>

                <p
                    v-if="props.server.description"
                    class="mb-6 whitespace-pre-wrap text-sm text-zinc-600 dark:text-zinc-400"
                >
                    {{ props.server.description }}
                </p>
                <p
                    v-else
                    class="mb-6 text-sm text-zinc-500 dark:text-zinc-500"
                >
                    Подключение: <code class="rounded bg-zinc-200 px-1.5 py-0.5 font-mono text-amber-600 dark:bg-zinc-700 dark:text-amber-500">localhost</code> (через лобби/прокси) или IP твоего ПК в сети.
                </p>

                <section v-if="props.mods.length > 0" class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-900 dark:text-white">
                        <Package class="h-5 w-5 text-amber-500" />
                        Моды в этом режиме
                    </h2>
                    <p class="mb-4 text-sm text-zinc-500 dark:text-zinc-400">
                        На сервере установлены следующие моды. Установи такие же в папку <code class="rounded bg-zinc-200 px-1 py-0.5 font-mono text-xs dark:bg-zinc-700">mods</code> клиента для совместимости.
                    </p>
                    <ul class="space-y-3">
                        <li
                            v-for="(mod, index) in props.mods"
                            :key="index"
                            class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-800/50"
                        >
                            <div class="font-medium text-zinc-900 dark:text-white">
                                {{ mod.name }}
                            </div>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ mod.description }}
                            </p>
                        </li>
                    </ul>
                </section>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';

interface ServerStatus {
    online: boolean;
    players_online: number;
    players_max: number;
}

interface Server {
    name: string;
    slug: string;
    version: string;
    port: number;
    status: ServerStatus | null;
}

interface Group {
    game: string;
    available: boolean;
    servers: Server[];
}

const props = defineProps<{
    groups: Group[];
}>();

const statusByPort = ref<Record<number, ServerStatus>>({});

async function fetchServerStatus() {
    try {
        const res = await fetch('/api/servers/status');
        if (!res.ok) return;
        const data = await res.json();
        const next: Record<number, ServerStatus> = {};
        for (const s of data.servers ?? []) {
            const port = Number(s.port);
            if (!Number.isInteger(port)) continue;
            next[port] = {
                online: Boolean(s.online),
                players_online: Number(s.players_online) || 0,
                players_max: Number(s.players_max) || 0,
            };
        }
        statusByPort.value = next;
    } catch {
        // ignore
    }
}

let pollInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    const initial: Record<number, ServerStatus> = {};
    for (const group of props.groups) {
        for (const server of group.servers) {
            if (server.status) initial[server.port] = server.status;
        }
    }
    statusByPort.value = initial;
    fetchServerStatus();
    pollInterval = setInterval(fetchServerStatus, 10_000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

const groupsWithStatus = computed(() =>
    props.groups.map((group) => ({
        ...group,
        servers: group.servers.map((server) => ({
            ...server,
            status: (statusByPort.value[server.port] ?? server.status) as ServerStatus | null,
        })),
    })),
);
</script>

<template>
    <Head title="Все сервера — GrindZone" />

    <div class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <SiteHeader />

        <main class="mx-auto max-w-4xl px-4 py-8 sm:py-12">
            <h1 class="mb-8 text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">Все сервера</h1>

            <div class="space-y-8">
                <section
                    v-for="group in groupsWithStatus"
                    :key="group.game"
                    class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/50"
                >
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ group.game }}
                            <span
                                v-if="!group.available"
                                class="ml-2 text-sm font-normal text-zinc-500 dark:text-zinc-400"
                            >
                                — В разработке
                            </span>
                        </h2>
                    </div>

                    <div v-if="group.available && group.servers.length > 0" class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        <Link
                            v-for="server in group.servers"
                            :key="server.slug"
                            :href="`/servers/${server.slug}`"
                            class="group flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                        >
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ server.name }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ server.version }}</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <span
                                    v-if="server.status !== null"
                                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-sm"
                                    :class="server.status.online
                                        ? 'bg-emerald-500/15 text-emerald-700 dark:bg-emerald-400/20 dark:text-emerald-400'
                                        : 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400'"
                                >
                                    <span
                                        class="h-1.5 w-1.5 shrink-0 rounded-full"
                                        :class="server.status.online ? 'bg-emerald-500' : 'bg-zinc-400'"
                                    />
                                    {{ server.status.online ? `${server.status.players_online} онлайн` : 'Офлайн' }}
                                </span>
                                <span class="text-zinc-400 transition group-hover:text-amber-500">
                                    <ChevronRight class="h-5 w-5" />
                                </span>
                            </div>
                        </Link>
                    </div>

                    <div
                        v-else-if="!group.available"
                        class="px-6 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400"
                    >
                        Скоро на GrindZone
                    </div>

                    <div
                        v-else
                        class="px-6 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400"
                    >
                        Нет серверов
                    </div>
                </section>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>

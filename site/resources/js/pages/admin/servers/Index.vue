<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FileText, RefreshCw, Server } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface ServerRow {
    id: number;
    name: string;
    slug: string;
    version: string;
    port: number;
    description: string | null;
    log_path: string;
    has_log: boolean;
}

defineProps<{
    servers: ServerRow[];
}>();
</script>

<template>
    <Head title="Серверы — Админка | GrindZone" />

    <AdminLayout>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
            <h1 class="mb-6 text-2xl font-bold text-zinc-900 dark:text-white">
                Управление серверами
            </h1>

            <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                Список серверов из БД. Открой сервер для просмотра логов и перезапуска.
            </p>

            <div v-if="servers.length === 0" class="rounded-xl border border-zinc-200 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-800/50">
                <p class="text-zinc-600 dark:text-zinc-400">
                    Серверов пока нет. Запусти сидер или добавь записи в БД.
                </p>
            </div>

            <div v-else class="space-y-3">
                <Link
                    v-for="s in servers"
                    :key="s.id"
                    :href="`/admin/servers/${s.slug}`"
                    class="flex items-center gap-4 rounded-xl border border-zinc-200 p-4 transition hover:border-amber-500/40 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-amber-500/30 dark:hover:bg-zinc-800/50"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-500">
                        <Server class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-zinc-900 dark:text-white">
                            {{ s.name }}
                        </div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ s.version }} · порт {{ s.port }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            v-if="s.has_log"
                            class="inline-flex items-center gap-1 rounded bg-emerald-500/15 px-2 py-0.5 text-xs text-emerald-700 dark:text-emerald-400"
                        >
                            <FileText class="h-3 w-3" />
                            Лог
                        </span>
                        <span class="text-zinc-400 dark:text-zinc-500">→</span>
                    </div>
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>

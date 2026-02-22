<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ShoppingBag } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface ServerItem {
    id: number;
    name: string;
    slug: string;
    version: string;
}

defineProps<{
    servers: ServerItem[];
}>();
</script>

<template>
    <Head title="Магазин DynamicShop — Админка | GrindZone" />

    <AdminLayout>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
            <h1 class="mb-6 text-2xl font-bold text-zinc-900 dark:text-white">
                Магазин DynamicShop
            </h1>

            <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                Выберите сервер для редактирования цен предметов в магазине. Отображаются только серверы с установленным DynamicShop.
            </p>

            <div
                v-if="servers.length === 0"
                class="rounded-xl border border-zinc-200 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-800/50"
            >
                <ShoppingBag class="mx-auto mb-3 h-12 w-12 text-zinc-400 dark:text-zinc-500" />
                <p class="text-zinc-600 dark:text-zinc-400">
                    Нет серверов с DynamicShop. Установите плагин на сервер и перезапустите его.
                </p>
            </div>

            <div v-else class="space-y-3">
                <Link
                    v-for="s in servers"
                    :key="s.id"
                    :href="`/admin/shop/${s.slug}`"
                    class="flex items-center gap-4 rounded-xl border border-zinc-200 p-4 transition hover:border-amber-500/40 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-amber-500/30 dark:hover:bg-zinc-800/50"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-500">
                        <ShoppingBag class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-zinc-900 dark:text-white">
                            {{ s.name }}
                        </div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ s.version }}
                        </div>
                    </div>
                    <span class="text-zinc-400 dark:text-zinc-500">→</span>
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bell, ChevronRight } from 'lucide-vue-next';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';

interface NotificationItem {
    id: string;
    type: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    notifications: {
        data: NotificationItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: PaginationLink[];
    };
}>();

function formatDate(iso: string): string {
    const d = new Date(iso);
    const now = new Date();
    const diff = now.getTime() - d.getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'Только что';
    if (mins < 60) return `${mins} мин назад`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours} ч назад`;
    const days = Math.floor(hours / 24);
    if (days < 7) return `${days} дн назад`;
    return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', year: d.getFullYear() !== now.getFullYear() ? 'numeric' : undefined });
}

function notificationTitle(item: NotificationItem): string {
    const d = item.data;
    if (typeof d?.title === 'string') return d.title;
    if (typeof d?.message === 'string') return d.message;
    return 'Уведомление';
}

function notificationBody(item: NotificationItem): string | null {
    const d = item.data;
    if (typeof d?.body === 'string') return d.body;
    if (typeof d?.message === 'string' && typeof d?.title === 'string') return d.message;
    return null;
}
</script>

<template>
    <Head title="Уведомления — GrindZone" />

    <div class="min-h-screen overflow-x-hidden bg-zinc-50 text-zinc-900 dark:bg-[#0a0a0d] dark:text-white [padding:env(safe-area-inset-top)_env(safe-area-inset-right)_env(safe-area-inset-bottom)_env(safe-area-inset-left)]">
        <SiteHeader />

        <div class="mx-auto max-w-2xl px-4 py-8 sm:py-12">
            <div class="mb-8 flex items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-orange-500/15 text-orange-600 dark:text-orange-400">
                    <Bell class="h-6 w-6" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Уведомления</h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Все ваши уведомления в одном месте</p>
                </div>
            </div>

            <div v-if="notifications.data.length === 0" class="rounded-2xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-800 dark:bg-[#0f0f12]">
                <Bell class="mx-auto mb-4 h-12 w-12 text-zinc-400" />
                <p class="text-zinc-600 dark:text-zinc-400">У вас пока нет уведомлений</p>
                <Link href="/" class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-orange-600 hover:underline dark:text-orange-400">
                    На главную
                    <ChevronRight class="h-4 w-4" />
                </Link>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="item in notifications.data"
                    :key="item.id"
                    class="rounded-xl border border-zinc-200 bg-white p-4 transition dark:border-zinc-800 dark:bg-[#0f0f12]"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                            :class="item.read_at ? 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' : 'bg-orange-500/15 text-orange-600 dark:text-orange-400'"
                        >
                            <Bell class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-medium text-zinc-900 dark:text-white">
                                {{ notificationTitle(item) }}
                            </h3>
                            <p v-if="notificationBody(item)" class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ notificationBody(item) }}
                            </p>
                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-500">
                                {{ formatDate(item.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>

                <nav
                    v-if="notifications.last_page > 1"
                    class="flex flex-wrap items-center justify-center gap-2 pt-4"
                    aria-label="Пагинация"
                >
                    <template v-for="(link, i) in notifications.links" :key="i">
                        <span
                            v-if="!link.url"
                            class="flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-zinc-200 px-3 text-sm text-zinc-400 dark:border-zinc-700 dark:text-zinc-500"
                        >
                            {{ link.label }}
                        </span>
                        <Link
                            v-else
                            :href="link.url"
                            class="flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border px-3 text-sm font-medium transition"
                            :class="
                                link.active
                                    ? 'border-orange-500 bg-orange-500 text-white dark:border-orange-500 dark:bg-orange-500'
                                    : 'border-zinc-200 text-zinc-700 hover:border-orange-500/50 hover:bg-orange-500/10 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-orange-500/30 dark:hover:bg-orange-500/10'
                            "
                            preserve-scroll
                        >
                            {{ link.label }}
                        </Link>
                    </template>
                </nav>
            </div>
        </div>

        <SiteFooter />
    </div>
</template>

<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Crown, Pencil, Sparkles } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface PrivilegeRow {
    id: number;
    key: string;
    name: string;
    description: string | null;
    price: number;
    features: string[];
    server_slugs: string[];
}

interface TabPrefixResult {
    port: number;
    label: string;
    ok: boolean;
    groups: Record<string, boolean>;
}

defineProps<{
    privileges: PrivilegeRow[];
}>();

const page = usePage();
const flash = page.props.flash as { status?: string; tab_prefixes_results?: TabPrefixResult[] };
const flashStatus = flash?.status;
const tabResults = flash?.tab_prefixes_results;

const applyingTab = ref(false);
function applyTabPrefixes() {
    applyingTab.value = true;
    router.post('/admin/privileges/apply-tab-prefixes', {}, {
        preserveScroll: true,
        onFinish: () => { applyingTab.value = false; },
    });
}
</script>

<template>
    <Head title="Привилегии — Админка | GrindZone" />

    <AdminLayout>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
            <h1 class="mb-6 text-2xl font-bold text-zinc-900 dark:text-white">
                Привилегии
            </h1>
            <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                Группы доната (VIP, Premium, Legend). Редактируйте название, цену, список возможностей и привязку к серверам.
            </p>
            <div
                v-if="flashStatus"
                class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200"
            >
                {{ flashStatus }}
            </div>

            <div
                v-if="tabResults && tabResults.length"
                class="mb-6 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700"
            >
                <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                    Результат применения префиксов в табе
                </div>
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <li
                        v-for="r in tabResults"
                        :key="r.port"
                        class="flex items-center justify-between px-4 py-2 text-sm"
                        :class="r.ok ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'"
                    >
                        <span>{{ r.label }}</span>
                        <span>{{ r.ok ? 'OK' : 'Ошибка RCON' }}</span>
                    </li>
                </ul>
            </div>

            <div class="mb-6 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    :disabled="applyingTab"
                    class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-600 disabled:opacity-50 dark:bg-amber-600 dark:hover:bg-amber-700"
                    @click="applyTabPrefixes"
                >
                    <Sparkles class="h-4 w-4" />
                    {{ applyingTab ? 'Применяем…' : 'Применить префиксы в табе' }}
                </button>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Устанавливает префиксы [VIP], [Premium], [Legend] для групп LuckPerms на всех серверах 1.21 (по RCON).
                </p>
            </div>

            <div v-if="privileges.length === 0" class="rounded-xl border border-zinc-200 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-800/50">
                <p class="mb-4 text-zinc-600 dark:text-zinc-400">
                    Привилегий нет. Запустите сидер: <code class="rounded bg-zinc-200 px-1 dark:bg-zinc-700">php artisan db:seed --class=PrivilegeSeeder</code>
                </p>
            </div>

            <div v-else class="space-y-4">
                <Link
                    v-for="p in privileges"
                    :key="p.id"
                    :href="`/admin/privileges/${p.id}/edit`"
                    class="flex items-start gap-4 rounded-xl border border-zinc-200 p-4 transition hover:border-amber-500/40 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-amber-500/30 dark:hover:bg-zinc-800/50"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-500">
                        <Crown class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-zinc-900 dark:text-white">
                            {{ p.name }}
                        </div>
                        <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ p.description || '—' }}
                        </p>
                        <p class="mt-2 text-lg font-medium text-amber-600 dark:text-amber-400">
                            {{ p.price?.toLocaleString('ru-RU') }} ₽
                        </p>
                        <p v-if="p.server_slugs?.length" class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                            Серверов: {{ p.server_slugs.length }} ({{ p.server_slugs.slice(0, 3).join(', ') }}{{ p.server_slugs.length > 3 ? '…' : '' }})
                        </p>
                        <p v-else class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                            Не привязана к серверам
                        </p>
                    </div>
                    <div class="shrink-0">
                        <span class="inline-flex items-center gap-1 rounded bg-zinc-100 px-2 py-1 text-xs text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                            <Pencil class="h-3 w-3" />
                            Редактировать
                        </span>
                    </div>
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>

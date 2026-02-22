<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { CreditCard, Coins, Crown } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface TopupItem {
    id: number;
    user_id: number;
    user_name: string;
    amount: number;
    coins: number;
    order_id: string;
    status: string;
    created_at: string;
}

interface PurchaseItem {
    id: number;
    user_id: number;
    user_name: string;
    server_name: string;
    privilege_name: string;
    amount: number;
    order_id: string;
    status: string;
    created_at: string;
}

const props = defineProps<{
    topups: TopupItem[];
    purchases: PurchaseItem[];
}>();

const page = usePage();
const flashStatus = computed(() => (page.props.flash as { status?: string })?.status);

const statusOptions = [
    { value: 'pending', label: 'В обработке' },
    { value: 'completed', label: 'Оплачено' },
    { value: 'failed', label: 'Ошибка' },
    { value: 'refunded', label: 'Возврат' },
];

function formatPrice(value: number): string {
    return value.toLocaleString('ru-RU') + ' ₽';
}

function formatDate(iso: string): string {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function changeTopupStatus(topupId: number, status: string) {
    router.put(`/admin/payments/topups/${topupId}/status`, { status });
}

function changePurchaseStatus(purchaseId: number, status: string) {
    router.put(`/admin/payments/purchases/${purchaseId}/status`, { status });
}

function statusClass(status: string): string {
    switch (status) {
        case 'completed':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'failed':
        case 'refunded':
            return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
        default:
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
    }
}
</script>

<template>
    <Head title="Оплаты — Админка | GrindZone" />

    <AdminLayout>
        <div class="space-y-8">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
                <h1 class="mb-6 text-2xl font-bold text-zinc-900 dark:text-white">
                    Оплаты
                </h1>

                <div
                    v-if="flashStatus"
                    class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200"
                >
                    {{ flashStatus }}
                </div>

                <!-- Пополнения баланса -->
                <section class="mb-10">
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-900 dark:text-white">
                        <Coins class="h-5 w-5 text-amber-500" />
                        Пополнения баланса
                    </h2>
                    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-zinc-200 bg-zinc-50/80 dark:border-zinc-700 dark:bg-zinc-800/50">
                                <tr>
                                    <th class="px-4 py-3 font-medium text-zinc-900 dark:text-white">Дата</th>
                                    <th class="px-4 py-3 font-medium text-zinc-900 dark:text-white">Пользователь</th>
                                    <th class="px-4 py-3 font-medium text-zinc-900 dark:text-white">Сумма</th>
                                    <th class="px-4 py-3 font-medium text-zinc-900 dark:text-white">Монеты</th>
                                    <th class="px-4 py-3 font-medium text-zinc-900 dark:text-white">Заказ</th>
                                    <th class="px-4 py-3 font-medium text-zinc-900 dark:text-white">Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="t in topups"
                                    :key="t.id"
                                    class="border-b border-zinc-100 dark:border-zinc-800"
                                >
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                        {{ formatDate(t.created_at) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Link
                                            :href="`/admin/users/${t.user_id}`"
                                            class="text-amber-600 hover:underline dark:text-amber-400"
                                        >
                                            {{ t.user_name }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3 font-medium">{{ formatPrice(t.amount) }}</td>
                                    <td class="px-4 py-3">{{ t.coins.toLocaleString('ru-RU') }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ t.order_id }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <select
                                            :value="t.status"
                                            class="rounded-lg border border-zinc-300 px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                            :class="statusClass(t.status)"
                                            @change="changeTopupStatus(t.id, ($event.target as HTMLSelectElement).value)"
                                        >
                                            <option
                                                v-for="opt in statusOptions"
                                                :key="opt.value"
                                                :value="opt.value"
                                            >
                                                {{ opt.label }}
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                                <tr v-if="topups.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                        Нет пополнений
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Покупки привилегий -->
                <section>
                    <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-900 dark:text-white">
                        <Crown class="h-5 w-5 text-amber-500" />
                        Покупки привилегий
                    </h2>
                    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-zinc-200 bg-zinc-50/80 dark:border-zinc-700 dark:bg-zinc-800/50">
                                <tr>
                                    <th class="px-4 py-3 font-medium text-zinc-900 dark:text-white">Дата</th>
                                    <th class="px-4 py-3 font-medium text-zinc-900 dark:text-white">Пользователь</th>
                                    <th class="px-4 py-3 font-medium text-zinc-900 dark:text-white">Сервер</th>
                                    <th class="px-4 py-3 font-medium text-zinc-900 dark:text-white">Привилегия</th>
                                    <th class="px-4 py-3 font-medium text-zinc-900 dark:text-white">Сумма</th>
                                    <th class="px-4 py-3 font-medium text-zinc-900 dark:text-white">Заказ</th>
                                    <th class="px-4 py-3 font-medium text-zinc-900 dark:text-white">Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="p in purchases"
                                    :key="p.id"
                                    class="border-b border-zinc-100 dark:border-zinc-800"
                                >
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                        {{ formatDate(p.created_at) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Link
                                            :href="`/admin/users/${p.user_id}`"
                                            class="text-amber-600 hover:underline dark:text-amber-400"
                                        >
                                            {{ p.user_name }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3">{{ p.server_name }}</td>
                                    <td class="px-4 py-3">{{ p.privilege_name }}</td>
                                    <td class="px-4 py-3 font-medium">{{ formatPrice(p.amount) }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ p.order_id }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <select
                                            :value="p.status"
                                            class="rounded-lg border border-zinc-300 px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                                            :class="statusClass(p.status)"
                                            @change="changePurchaseStatus(p.id, ($event.target as HTMLSelectElement).value)"
                                        >
                                            <option
                                                v-for="opt in statusOptions"
                                                :key="opt.value"
                                                :value="opt.value"
                                            >
                                                {{ opt.label }}
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                                <tr v-if="purchases.length === 0">
                                    <td colspan="7" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                        Нет покупок
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>

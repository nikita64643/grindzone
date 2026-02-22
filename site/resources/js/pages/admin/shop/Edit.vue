<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';

interface ShopItem {
    material: string;
    base: number;
    enabled: boolean;
}

interface ServerInfo {
    id: number;
    name: string;
    slug: string;
    version: string;
}

const props = defineProps<{
    server: ServerInfo;
    items: ShopItem[];
    categories: Record<string, unknown>;
    sell_tax_percent?: number;
}>();

const sellTax = computed(() => props.sell_tax_percent ?? 20);

const searchQuery = ref('');
const showDisabledOnly = ref(false);

const form = useForm({
    items: props.items.map((i) => ({ material: i.material, base: i.base })),
});

const filteredItems = computed(() => {
    let list = props.items;
    const q = searchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter((i) => i.material.toLowerCase().includes(q));
    }
    if (showDisabledOnly.value) {
        list = list.filter((i) => !i.enabled);
    }
    return list;
});

function parsePrice(v: string | number): number {
    const n = Number(v);
    return Number.isNaN(n) ? -1 : n;
}

function updateItem(material: string, base: number) {
    const idx = form.items.findIndex((i) => i.material === material);
    if (idx !== -1) {
        form.items = [...form.items];
        form.items[idx] = { material, base };
    }
}

function getItemBase(material: string): number {
    const item = form.items.find((i) => i.material === material);
    return item ? item.base : -1;
}

/** Цена покупки (base) — редактируемая. */
function getBuyPrice(material: string): number {
    return getItemBase(material);
}

/** Цена продажи: buy * (1 - tax/100). При tax=20%: sell = buy * 0.8. */
function getSellPrice(material: string): number {
    const buy = getBuyPrice(material);
    if (buy < 0) return -1;
    return Math.round(buy * (1 - sellTax.value / 100) * 100) / 100;
}

/** Финальная цена: цена покупки + налог = buy * (1 + tax/100). Высчитано: base 5 → 5.23 = +4.6% */
const BUY_TAX_PERCENT = 4.6;

function getFinalPrice(material: string): number {
    const buy = getBuyPrice(material);
    if (buy < 0) return -1;
    return Math.round(buy * (1 + BUY_TAX_PERCENT / 100) * 100) / 100;
}
</script>

<template>
    <Head :title="`Магазин ${server.name} | Админка`" />

    <AdminLayout>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
            <Link
                :href="'/admin/shop'"
                class="mb-6 inline-flex items-center gap-2 text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
            >
                <ArrowLeft class="h-4 w-4" />
                К списку магазинов
            </Link>

            <h1 class="mb-2 text-2xl font-bold text-zinc-900 dark:text-white">
                Магазин: {{ server.name }}
            </h1>
            <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                {{ server.version }} · {{ items.length }} предметов. Финальная цена = покупка + {{ BUY_TAX_PERCENT }}% налог. Продажа: −{{ sellTax }}%. -1 = отключён. После сохранения: <code class="rounded bg-zinc-200 px-1 dark:bg-zinc-700">/shopadmin reload</code>.
            </p>

            <div v-if="$page.props.flash?.status" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                {{ $page.props.flash.status }}
            </div>
            <InputError v-if="form.errors.save" :message="form.errors.save" class="mb-6" />

            <form
                class="space-y-6"
                @submit.prevent="form.put(`/admin/shop/${server.slug}`)"
            >
                <div class="flex flex-wrap items-center gap-4">
                    <div class="relative flex-1 min-w-[200px]">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                        <Input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Поиск по предмету (STONE, DIAMOND...)"
                            class="pl-9"
                        />
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="showDisabledOnly"
                            type="checkbox"
                            class="rounded border-zinc-300 dark:border-zinc-600"
                        >
                        Только отключённые
                    </label>
                </div>

                <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-zinc-900 dark:text-white">
                                    Предмет
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-zinc-900 dark:text-white">
                                    Цена покупки
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-zinc-900 dark:text-white">
                                    Цена продажи
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-zinc-900 dark:text-white" :title="`Цена покупки + ${BUY_TAX_PERCENT}% налог`">
                                    Финальная цена
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-zinc-900 dark:text-white">
                                    Статус
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            <tr
                                v-for="item in filteredItems"
                                :key="item.material"
                                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30"
                            >
                                <td class="px-4 py-2 font-mono text-zinc-900 dark:text-white">
                                    {{ item.material }}
                                </td>
                                <td class="px-4 py-2">
                                    <Input
                                        :model-value="getBuyPrice(item.material)"
                                        type="number"
                                        step="0.01"
                                        min="-1"
                                        class="w-24 font-mono"
                                        placeholder="—"
                                        @update:model-value="(v: string | number) => updateItem(item.material, parsePrice(v))"
                                    />
                                </td>
                                <td class="px-4 py-2 font-mono tabular-nums text-zinc-600 dark:text-zinc-400">
                                    {{ getSellPrice(item.material) >= 0 ? getSellPrice(item.material).toFixed(2) : '—' }}
                                </td>
                                <td class="px-4 py-2 font-mono tabular-nums font-medium text-zinc-900 dark:text-white">
                                    {{ getFinalPrice(item.material) >= 0 ? getFinalPrice(item.material).toFixed(2) : '—' }}
                                </td>
                                <td class="px-4 py-2">
                                    <span
                                        :class="getItemBase(item.material) >= 0
                                            ? 'rounded bg-emerald-500/15 px-2 py-0.5 text-xs text-emerald-700 dark:text-emerald-400'
                                            : 'rounded bg-zinc-500/15 px-2 py-0.5 text-xs text-zinc-600 dark:text-zinc-400'"
                                    >
                                        {{ getItemBase(item.material) >= 0 ? 'Вкл' : 'Выкл' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p v-if="filteredItems.length === 0" class="py-8 text-center text-zinc-500">
                    {{ searchQuery || showDisabledOnly ? 'Ничего не найдено. Измените фильтры.' : 'Нет предметов.' }}
                </p>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Сохранение…' : 'Сохранить' }}
                    </Button>
                    <Link
                        href="/admin/shop"
                        class="inline-flex items-center rounded-lg border border-zinc-200 px-4 py-2 text-sm font-medium dark:border-zinc-700"
                    >
                        Отмена
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

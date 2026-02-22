<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Coins } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface PackageItem {
    id: number;
    coins: number;
    price: number;
    bonus_percent: number;
    total_coins: number;
    easydonate_product_id: number;
    is_active: boolean;
}

const page = usePage();
const flashStatus = computed(() => (page.props.flash as { status?: string })?.status);
const flashError = computed(() => (page.props.flash as { error?: string })?.error);

const props = defineProps<{
    packages: PackageItem[];
}>();

const form = useForm({
    packages: props.packages.map((p) => ({
        id: p.id,
        easydonate_product_id: p.easydonate_product_id || '',
    })),
});

function formatPrice(value: number): string {
    return value.toLocaleString('ru-RU') + ' ₽';
}
</script>

<template>
    <Head title="Пакеты монет — Админка | GrindZone" />

    <AdminLayout>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
            <h1 class="mb-6 text-2xl font-bold text-zinc-900 dark:text-white">
                Пакеты монет
            </h1>

            <div
                v-if="flashStatus"
                class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200"
            >
                {{ flashStatus }}
            </div>
            <div
                v-if="flashError"
                class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/50 dark:text-red-200"
            >
                {{ flashError }}
            </div>
            <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                Укажите ID товаров из EasyDonate для каждого пакета. Создайте товары в
                <a href="https://cp.easydonate.ru/" target="_blank" rel="noopener" class="text-amber-600 underline hover:no-underline dark:text-amber-400">панели EasyDonate</a>
                (раздел «Товары») с ценами 100, 450, 800, 1800, 3200 ₽, затем нажмите «Синхронизировать».
            </p>

            <form
                method="post"
                action="/admin/packages/sync-easydonate"
                class="mb-6"
            >
                <input type="hidden" name="_token" :value="page.props.csrf_token ?? ''" />
                <button
                    type="submit"
                    class="rounded-xl border border-amber-500 bg-amber-500/10 px-4 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-500/20 dark:border-amber-500 dark:text-amber-400 dark:hover:bg-amber-500/20"
                >
                    Синхронизировать с EasyDonate
                </button>
            </form>

            <form @submit.prevent="form.put('/admin/packages')" class="space-y-4">
                <div
                    v-for="(pkg, idx) in packages"
                    :key="pkg.id"
                    class="flex flex-wrap items-center gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-500">
                        <Coins class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-zinc-900 dark:text-white">
                            {{ pkg.total_coins.toLocaleString('ru-RU') }} монет
                        </div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ formatPrice(pkg.price) }}
                            <span v-if="pkg.bonus_percent > 0" class="text-emerald-600 dark:text-emerald-400">
                                (+{{ pkg.bonus_percent }}%)
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-zinc-600 dark:text-zinc-400">EasyDonate ID:</label>
                        <input
                            v-model="form.packages[idx].easydonate_product_id"
                            type="number"
                            min="0"
                            class="w-24 rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                        />
                    </div>
                </div>

                <div v-if="form.errors.packages" class="text-sm text-red-600 dark:text-red-400">
                    {{ form.errors.packages }}
                </div>

                <button
                    type="submit"
                    class="rounded-xl bg-amber-500 px-6 py-2.5 font-medium text-zinc-950 transition hover:bg-amber-400 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Сохранение…' : 'Сохранить' }}
                </button>
            </form>

            <p v-if="packages.length === 0" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                Пакеты не найдены. Запустите сидер: <code class="rounded bg-amber-200/50 px-1 dark:bg-amber-900/30">php artisan db:seed --class=BalanceTopupPackageSeeder</code>
            </p>
        </div>
    </AdminLayout>
</template>

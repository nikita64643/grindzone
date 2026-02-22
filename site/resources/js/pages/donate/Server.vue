<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft, Check, CreditCard, Gift, Tag, Wallet } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';
import donate from '@/routes/donate';

interface ServerItem {
    id: number;
    name: string;
    slug: string;
    version: string;
    port: number;
}

interface Privilege {
    key: string;
    name: string;
    description: string;
    price: number;
    features: string[];
}

const props = defineProps<{
    server: ServerItem;
    privileges: Privilege[];
}>();

const page = usePage();
const authUser = computed(() => page.props.auth?.user as { balance?: number } | null);
const flashStatus = computed(() => (page.props.flash as { status?: string })?.status);
const flashError = computed(() => (page.props.flash as { error?: string })?.error);
const balance = computed(() => Number(authUser.value?.balance ?? 0));
const csrfToken = computed(() => (page.props as { csrf_token?: string }).csrf_token ?? '');
const easydonateEnabled = computed(() => (page.props as { easydonate_enabled?: boolean }).easydonate_enabled ?? false);

const selectedKey = ref<string | null>(null);
const paymentChoiceOpen = ref(false);

const selectedPrivilege = computed(() =>
    props.privileges.find((p) => p.key === selectedKey.value) ?? null,
);

const form = useForm({
    server_slug: props.server.slug,
    server_name: props.server.name,
    privilege_key: '',
    coupon_code: '',
});

const couponCode = ref('');
const couponValidating = ref(false);
const couponResult = ref<{
    valid: boolean;
    final_price?: number;
    discount_text?: string | null;
    message?: string;
} | null>(null);

let validateTimeout: ReturnType<typeof setTimeout> | null = null;
watch([() => selectedKey.value, couponCode], () => {
    if (validateTimeout) clearTimeout(validateTimeout);
    validateTimeout = setTimeout(validatePrivilegeCoupon, 400);
});

async function validatePrivilegeCoupon() {
    const priv = selectedPrivilege.value;
    const code = couponCode.value.trim();
    if (!priv) {
        couponResult.value = null;
        return;
    }
    if (code === '') {
        couponResult.value = { valid: true };
        return;
    }
    couponValidating.value = true;
    couponResult.value = null;
    try {
        const { data } = await axios.post('/api/coupon/validate-privilege', {
            amount: priv.price,
            coupon_code: code,
        });
        couponResult.value = data;
    } catch {
        couponResult.value = { valid: false, message: 'Ошибка проверки' };
    } finally {
        couponValidating.value = false;
    }
}

const finalPrivilegePrice = computed(() => {
    const priv = selectedPrivilege.value;
    if (!priv) return 0;
    if (couponResult.value?.valid && couponResult.value.final_price !== undefined) {
        return couponResult.value.final_price;
    }
    return priv.price;
});

function openPaymentChoice(key: string) {
    selectedKey.value = key;
    form.privilege_key = key;
    couponCode.value = '';
    couponResult.value = null;
    paymentChoiceOpen.value = true;
}

function submitWalletPayment() {
    if (!selectedPrivilege.value || balance.value < finalPrivilegePrice.value) return;
    form.coupon_code = couponCode.value.trim();
    form.post(donate.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            paymentChoiceOpen.value = false;
            selectedKey.value = null;
        },
    });
}

function formatPrice(value: number): string {
    return value.toLocaleString('ru-RU') + ' ₽';
}

// Разворачиваем фичи с учётом «Всё из VIP» / «Всё из Premium»
const expandedPrivileges = computed(() => {
    const order = props.privileges;
    const expanded: Record<string, Set<string>> = {};
    for (const priv of order) {
        const set = new Set<string>();
        const features = priv.features ?? [];
        for (const f of features) {
            if (f.startsWith('Всё из VIP')) {
                const vip = order.find((p) => p.key === 'vip');
                vip?.features?.filter((x) => !x.startsWith('Всё из')).forEach((x) => set.add(x));
            } else if (f.startsWith('Всё из Premium')) {
                const prem = expanded['premium'];
                prem?.forEach((x) => set.add(x));
            } else {
                set.add(f);
            }
        }
        expanded[priv.key] = set;
    }
    return expanded;
});

const tableFeatures = computed(() => {
    const seen = new Set<string>();
    const list: string[] = [];
    for (const priv of props.privileges) {
        for (const f of expandedPrivileges.value[priv.key] ?? []) {
            if (!seen.has(f)) {
                seen.add(f);
                list.push(f);
            }
        }
    }
    return list;
});

function hasFeature(privKey: string, feature: string): boolean {
    return expandedPrivileges.value[privKey]?.has(feature) ?? false;
}
</script>

<template>
    <Head :title="`Донат — ${server.name} | GrindZone`" />

    <div class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <SiteHeader />

        <main class="mx-auto max-w-3xl px-4 py-8 sm:py-12">
            <Link
                :href="donate.index.url()"
                class="mb-6 inline-flex items-center gap-2 text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
            >
                <ArrowLeft class="h-4 w-4" />
                К выбору сервера
            </Link>

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

            <div class="mb-8 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-6">
                <h1 class="mb-1 text-xl font-bold text-zinc-900 dark:text-white sm:text-2xl">
                    {{ server.name }}
                </h1>
                <p class="text-sm text-amber-600 dark:text-amber-400">
                    {{ server.version }}
                </p>
            </div>

            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">
                Привилегии
            </h2>
            <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                Выберите привилегию и способ оплаты. Ниже — что входит в каждую привилегию.
            </p>
            <p
                v-if="server.slug.includes('1-21')"
                class="mb-6 rounded-lg border border-amber-200 bg-amber-50/80 px-3 py-2 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200"
            >
                Для серверов 1.21 укажите <strong>ник в Minecraft</strong> в
                <Link :href="'/settings/profile'" class="underline hover:no-underline">настройках профиля</Link> — по нему будет выдана привилегия в игре.
            </p>

            <!-- Таблица сравнения привилегий -->
            <div class="mb-8 overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/50">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[500px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="px-4 py-3 font-semibold text-zinc-900 dark:text-white">
                                    Возможность
                                </th>
                                <th
                                    v-for="priv in privileges"
                                    :key="priv.key"
                                    class="px-4 py-3 text-center font-semibold text-zinc-900 dark:text-white"
                                >
                                    {{ priv.name }}
                                </th>
                            </tr>
                            <tr class="border-b border-zinc-200 bg-zinc-50/50 dark:border-zinc-700 dark:bg-zinc-800/30">
                                <th class="px-4 py-3 text-left font-medium text-zinc-900 dark:text-white">
                                    Цена
                                </th>
                                <th
                                    v-for="priv in privileges"
                                    :key="priv.key"
                                    class="px-4 py-3 text-center"
                                >
                                    <span class="font-semibold text-amber-600 dark:text-amber-400">
                                        {{ formatPrice(priv.price) }}
                                    </span>
                                </th>
                            </tr>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="px-4 py-3 text-left font-medium text-zinc-900 dark:text-white">
                                    Действие
                                </th>
                                <th
                                    v-for="priv in privileges"
                                    :key="priv.key"
                                    class="px-4 py-3 text-center"
                                >
                                    <button
                                        type="button"
                                        :disabled="!authUser"
                                        class="rounded-xl bg-amber-500 px-4 py-2 font-medium text-zinc-950 shadow transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50"
                                        @click="openPaymentChoice(priv.key)"
                                    >
                                        Оплатить
                                    </button>
                                    <p
                                        v-if="!authUser"
                                        class="mt-1 text-xs text-zinc-500 dark:text-zinc-400"
                                    >
                                        <Link href="/login" class="text-amber-600 hover:underline dark:text-amber-400">Войдите</Link>
                                    </p>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="feature in tableFeatures"
                                :key="feature"
                                class="border-b border-zinc-100 dark:border-zinc-800"
                            >
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">
                                    {{ feature }}
                                </td>
                                <td
                                    v-for="priv in privileges"
                                    :key="priv.key"
                                    class="px-4 py-2.5 text-center"
                                >
                                    <span
                                        v-if="hasFeature(priv.key, feature)"
                                        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400"
                                        aria-label="Входит"
                                    >
                                        <Check class="h-3.5 w-3.5" />
                                    </span>
                                    <span
                                        v-else
                                        class="text-zinc-300 dark:text-zinc-600"
                                        aria-label="Не входит"
                                    >
                                        —
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Модалка выбора способа оплаты -->
            <Teleport to="body">
                <div
                    v-show="paymentChoiceOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="payment-choice-title"
                >
                    <div
                        class="fixed inset-0 bg-zinc-950/50 backdrop-blur-sm"
                        aria-hidden="true"
                        @click="paymentChoiceOpen = false"
                    />
                    <div
                        class="relative w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <h2 id="payment-choice-title" class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">
                            Способ оплаты
                        </h2>
                        <template v-if="selectedPrivilege">
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ server.name }} · {{ selectedPrivilege.name }}
                            </p>
                            <p class="mt-1 text-lg font-medium text-amber-600 dark:text-amber-400">
                                {{ formatPrice(finalPrivilegePrice) }}
                            </p>
                        </template>

                        <!-- Промокод -->
                        <div class="mt-4">
                            <label class="mb-1 block text-xs font-medium text-zinc-500 dark:text-zinc-400">Промокод</label>
                            <input
                                v-model="couponCode"
                                type="text"
                                placeholder="Введите промокод"
                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                            />
                            <p
                                v-if="couponResult?.valid && couponResult.discount_text"
                                class="mt-1 text-xs text-emerald-600 dark:text-emerald-400"
                            >
                                <Tag class="inline h-3 w-3" />
                                {{ couponResult.discount_text }}
                            </p>
                            <p
                                v-else-if="couponResult && !couponResult.valid && couponResult.message"
                                class="mt-1 text-xs text-red-600 dark:text-red-400"
                            >
                                {{ couponResult.message }}
                            </p>
                        </div>

                        <div class="mt-6 space-y-3">
                            <!-- Оплата с кошелька -->
                            <div
                                class="rounded-xl border-2 p-4 transition"
                                :class="balance >= (finalPrivilegePrice ?? 0)
                                    ? 'border-zinc-200 dark:border-zinc-700'
                                    : 'border-zinc-100 dark:border-zinc-800 opacity-75'"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/15">
                                        <Wallet class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-zinc-900 dark:text-white">С кошелька</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            На балансе: {{ formatPrice(balance) }}
                                        </p>
                                    </div>
                                    <button
                                        v-if="balance >= (finalPrivilegePrice ?? 0)"
                                        type="button"
                                        class="shrink-0 rounded-lg bg-amber-500 px-4 py-2 font-medium text-zinc-950 transition hover:bg-amber-400"
                                        :disabled="form.processing"
                                        @click="submitWalletPayment"
                                    >
                                        {{ form.processing ? 'Оплата…' : 'Подтвердить' }}
                                    </button>
                                    <div v-else class="shrink-0 text-sm text-zinc-500 dark:text-zinc-400">
                                        Недостаточно
                                    </div>
                                </div>
                            </div>

                            <!-- Оплата картой / СБП (Moneta) -->
                            <div class="rounded-xl border-2 border-zinc-200 p-4 dark:border-zinc-700">
                                <form
                                    method="post"
                                    action="/payment/create-privilege"
                                    class="contents"
                                    @submit="paymentChoiceOpen = false"
                                >
                                    <input type="hidden" name="_token" :value="csrfToken" />
                                    <input type="hidden" name="server_slug" :value="server.slug" />
                                    <input type="hidden" name="server_name" :value="server.name" />
                                    <input type="hidden" name="privilege_key" :value="selectedPrivilege?.key ?? ''" />
                                    <input type="hidden" name="privilege_name" :value="selectedPrivilege?.name ?? ''" />
                                    <input type="hidden" name="amount" :value="finalPrivilegePrice ?? 0" />
                                    <input type="hidden" name="coupon_code" :value="couponCode.trim()" />
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15">
                                            <CreditCard class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-zinc-900 dark:text-white">Картой / СБП</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                Moneta · СБП, карты, Qiwi
                                            </p>
                                        </div>
                                        <button
                                            type="submit"
                                            class="shrink-0 rounded-lg bg-emerald-600 px-4 py-2 font-medium text-white transition hover:bg-emerald-500"
                                        >
                                            Оплатить
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Оплата через EasyDonate -->
                            <div
                                v-if="easydonateEnabled"
                                class="rounded-xl border-2 border-zinc-200 p-4 dark:border-zinc-700"
                            >
                                <form
                                    method="post"
                                    action="/payment/create-privilege-easydonate"
                                    class="contents"
                                    @submit="paymentChoiceOpen = false"
                                >
                                    <input type="hidden" name="_token" :value="csrfToken" />
                                    <input type="hidden" name="server_slug" :value="server.slug" />
                                    <input type="hidden" name="server_name" :value="server.name" />
                                    <input type="hidden" name="privilege_key" :value="selectedPrivilege?.key ?? ''" />
                                    <input type="hidden" name="privilege_name" :value="selectedPrivilege?.name ?? ''" />
                                    <input type="hidden" name="coupon_code" :value="couponCode.trim()" />
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-violet-500/15">
                                            <Gift class="h-5 w-5 text-violet-600 dark:text-violet-400" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-zinc-900 dark:text-white">EasyDonate</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                Карты, СБП, Qiwi, ЮMoney
                                            </p>
                                        </div>
                                        <button
                                            type="submit"
                                            class="shrink-0 rounded-lg bg-violet-600 px-4 py-2 font-medium text-white transition hover:bg-violet-500"
                                        >
                                            Оплатить
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="mt-6 w-full rounded-xl border border-zinc-200 px-4 py-2.5 font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            @click="paymentChoiceOpen = false"
                        >
                            Отмена
                        </button>
                        <p v-if="form.errors.balance" class="mt-3 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.balance }}
                        </p>
                        <p v-if="form.errors.nickname" class="mt-3 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.nickname }}
                        </p>
                    </div>
                </div>
            </Teleport>
        </main>

        <SiteFooter />
    </div>
</template>

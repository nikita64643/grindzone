<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Check, Coins, ShoppingCart, Tag } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Dialog,
    DialogContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { getInitials } from '@/composables/useInitials';
import type { User } from '@/types';

interface BalanceTopupPackage {
    id: number;
    coins: number;
    price: number;
    bonus_percent: number;
    total_coins: number;
    easydonate_product_id?: number;
}

const props = withDefaults(
    defineProps<{
        open: boolean;
        user: User | null;
    }>(),
    { user: null },
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const selectedPackageId = ref<number | null>(null);
const selectedPaymentId = ref<string | null>('moneta');
const isSubmitting = ref(false);
const couponCode = ref('');
const couponValidating = ref(false);
const couponResult = ref<{
    valid: boolean;
    final_price?: number;
    final_coins?: number;
    discount_text?: string | null;
    message?: string;
} | null>(null);

const page = usePage();
const packages = computed<BalanceTopupPackage[]>(
    () => (page.props.balance_topup_packages as BalanceTopupPackage[]) ?? [],
);
const easydonateEnabled = computed(
    () => (page.props as { easydonate_enabled?: boolean }).easydonate_enabled ?? false,
);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            selectedPaymentId.value = 'moneta';
            selectedPackageId.value = packages.value[0]?.id ?? null;
            couponCode.value = '';
            couponResult.value = null;
        }
    },
);
watch(easydonateEnabled, (enabled) => {
    if (!enabled && selectedPaymentId.value === 'easydonate') {
        selectedPaymentId.value = 'moneta';
    }
}, { immediate: true });

let validateTimeout: ReturnType<typeof setTimeout> | null = null;
watch([selectedPackageId, couponCode], () => {
    if (validateTimeout) clearTimeout(validateTimeout);
    validateTimeout = setTimeout(validateCoupon, 400);
});

watch(packages, (pkg) => {
    if (pkg.length > 0 && !selectedPackageId.value) {
        selectedPackageId.value = pkg[0].id;
    }
}, { immediate: true });

const selectedPackage = computed(() =>
    packages.value.find((p) => p.id === selectedPackageId.value) ?? null,
);

const amountToPay = computed(() => {
    if (couponResult.value?.valid && couponResult.value.final_price !== undefined) {
        return couponResult.value.final_price;
    }
    return selectedPackage.value?.price ?? 0;
});

const displayCoins = computed(() => {
    if (couponResult.value?.valid && couponResult.value.final_coins !== undefined) {
        return couponResult.value.final_coins;
    }
    return selectedPackage.value?.total_coins ?? 0;
});

async function validateCoupon() {
    const pkgId = selectedPackageId.value;
    const code = couponCode.value.trim();
    if (!pkgId) {
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
        const { data } = await axios.post('/api/coupon/validate-balance', {
            package_id: pkgId,
            coupon_code: code,
        });
        couponResult.value = data;
    } catch {
        couponResult.value = { valid: false, message: 'Ошибка проверки' };
    } finally {
        couponValidating.value = false;
    }
}

function formatBalance(value: number | undefined): string {
    const n = Number(value) || 0;
    return n.toLocaleString('ru-RU') + ' руб.';
}

function formatCoins(value: number): string {
    return value.toLocaleString('ru-RU') + ' монет';
}

const isPremium = computed(() => {
    const s = (props.user?.status ?? '').toLowerCase();
    return s === 'premium' || s === 'премиум';
});

const paymentMethods = computed(() => {
    const list = [
        {
            id: 'moneta',
            name: 'Moneta',
            description: 'СБП, карты, Qiwi',
            logoClass: 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white px-2 py-1 text-xs font-bold rounded',
            recommended: true,
        },
        {
            id: 'easydonate',
            name: 'EasyDonate',
            description: 'Карты, СБП, Qiwi, ЮMoney',
            logoClass: 'bg-gradient-to-br from-violet-500 to-purple-600 text-white px-2 py-1 text-xs font-bold rounded',
        },
    ];
    return easydonateEnabled.value ? list : list.filter((m) => m.id !== 'easydonate');
});

const flashError = computed(() => (page.props.flash as { error?: string })?.error);

const paymentAction = computed(() =>
    selectedPaymentId.value === 'easydonate' ? '/payment/create-balance-easydonate' : '/payment/create',
);

const canSubmitEasyDonate = computed(() => {
    if (selectedPaymentId.value !== 'easydonate') return true;
    const pkg = selectedPackage.value;
    return pkg && (pkg.easydonate_product_id ?? 0) > 0;
});

function selectPayment(id: string) {
    selectedPaymentId.value = id;
}

function onSubmit() {
    isSubmitting.value = true;
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="max-w-md border-zinc-200 bg-white p-0 dark:border-zinc-800 dark:bg-zinc-900"
            :show-close-button="true"
        >
            <DialogTitle class="sr-only">Пополнение баланса</DialogTitle>
            <!-- Шапка: юзер слева, баланс справа -->
            <div class="flex items-start justify-between gap-4 border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <Avatar class="h-10 w-10 shrink-0 rounded-lg">
                        <AvatarImage v-if="user?.avatar" :src="user.avatar" :alt="user.name" />
                        <AvatarFallback class="rounded-lg bg-amber-500/20 text-sm font-medium text-amber-700 dark:bg-amber-500/30 dark:text-amber-400">
                            {{ user ? getInitials(user.name) : '' }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-zinc-900 dark:text-white">{{ user?.nickname || user?.name || '' }}</span>
                        <span
                            v-if="isPremium"
                            class="inline-flex w-fit items-center gap-1 rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-medium text-white"
                        >
                            <Check class="h-3 w-3 shrink-0" />
                            Премиум
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">Мой баланс:</span>
                    <div class="text-sm font-semibold tabular-nums text-zinc-900 dark:text-white">{{ formatBalance(user?.balance) }}</div>
                </div>
            </div>

            <div class="space-y-5 px-6 py-5">
                <div
                    v-if="flashError"
                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200"
                >
                    {{ flashError }}
                </div>

                <!-- Выбор пакета монет -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Выбери пакет монет
                    </label>
                    <p class="mb-3 text-xs text-zinc-500 dark:text-zinc-400">
                        Чем больше пакет — тем больше бонус
                    </p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <button
                            v-for="pkg in packages"
                            :key="pkg.id"
                            type="button"
                            class="flex flex-col items-start gap-1 rounded-xl border-2 p-3 text-left transition"
                            :class="selectedPackageId === pkg.id
                                ? 'border-amber-500 bg-amber-500/10 dark:border-amber-500 dark:bg-amber-500/20'
                                : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600'"
                            @click="selectedPackageId = pkg.id"
                        >
                            <div class="flex w-full items-center justify-between gap-2">
                                <span class="flex items-center gap-1.5 font-semibold text-zinc-900 dark:text-white">
                                    <Coins class="h-4 w-4 text-amber-500" />
                                    {{ formatCoins(pkg.total_coins) }}
                                </span>
                                <span
                                    v-if="pkg.bonus_percent > 0"
                                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400"
                                >
                                    +{{ pkg.bonus_percent }}%
                                </span>
                            </div>
                            <span class="text-sm font-medium text-amber-600 dark:text-amber-400">
                                {{ pkg.price.toLocaleString('ru-RU') }} ₽
                            </span>
                        </button>
                    </div>
                    <p
                        v-if="packages.length === 0"
                        class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200"
                    >
                        Пакеты не настроены. Обратитесь к администратору.
                    </p>
                </div>

                <!-- Промокод -->
                <div>
                    <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Промокод
                    </label>
                    <div class="flex gap-2">
                        <Input
                            v-model="couponCode"
                            type="text"
                            placeholder="Введите промокод"
                            class="flex-1"
                            :class="couponResult && !couponResult.valid ? 'border-red-300 dark:border-red-700' : ''"
                        />
                    </div>
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
                    <p
                        v-else-if="couponValidating"
                        class="mt-1 text-xs text-zinc-500 dark:text-zinc-400"
                    >
                        Проверка…
                    </p>
                </div>

                <!-- Способы оплаты -->
                <div>
                    <h3 class="mb-3 text-sm font-bold text-zinc-900 dark:text-white">
                        Способ оплаты (к оплате {{ amountToPay.toLocaleString('ru-RU') }} ₽ · {{ formatCoins(displayCoins) }})
                    </h3>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-for="m in paymentMethods"
                            :key="m.id"
                            type="button"
                            class="relative flex flex-col items-center justify-center gap-1 rounded-lg border-2 p-3 text-center transition"
                            :class="selectedPaymentId === m.id
                                ? 'border-amber-500 bg-amber-500/10 dark:border-amber-500 dark:bg-amber-500/20'
                                : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600'"
                            @click="selectPayment(m.id)"
                        >
                            <span v-if="m.recommended" class="absolute right-1 top-1 h-2 w-2 rounded-full bg-emerald-500" />
                            <span :class="m.logoClass">{{ m.name }}</span>
                            <span class="text-[10px] text-zinc-500 dark:text-zinc-400">{{ m.description }}</span>
                        </button>
                    </div>
                </div>

                <!-- Форма — редирект на Moneta или EasyDonate -->
                <form
                    method="post"
                    :action="paymentAction"
                    class="contents"
                    @submit="onSubmit"
                >
                    <input type="hidden" name="_token" :value="page.props.csrf_token ?? ''" />
                    <input type="hidden" name="package_id" :value="selectedPackageId ?? ''" />
                    <input type="hidden" name="coupon_code" :value="couponCode.trim()" />
                    <p
                    v-if="selectedPaymentId === 'easydonate' && selectedPackage && !canSubmitEasyDonate"
                    class="text-xs text-amber-600 dark:text-amber-400"
                >
                    Этот пакет не настроен для EasyDonate. Выберите Moneta или другой пакет.
                </p>
                    <button
                        type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-lg py-3 font-semibold text-white transition disabled:opacity-50"
                        :class="selectedPaymentId === 'easydonate'
                            ? 'bg-violet-600 hover:bg-violet-500 dark:bg-violet-600 dark:hover:bg-violet-500'
                            : 'bg-emerald-600 hover:bg-emerald-500 dark:bg-emerald-600 dark:hover:bg-emerald-500'"
                        :disabled="isSubmitting || !selectedPackageId || (selectedPaymentId === 'easydonate' && !canSubmitEasyDonate)"
                    >
                        <ShoppingCart class="h-5 w-5" />
                        {{ isSubmitting ? 'Перенаправление…' : 'Перейти к пополнению' }}
                    </button>
                </form>
            </div>
        </DialogContent>
    </Dialog>
</template>

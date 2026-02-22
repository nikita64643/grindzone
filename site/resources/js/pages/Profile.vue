<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Crown, Key, User } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import CabinetLayout from '@/layouts/CabinetLayout.vue';
import BalanceTopUpModal from '@/components/BalanceTopUpModal.vue';
import donate from '@/routes/donate';
import { edit as editProfile } from '@/routes/profile';
import { edit as editPassword } from '@/routes/user-password';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const balanceModalOpen = ref(false);
const openBalanceModal = computed(() => (page.props.flash as { open_balance_modal?: boolean })?.open_balance_modal);

watch(openBalanceModal, (open) => {
    if (open) balanceModalOpen.value = true;
}, { immediate: true });
</script>

<template>
    <Head title="Профиль — GrindZone" />

    <CabinetLayout>
        <template #balance>
            <button
                type="button"
                class="flex w-full flex-col items-start rounded-2xl border-2 border-amber-500/30 bg-amber-500/5 p-6 text-left transition hover:border-amber-500/50 hover:bg-amber-500/10 dark:border-amber-500/20 dark:bg-amber-500/10 dark:hover:border-amber-500/30 dark:hover:bg-amber-500/15"
                @click="balanceModalOpen = true"
            >
                <h4 class="text-lg font-bold text-zinc-900 dark:text-white">Пополнить баланс</h4>
                <span class="mt-2 block text-sm text-zinc-500 dark:text-zinc-400">Ваш баланс:</span>
                <span class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">
                    {{ (Number(user?.balance) || 0).toLocaleString('ru-RU') }} ₽
                </span>
            </button>
        </template>

        <div class="grid gap-8 lg:grid-cols-2">
            <div>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Внешний вид персонажа</h3>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Персонализируйте своего игрового персонажа. Загрузка скина и плаща будет доступна в следующих обновлениях.
                </p>
                <div class="mt-6 flex h-48 items-center justify-center rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700">
                    <div class="text-center">
                        <User class="mx-auto h-12 w-12 text-zinc-400" />
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Скоро здесь появится 3D-просмотр</p>
                    </div>
                </div>
            </div>
            <div>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Быстрые действия</h3>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Перейдите в нужный раздел для настройки аккаунта.
                </p>
                <div class="mt-6 space-y-3">
                    <Link
                        :href="donate.index()"
                        class="flex items-center gap-3 rounded-xl border border-zinc-200 p-4 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/50"
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 dark:bg-amber-500/20">
                            <Crown class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-zinc-900 dark:text-white">Привилегии</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">VIP, Premium, Legend</p>
                        </div>
                    </Link>
                    <Link
                        :href="editProfile()"
                        class="flex items-center gap-3 rounded-xl border border-zinc-200 p-4 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/50"
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            <User class="h-5 w-5 text-zinc-600 dark:text-zinc-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-zinc-900 dark:text-white">Настройки профиля</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Имя, почта</p>
                        </div>
                    </Link>
                    <Link
                        :href="editPassword()"
                        class="flex items-center gap-3 rounded-xl border border-zinc-200 p-4 transition hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800/50"
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                            <Key class="h-5 w-5 text-zinc-600 dark:text-zinc-400" />
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-zinc-900 dark:text-white">Безопасность</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Пароль, 2FA</p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </CabinetLayout>

    <BalanceTopUpModal v-if="user" v-model:open="balanceModalOpen" :user="user" />
</template>

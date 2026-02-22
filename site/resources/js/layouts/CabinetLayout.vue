<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Shield, User } from 'lucide-vue-next';
import { computed } from 'vue';
import SiteHeader from '@/components/SiteHeader.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import donate from '@/routes/donate';
import { edit as editProfile } from '@/routes/profile';
import { edit as editPassword } from '@/routes/user-password';
import { getInitials } from '@/composables/useInitials';
import { useCurrentUrl } from '@/composables/useCurrentUrl';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const { isCurrentUrl } = useCurrentUrl();

function formatBalance(value: number | undefined): string {
    const n = Number(value) || 0;
    return n.toLocaleString('ru-RU') + ' ₽';
}

function formatDate(dateStr: string | null | undefined): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    const day = d.getDate();
    const months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    const month = months[d.getMonth()];
    const year = d.getFullYear();
    const hours = d.getHours().toString().padStart(2, '0');
    const mins = d.getMinutes().toString().padStart(2, '0');
    return `${day} ${month} ${year} в ${hours}:${mins}`;
}

const isPremium = computed(() => {
    const s = (user.value?.status ?? '').toLowerCase();
    return s === 'premium' || s === 'премиум';
});

const cabinetTabs = [
    { title: 'Персонаж', href: '/profile' },
    { title: 'Привилегии', href: '/donate' },
    { title: 'Статистика', href: '/profile/stats' },
    { title: 'Настройки профиля', href: '/settings/profile' },
    { title: 'Безопасность', href: '/settings/password' },
    { title: '2FA', href: '/settings/two-factor' },
    { title: 'Оформление', href: '/settings/appearance' },
];
</script>

<template>
    <div class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <SiteHeader />

        <div class="mx-auto max-w-6xl px-4 py-8 sm:py-12">
            <!-- Headline -->
            <div class="mb-8 flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">
                        Привет, {{ user?.name ?? user?.nickname }}!
                    </h2>
                    <div class="mt-6 flex flex-col gap-6 sm:flex-row sm:items-start">
                        <Avatar class="h-24 w-24 shrink-0 rounded-2xl">
                            <AvatarImage v-if="user?.avatar" :src="user.avatar" :alt="user?.name" />
                            <AvatarFallback
                                class="rounded-2xl bg-amber-500/20 text-3xl font-semibold text-amber-700 dark:bg-amber-500/30 dark:text-amber-400"
                            >
                                {{ getInitials(user?.name ?? '') }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="min-w-0 flex-1">
                            <ul class="space-y-2 text-sm">
                                <li class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:gap-2">
                                    <span class="w-40 shrink-0 text-zinc-500 dark:text-zinc-400">Группа</span>
                                    <span class="font-medium text-zinc-900 dark:text-white">
                                        {{ isPremium ? 'Премиум' : (user?.status ?? 'Игрок') }}
                                    </span>
                                </li>
                                <li class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:gap-2">
                                    <span class="w-40 shrink-0 text-zinc-500 dark:text-zinc-400">Регистрация</span>
                                    <span class="text-zinc-700 dark:text-zinc-300">{{ formatDate(user?.created_at) }}</span>
                                </li>
                                <li class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:gap-2">
                                    <span class="w-40 shrink-0 text-zinc-500 dark:text-zinc-400">Почта</span>
                                    <span class="text-zinc-700 dark:text-zinc-300">{{ user?.email ?? '—' }}</span>
                                </li>
                            </ul>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <Link
                                    :href="editProfile()"
                                    class="inline-flex items-center rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                >
                                    Настройки
                                </Link>
                                <Link
                                    href="/shop"
                                    class="inline-flex items-center rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                >
                                    Магазин
                                </Link>
                                <Link
                                    :href="editPassword()"
                                    class="inline-flex items-center rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                >
                                    Сменить пароль
                                </Link>
                                <Link
                                    v-if="user?.is_admin"
                                    href="/admin"
                                    class="inline-flex items-center gap-2 rounded-lg border border-amber-500/50 bg-amber-500/10 px-3 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-500/20 dark:text-amber-400 dark:hover:bg-amber-500/20"
                                >
                                    <Shield class="h-4 w-4" />
                                    Админка
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="shrink-0 lg:w-72">
                    <slot name="balance">
                        <Link
                            href="/profile"
                            class="flex w-full flex-col items-start rounded-2xl border-2 border-amber-500/30 bg-amber-500/5 p-6 text-left transition hover:border-amber-500/50 hover:bg-amber-500/10 dark:border-amber-500/20 dark:bg-amber-500/10 dark:hover:border-amber-500/30 dark:hover:bg-amber-500/15"
                        >
                            <h4 class="text-lg font-bold text-zinc-900 dark:text-white">Пополнить баланс</h4>
                            <span class="mt-2 block text-sm text-zinc-500 dark:text-zinc-400">Ваш баланс:</span>
                            <span class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">
                                {{ formatBalance(user?.balance) }}
                            </span>
                        </Link>
                    </slot>
                </div>
            </div>

            <!-- Content card with integrated tabs -->
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
                <nav
                    class="flex gap-0 overflow-x-auto border-b border-zinc-200 bg-zinc-50/80 px-2 pt-2 dark:border-zinc-800 dark:bg-zinc-900/30"
                    aria-label="Разделы кабинета"
                >
                    <Link
                        v-for="tab in cabinetTabs"
                        :key="tab.href"
                        :href="tab.href"
                        :aria-current="isCurrentUrl(tab.href) ? 'page' : undefined"
                        :class="[
                            'relative shrink-0 px-5 py-3 text-sm font-medium transition',
                            isCurrentUrl(tab.href)
                                ? 'text-amber-600 dark:text-amber-400'
                                : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100'
                        ]"
                    >
                        {{ tab.title }}
                        <span
                            v-if="isCurrentUrl(tab.href)"
                            class="absolute bottom-0 left-0 right-0 h-0.5 bg-amber-500"
                        />
                    </Link>
                </nav>
                <div class="p-6 sm:p-8">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>

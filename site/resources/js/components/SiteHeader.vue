<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, ChevronDown, Crown, Menu, Monitor, Moon, Sun, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import profile from '@/routes/profile';
import { login, logout, register } from '@/routes';
import { edit } from '@/routes/profile';
import BalanceTopUpModal from '@/components/BalanceTopUpModal.vue';
import GrindzoneLogo from '@/components/GrindzoneLogo.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useAppearance } from '@/composables/useAppearance';
import { getInitials } from '@/composables/useInitials';

const { appearance, updateAppearance } = useAppearance();
const page = usePage();

const authUser = computed(() => page.props.auth?.user);
const canRegister = computed(() => Boolean(page.props.canRegister));

interface ServerWithStatus {
    name: string;
    slug: string | null;
    version: string;
    port: number;
    status: { online: boolean; players_online: number; players_max: number } | null;
}

interface ServerGroup {
    game: string;
    available: boolean;
    servers: ServerWithStatus[];
}

const headerServerGroups = computed<ServerGroup[]>(
    () => (page.props.headerServerGroups as ServerGroup[]) ?? [],
);

const mobileMenuOpen = ref(false);
const balanceModalOpen = ref(false);
const openBalanceModal = computed(() => (page.props.flash as { open_balance_modal?: boolean })?.open_balance_modal);
watch(openBalanceModal, (open) => {
    if (open) balanceModalOpen.value = true;
}, { immediate: true });
const serversMenuOpen = ref(false);
const helpMenuOpen = ref(false);
let serversMenuCloseTimeout: ReturnType<typeof setTimeout> | null = null;
let helpMenuCloseTimeout: ReturnType<typeof setTimeout> | null = null;
let serversMenuOpenedAt = 0;
let helpMenuOpenedAt = 0;
const SERVERS_MENU_CLOSE_DELAY = 300;
const SERVERS_MENU_OPEN_GUARD_MS = 250;

function openServersMenu() {
    if (serversMenuCloseTimeout) {
        clearTimeout(serversMenuCloseTimeout);
        serversMenuCloseTimeout = null;
    }
    serversMenuOpenedAt = Date.now();
    serversMenuOpen.value = true;
}
function closeServersMenu() {
    const now = Date.now();
    if (now - serversMenuOpenedAt < SERVERS_MENU_OPEN_GUARD_MS) return;
    serversMenuCloseTimeout = setTimeout(() => {
        serversMenuOpen.value = false;
        serversMenuCloseTimeout = null;
    }, SERVERS_MENU_CLOSE_DELAY);
}
function onServersMenuOpenChange(open: boolean) {
    if (open) {
        openServersMenu();
        return;
    }
    if (Date.now() - serversMenuOpenedAt < SERVERS_MENU_OPEN_GUARD_MS) return;
    if (serversMenuCloseTimeout) {
        clearTimeout(serversMenuCloseTimeout);
        serversMenuCloseTimeout = null;
    }
    serversMenuOpen.value = false;
}

function openHelpMenu() {
    if (helpMenuCloseTimeout) {
        clearTimeout(helpMenuCloseTimeout);
        helpMenuCloseTimeout = null;
    }
    helpMenuOpenedAt = Date.now();
    helpMenuOpen.value = true;
}
function closeHelpMenu() {
    const now = Date.now();
    if (now - helpMenuOpenedAt < SERVERS_MENU_OPEN_GUARD_MS) return;
    helpMenuCloseTimeout = setTimeout(() => {
        helpMenuOpen.value = false;
        helpMenuCloseTimeout = null;
    }, SERVERS_MENU_CLOSE_DELAY);
}
function onHelpMenuOpenChange(open: boolean) {
    if (open) {
        openHelpMenu();
        return;
    }
    if (Date.now() - helpMenuOpenedAt < SERVERS_MENU_OPEN_GUARD_MS) return;
    if (helpMenuCloseTimeout) {
        clearTimeout(helpMenuCloseTimeout);
        helpMenuCloseTimeout = null;
    }
    helpMenuOpen.value = false;
}

function formatBalance(value: number | undefined): string {
    const n = Number(value) || 0;
    return n.toLocaleString('ru-RU') + ' ₽';
}

const notificationsCount = computed(() => (page.props.auth as { notificationsCount?: number })?.notificationsCount ?? 0);
const isPremium = computed(() => {
    const s = (authUser.value?.status ?? '').toLowerCase();
    return s === 'premium' || s === 'премиум';
});

</script>

<template>
    <header
        class="sticky top-0 z-50 border-b bg-white/95 backdrop-blur-sm dark:border-zinc-800 dark:bg-[#0a0a0d]/95"
        style="padding-left: env(safe-area-inset-left); padding-right: env(safe-area-inset-right);"
    >
        <div class="mx-auto flex h-16 min-h-[4rem] max-w-7xl items-center justify-between gap-4 px-4">
            <Link href="/" class="shrink-0">
                <GrindzoneLogo class="scale-90 sm:scale-100" />
            </Link>

            <nav class="hidden items-center gap-6 text-sm md:flex">
                <DropdownMenu :modal="false" :open="serversMenuOpen" @update:open="onServersMenuOpenChange">
                    <DropdownMenuTrigger
                        as-child
                        @mouseenter="openServersMenu"
                        @mouseleave="closeServersMenu"
                    >
                        <button
                            type="button"
                            class="flex items-center gap-1 text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white"
                        >
                            Наши сервера
                            <ChevronDown class="h-4 w-4 shrink-0" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="start"
                        class="min-w-[220px] border-zinc-200 bg-white text-zinc-900 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
                        @mouseenter="openServersMenu"
                        @mouseleave="closeServersMenu"
                    >
                        <template v-for="(group, idx) in headerServerGroups" :key="group.game">
                            <DropdownMenuSeparator v-if="idx > 0" />
                            <DropdownMenuGroup>
                                <DropdownMenuLabel class="text-xs font-semibold text-orange-600 dark:text-orange-400">
                                    {{ group.game }}{{ group.available ? '' : ' — В разработке' }}
                                </DropdownMenuLabel>
                                <template v-if="group.available && group.servers.length > 0">
                                    <DropdownMenuItem v-for="server in group.servers" :key="server.slug ?? server.port" as-child class="focus:bg-zinc-100 focus:text-zinc-900 dark:focus:bg-zinc-800 dark:focus:text-white">
                                        <a :href="server.slug ? `/servers/${server.slug}` : '/servers'" class="cursor-pointer">{{ server.name }}</a>
                                    </DropdownMenuItem>
                                </template>
                            </DropdownMenuGroup>
                        </template>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem as-child class="focus:bg-zinc-100 focus:text-zinc-900 dark:focus:bg-zinc-800 dark:focus:text-white">
                            <a href="/servers" class="cursor-pointer font-medium text-orange-600 dark:text-orange-400">Все сервера →</a>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
                <Link href="/shop" class="text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">Магазин</Link>
                <DropdownMenu :modal="false" :open="helpMenuOpen" @update:open="onHelpMenuOpenChange">
                    <DropdownMenuTrigger
                        as-child
                        @mouseenter="openHelpMenu"
                        @mouseleave="closeHelpMenu"
                    >
                        <button
                            type="button"
                            class="flex items-center gap-1 text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white"
                        >
                            Помощь
                            <ChevronDown class="h-4 w-4 shrink-0" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="start"
                        class="min-w-[200px] border-zinc-200 bg-white text-zinc-900 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
                        @mouseenter="openHelpMenu"
                        @mouseleave="closeHelpMenu"
                    >
                        <DropdownMenuItem as-child class="focus:bg-zinc-100 focus:text-zinc-900 dark:focus:bg-zinc-800 dark:focus:text-white">
                            <a href="/rules" class="cursor-pointer">Правила</a>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child class="focus:bg-zinc-100 focus:text-zinc-900 dark:focus:bg-zinc-800 dark:focus:text-white">
                            <a href="/help" class="cursor-pointer">Вопросы и ответы</a>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem as-child class="focus:bg-zinc-100 focus:text-zinc-900 dark:focus:bg-zinc-800 dark:focus:text-white">
                            <a href="/requisites" class="cursor-pointer">Реквизиты</a>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child class="focus:bg-zinc-100 focus:text-zinc-900 dark:focus:bg-zinc-800 dark:focus:text-white">
                            <a href="/offer" class="cursor-pointer">Публичная оферта</a>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child class="focus:bg-zinc-100 focus:text-zinc-900 dark:focus:bg-zinc-800 dark:focus:text-white">
                            <a href="/privacy" class="cursor-pointer">Политика конфиденциальности</a>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
                <Link href="/donate" class="text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">Донат</Link>
            </nav>

            <div class="flex shrink-0 items-center gap-3">
                <div class="hidden rounded-lg border border-zinc-200 bg-zinc-100 p-0.5 dark:border-zinc-700 dark:bg-zinc-800/50 md:flex" role="group" aria-label="Тема">
                    <button type="button" title="Светлая" :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-md transition', appearance === 'light' ? 'bg-white text-orange-600 shadow dark:bg-zinc-700 dark:text-orange-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-white']" @click="updateAppearance('light')">
                        <Sun class="h-4 w-4" />
                    </button>
                    <button type="button" title="Тёмная" :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-md transition', appearance === 'dark' ? 'bg-white text-orange-600 shadow dark:bg-zinc-700 dark:text-orange-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-white']" @click="updateAppearance('dark')">
                        <Moon class="h-4 w-4" />
                    </button>
                    <button type="button" title="Как в системе" :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-md transition', appearance === 'system' ? 'bg-white text-orange-600 shadow dark:bg-zinc-700 dark:text-orange-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-white']" @click="updateAppearance('system')">
                        <Monitor class="h-4 w-4" />
                    </button>
                </div>
                <template v-if="authUser">
                    <Link href="/notifications" class="relative hidden md:flex" aria-label="Уведомления">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg text-zinc-500 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                            <Bell class="h-5 w-5" />
                        </span>
                        <span
                            v-if="notificationsCount > 0"
                            class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-orange-500 px-1 text-[10px] font-medium text-white"
                        >
                            {{ notificationsCount > 99 ? '99+' : notificationsCount }}
                        </span>
                        <span v-else class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-zinc-400 text-[10px] font-medium text-white dark:bg-zinc-600">0</span>
                    </Link>
                    <button type="button" class="hidden rounded-lg border border-zinc-200 bg-zinc-100 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-200 dark:border-zinc-600 dark:bg-zinc-800/50 dark:text-zinc-300 dark:hover:bg-zinc-700/50 md:block" @click="balanceModalOpen = true">
                        {{ formatBalance(authUser.balance) }}
                    </button>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button type="button" class="flex items-center gap-2 rounded-lg border border-zinc-200 py-1 pl-1 pr-2 transition hover:bg-zinc-100 dark:border-transparent dark:hover:bg-zinc-800" aria-label="Профиль">
                                <Avatar class="h-8 w-8 shrink-0 rounded-lg">
                                    <AvatarImage v-if="authUser.avatar" :src="authUser.avatar" :alt="authUser.name" />
                                    <AvatarFallback class="rounded-lg bg-orange-500/20 text-sm font-medium text-orange-600 dark:text-orange-400">{{ getInitials(authUser.name) }}</AvatarFallback>
                                </Avatar>
                                <span class="max-w-[100px] truncate text-sm font-medium text-zinc-900 dark:text-white">{{ authUser.nickname || authUser.name }}</span>
                                <ChevronDown class="h-4 w-4 shrink-0 text-zinc-500 dark:text-zinc-400" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56 border-zinc-200 bg-white text-zinc-900 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100">
                            <UserMenuContent :user="authUser" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>
                <template v-else>
                    <Link :href="login()" class="hidden rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800/50 dark:text-zinc-300 dark:hover:bg-zinc-700/50 md:block">Войти</Link>
                    <Link v-if="canRegister" :href="register()" class="hidden rounded-lg bg-orange-500 px-4 py-2 text-sm font-bold text-white shadow-[0_0_15px_rgba(249,115,22,0.3)] transition hover:bg-orange-600 md:block">Регистрация</Link>
                </template>
                <button type="button" class="flex h-9 w-9 items-center justify-center rounded-lg text-zinc-500 md:hidden dark:text-zinc-400" aria-label="Меню" @click="mobileMenuOpen = !mobileMenuOpen">
                    <Menu v-if="!mobileMenuOpen" class="h-5 w-5" />
                    <X v-else class="h-5 w-5" />
                </button>
            </div>
        </div>

        <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-show="mobileMenuOpen" class="absolute inset-x-0 top-full z-50 border-b border-zinc-200 bg-white py-4 dark:border-zinc-800 dark:bg-[#0a0a0d] md:hidden">
                <nav class="mx-auto flex max-w-7xl flex-col gap-1 px-4">
                    <div class="flex rounded-lg border border-zinc-200 bg-zinc-100 p-0.5 dark:border-zinc-700 dark:bg-zinc-800/50" role="group" aria-label="Тема">
                        <button type="button" title="Светлая" class="flex h-8 flex-1 items-center justify-center rounded transition" :class="appearance === 'light' ? 'bg-white text-orange-600 shadow dark:bg-zinc-700 dark:text-orange-400' : 'text-zinc-500 dark:text-zinc-400'" @click="updateAppearance('light')"><Sun class="h-4 w-4" /></button>
                        <button type="button" title="Тёмная" class="flex h-8 flex-1 items-center justify-center rounded transition" :class="appearance === 'dark' ? 'bg-white text-orange-600 shadow dark:bg-zinc-700 dark:text-orange-400' : 'text-zinc-500 dark:text-zinc-400'" @click="updateAppearance('dark')"><Moon class="h-4 w-4" /></button>
                        <button type="button" title="Система" class="flex h-8 flex-1 items-center justify-center rounded transition" :class="appearance === 'system' ? 'bg-white text-orange-600 shadow dark:bg-zinc-700 dark:text-orange-400' : 'text-zinc-500 dark:text-zinc-400'" @click="updateAppearance('system')"><Monitor class="h-4 w-4" /></button>
                    </div>
                    <div class="py-2">
                        <p class="px-4 pb-1.5 text-xs font-semibold text-orange-600 dark:text-orange-400">Наши сервера</p>
                        <template v-for="group in headerServerGroups" :key="group.game">
                            <p class="px-4 pt-2 pb-0.5 text-[11px] font-medium text-zinc-500 dark:text-zinc-400">
                                {{ group.game }}{{ group.available ? '' : ' — В разработке' }}
                            </p>
                            <template v-if="group.available && group.servers.length > 0">
                                <a
                                    v-for="server in group.servers"
                                    :key="server.slug ?? server.port"
                                    :href="server.slug ? `/servers/${server.slug}` : '/servers'"
                                    class="flex min-h-11 items-center rounded-lg px-6 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                    @click="mobileMenuOpen = false"
                                >
                                    {{ server.name }}
                                </a>
                            </template>
                        </template>
                        <a href="/servers" class="mt-1 flex min-h-11 items-center rounded-lg px-4 py-2 text-sm font-medium text-orange-600 dark:text-orange-400" @click="mobileMenuOpen = false">Все сервера →</a>
                    </div>
                    <Link href="/shop" class="flex min-h-12 items-center rounded-lg px-4 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" @click="mobileMenuOpen = false">Магазин</Link>
                    <div class="py-2">
                        <p class="px-4 pb-1.5 text-xs font-semibold text-orange-600 dark:text-orange-400">Помощь</p>
                        <a href="/rules" class="flex min-h-11 items-center rounded-lg px-6 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" @click="mobileMenuOpen = false">Правила</a>
                        <a href="/help" class="flex min-h-11 items-center rounded-lg px-6 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" @click="mobileMenuOpen = false">Вопросы и ответы</a>
                        <a href="/requisites" class="flex min-h-11 items-center rounded-lg px-6 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" @click="mobileMenuOpen = false">Реквизиты</a>
                        <a href="/offer" class="flex min-h-11 items-center rounded-lg px-6 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" @click="mobileMenuOpen = false">Публичная оферта</a>
                        <a href="/privacy" class="flex min-h-11 items-center rounded-lg px-6 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" @click="mobileMenuOpen = false">Политика конфиденциальности</a>
                    </div>
                    <Link href="/donate" class="flex min-h-12 items-center rounded-lg px-4 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" @click="mobileMenuOpen = false">Донат</Link>
                    <template v-if="authUser">
                        <div class="flex min-h-12 items-center gap-3 border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
                            <Link href="/notifications" class="flex h-9 w-9 items-center justify-center rounded-lg text-zinc-500 dark:text-zinc-400" aria-label="Уведомления">
                                <Bell class="h-5 w-5" />
                            </Link>
                            <button type="button" class="flex flex-col text-left" @click="balanceModalOpen = true; mobileMenuOpen = false">
                                <span class="text-[11px] text-zinc-500 dark:text-zinc-400">Баланс:</span>
                                <span class="text-sm font-medium tabular-nums text-zinc-700 dark:text-zinc-300">{{ formatBalance(authUser.balance) }}</span>
                            </button>
                        </div>
                        <Link :href="profile.index()" class="flex min-h-12 items-center rounded-lg px-4 py-2 font-medium text-orange-600 dark:text-orange-400" @click="mobileMenuOpen = false">Профиль</Link>
                        <Link :href="edit()" class="flex min-h-12 items-center rounded-lg px-4 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" @click="mobileMenuOpen = false">Настройки</Link>
                        <Link :href="logout()" class="flex min-h-12 items-center rounded-lg px-4 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" @click="mobileMenuOpen = false">Выйти</Link>
                    </template>
                    <template v-else>
                        <Link :href="login()" class="flex min-h-12 items-center rounded-lg px-4 py-2 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" @click="mobileMenuOpen = false">Войти</Link>
                        <Link v-if="canRegister" :href="register()" class="flex min-h-12 items-center rounded-lg px-4 py-2 font-medium text-orange-600 dark:text-orange-400" @click="mobileMenuOpen = false">Регистрация</Link>
                    </template>
                </nav>
            </div>
        </Transition>
    </header>

    <BalanceTopUpModal v-if="authUser" v-model:open="balanceModalOpen" :user="authUser" />
</template>

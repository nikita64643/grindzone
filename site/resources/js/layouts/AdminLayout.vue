<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { CreditCard, Coins, Crown, Server, Shield, ShoppingBag, Users } from 'lucide-vue-next';
import SiteHeader from '@/components/SiteHeader.vue';

const page = usePage();
const path = () => (page.url as string);
</script>

<template>
    <div class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <SiteHeader />

        <div class="mx-auto flex max-w-6xl gap-6 px-4 py-6">
            <aside class="w-48 shrink-0">
                <nav class="sticky top-20 rounded-xl border border-zinc-200 bg-white p-2 dark:border-zinc-800 dark:bg-zinc-900/50">
                    <div class="mb-2 flex items-center gap-2 px-2 py-1.5 text-xs font-semibold uppercase text-amber-700 dark:text-amber-400">
                        <Shield class="h-4 w-4" />
                        Админка
                    </div>
                    <Link
                        href="/admin/servers"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition"
                        :class="path().startsWith('/admin/servers')
                            ? 'bg-amber-500/15 font-medium text-amber-700 dark:bg-amber-500/20 dark:text-amber-400'
                            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    >
                        <Server class="h-4 w-4" />
                        Серверы
                    </Link>
                    <Link
                        href="/admin/users"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition"
                        :class="path().startsWith('/admin/users')
                            ? 'bg-amber-500/15 font-medium text-amber-700 dark:bg-amber-500/20 dark:text-amber-400'
                            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    >
                        <Users class="h-4 w-4" />
                        Пользователи
                    </Link>
                    <Link
                        href="/admin/privileges"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition"
                        :class="path().startsWith('/admin/privileges')
                            ? 'bg-amber-500/15 font-medium text-amber-700 dark:bg-amber-500/20 dark:text-amber-400'
                            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    >
                        <Crown class="h-4 w-4" />
                        Привилегии
                    </Link>
                    <Link
                        href="/admin/payments"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition"
                        :class="path().startsWith('/admin/payments')
                            ? 'bg-amber-500/15 font-medium text-amber-700 dark:bg-amber-500/20 dark:text-amber-400'
                            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    >
                        <CreditCard class="h-4 w-4" />
                        Оплаты
                    </Link>
                    <Link
                        href="/admin/packages"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition"
                        :class="path().startsWith('/admin/packages')
                            ? 'bg-amber-500/15 font-medium text-amber-700 dark:bg-amber-500/20 dark:text-amber-400'
                            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    >
                        <Coins class="h-4 w-4" />
                        Пакеты монет
                    </Link>
                    <Link
                        href="/admin/shop"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition"
                        :class="path().startsWith('/admin/shop')
                            ? 'bg-amber-500/15 font-medium text-amber-700 dark:bg-amber-500/20 dark:text-amber-400'
                            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    >
                        <ShoppingBag class="h-4 w-4" />
                        Магазин
                    </Link>
                </nav>
            </aside>
            <main class="min-w-0 flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>

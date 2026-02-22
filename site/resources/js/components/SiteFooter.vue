<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <footer
        class="border-t border-zinc-200 bg-zinc-50 py-8 dark:border-zinc-800 dark:bg-[#0a0a0d] sm:py-12"
        style="padding-bottom: calc(2rem + env(safe-area-inset-bottom));"
    >
        <div class="mx-auto max-w-7xl px-4">
            <div class="flex flex-col gap-8 md:flex-row md:items-start md:justify-between">
                <Link href="/" class="flex shrink-0 items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                    <AppLogoIcon class="h-6 w-6 text-amber-500" />
                    <span>GrindZone</span>
                </Link>
                <nav class="grid grid-cols-2 gap-8 sm:grid-cols-3" aria-label="Футер">
                    <div>
                        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Сайт</h3>
                        <ul class="space-y-2">
                            <li><Link href="/" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Главная</Link></li>
                            <li><Link href="/news" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Новости</Link></li>
                            <li><Link href="/tops" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Топы</Link></li>
                            <li><Link href="/shop" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Магазин</Link></li>
                            <li><Link href="/howtoplay" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Как начать</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Поддержка</h3>
                        <ul class="space-y-2">
                            <li><Link href="/rules" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Правила</Link></li>
                            <li><Link href="/help" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Помощь</Link></li>
                            <li><Link href="/donate" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Донат</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Документы</h3>
                        <ul class="space-y-2">
                            <li><Link href="/requisites" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Реквизиты</Link></li>
                            <li><Link href="/offer" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Публичная оферта</Link></li>
                            <li><Link href="/privacy" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">Политика</Link></li>
                        </ul>
                    </div>
                </nav>
            </div>

            <p class="mt-8 text-center text-xs text-zinc-500 dark:text-zinc-500">
                © {{ new Date().getFullYear() }} GrindZone — серверы Minecraft. Не аффилированы с Minecraft и Mojang AB.
            </p>
            <p class="mt-2 text-center text-xs text-zinc-500 dark:text-zinc-500">
                Тац Никита Владимирович · ИНН 662344873322
            </p>
        </div>
    </footer>
</template>

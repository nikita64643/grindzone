<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { KeyRound, Palette, ShieldCheck, User } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Профиль',
        href: editProfile(),
        icon: User,
    },
    {
        title: 'Безопасность',
        href: editPassword(),
        icon: KeyRound,
    },
    {
        title: 'Двухфакторная аутентификация',
        href: show(),
        icon: ShieldCheck,
    },
    {
        title: 'Оформление',
        href: editAppearance(),
        icon: Palette,
    },
];

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Настройки"
            description="Управление аккаунтом и профилем"
        />

        <div class="flex flex-col gap-8 lg:flex-row lg:gap-10">
            <aside class="w-full shrink-0 lg:w-56">
                <nav
                    class="flex flex-row gap-1 overflow-x-auto rounded-xl border border-zinc-200 bg-white p-1 dark:border-zinc-800 dark:bg-zinc-900/50 lg:flex-col lg:overflow-visible"
                    aria-label="Настройки"
                >
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        :aria-current="isCurrentUrl(item.href) ? 'page' : undefined"
                        :class="[
                            'flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition shrink-0',
                            isCurrentUrl(item.href)
                                ? 'bg-amber-500/15 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400'
                                : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'
                        ]"
                    >
                        <component
                            v-if="item.icon"
                            :is="item.icon"
                            class="h-4 w-4 shrink-0"
                        />
                        <span>{{ item.title }}</span>
                    </Link>
                </nav>
            </aside>

            <div class="min-w-0 flex-1">
                <section class="space-y-8">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>

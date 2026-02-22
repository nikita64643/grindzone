<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import auth from '@/routes/auth';
import { login, register } from '@/routes';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';
import GrindzoneLogo from '@/components/GrindzoneLogo.vue';
import { ChevronRight, Mail, Lock, Eye } from 'lucide-vue-next';

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));

const socialProviders = [
    { provider: 'vkontakte', name: 'VK', color: '#4C75A3' },
    { provider: 'discord', name: 'Discord', color: '#5865F2' },
    { provider: 'telegram', name: 'Telegram', color: '#26A5E4' },
    { provider: 'google', name: 'Google', color: '#4285F4' },
    { provider: 'max', name: 'Max', color: '#07C160' },
];

function socialRedirectUrl(provider: string): string {
    return auth.social.redirect.url(provider);
}

interface ServerStatus {
    online: boolean;
    players_online: number;
    players_max: number;
}

const props = withDefaults(
    defineProps<{
        canRegister?: boolean;
        servers: Array<{ name: string; version: string; port: number; slug?: string | null }>;
        initialServerStatus?: Record<string | number, ServerStatus>;
        news?: Array<{ title: string; slug?: string; date: string; image?: string }>;
        topPlaytime?: Array<{ rank: number; name: string; minutes: number }>;
    }>(),
    {
        canRegister: true,
        servers: () => [],
        initialServerStatus: () => ({}),
        news: () => [
            { title: 'Запуск проекта GRINDZONE', slug: 'zapusk-proekta', date: new Date().toLocaleDateString('ru-RU'), image: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=400' },
        ],
        topPlaytime: () => [],
    },
);

function normalizeInitialStatus(raw: Record<string | number, ServerStatus> | undefined): Record<number, ServerStatus> {
    if (!raw || typeof raw !== 'object') return {};
    const out: Record<number, ServerStatus> = {};
    for (const [key, val] of Object.entries(raw)) {
        const port = Number(key);
        if (!Number.isInteger(port) || !val) continue;
        out[port] = {
            online: Boolean(val.online),
            players_online: Number(val.players_online) || 0,
            players_max: Number(val.players_max) || 0,
        };
    }
    return out;
}

const statusByPort = ref<Record<number, ServerStatus>>(normalizeInitialStatus(props.initialServerStatus));
let pollInterval: ReturnType<typeof setInterval> | null = null;

async function fetchServerStatus() {
    try {
        const res = await fetch('/api/servers/status');
        if (!res.ok) return;
        const data = await res.json();
        const next: Record<number, ServerStatus> = {};
        for (const s of data.servers ?? []) {
            const port = Number(s.port);
            if (!Number.isInteger(port)) continue;
            next[port] = {
                online: Boolean(s.online),
                players_online: Number(s.players_online) || 0,
                players_max: Number(s.players_max) || 0,
            };
        }
        statusByPort.value = next;
    } catch {
        // ignore
    }
}

onMounted(() => {
    fetchServerStatus();
    pollInterval = setInterval(fetchServerStatus, 10_000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

const totalOnline = computed(() => {
    let sum = 0;
    for (const s of Object.values(statusByPort.value)) {
        if (s.online) sum += s.players_online;
    }
    return sum;
});

const serverCards = computed(() => {
    const minecraft = props.servers.find((s) => s.version?.includes('1.16') || s.version?.includes('1.21'));
    const onlineMap: Record<string, number> = {};
    for (const s of props.servers) {
        const st = statusByPort.value[s.port];
        onlineMap[s.name] = st?.online ? st.players_online : 0;
    }
    const minecraftOnline = minecraft ? (onlineMap[minecraft.name] ?? statusByPort.value[minecraft.port]?.players_online ?? 0) : 0;
    return [
        { name: 'Minecraft', online: minecraftOnline, color: 'green', href: '/howtoplay', available: true },
        { name: 'CS2', online: 0, color: 'orange', href: null, available: false },
        { name: 'CS1.6', online: 0, color: 'blue', href: null, available: false },
        { name: 'CS:Source', online: 0, color: 'indigo', href: null, available: false },
        { name: 'CSv34', online: 0, color: 'violet', href: null, available: false },
        { name: 'DayZ', online: 0, color: 'amber', href: null, available: false },
    ];
});

const showPassword = ref(false);

function formatPlaytime(min: number): string {
    if (min <= 0) return '0 мин';
    if (min < 60) return `${min} мин`;
    const h = Math.floor(min / 60);
    const m = min % 60;
    if (h < 24 && m === 0) return `${h} ч`;
    if (h < 24) return `${h} ч ${m} мин`;
    const d = Math.floor(h / 24);
    const rh = h % 24;
    if (rh === 0) return `${d} дн`;
    return `${d} дн ${rh} ч`;
}
</script>

<template>
    <Head title="GRINDZONE — Игровые серверы нового уровня" />

    <div class="min-h-screen overflow-x-hidden bg-zinc-50 text-zinc-900 dark:bg-[#0a0a0d] dark:text-white [padding:env(safe-area-inset-top)_env(safe-area-inset-right)_env(safe-area-inset-bottom)_env(safe-area-inset-left)]">
        <SiteHeader />

        <!-- Hero Section -->
        <section class="relative min-h-[85vh] overflow-hidden border-b border-zinc-200 dark:border-zinc-800">
            <!-- Background with fantasy gradient -->
            <div
                class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-90"
                style="background-image: linear-gradient(135deg, rgba(15,23,42,0.95) 0%, rgba(30,41,59,0.7) 30%, rgba(124,45,18,0.3) 70%, rgba(234,88,12,0.2) 100%), url('https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1920')"
            />
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-black/40 to-black/80" />
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_20%,rgba(249,115,22,0.15),transparent_50%)]" />

            <div class="relative z-10 flex min-h-[85vh] flex-col items-center justify-center px-4 py-20 text-center">
                <GrindzoneLogo large class="mb-4" />
                <p class="mb-8 text-lg text-white drop-shadow-md sm:text-xl md:text-2xl">ИГРОВЫЕ СЕРВЕРЫ НОВОГО УРОВНЯ</p>
                <Link
                    href="/howtoplay"
                    class="group inline-flex min-h-[3.5rem] items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-orange-500 via-orange-600 to-red-600 px-8 py-4 text-lg font-bold text-white shadow-[0_0_30px_rgba(249,115,22,0.5)] transition-all hover:shadow-[0_0_40px_rgba(249,115,22,0.7)]"
                >
                    НАЧАТЬ ИГРАТЬ
                    <ChevronRight class="h-5 w-5 transition group-hover:translate-x-1" />
                </Link>
            </div>
        </section>

        <!-- Our Servers -->
        <section id="servers" class="border-b border-zinc-200 bg-white py-16 dark:border-zinc-800 dark:bg-[#0f0f12]">
            <div class="mx-auto max-w-7xl px-4">
                <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-orange-500">◆</span>
                        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">Наши Серверы</h2>
                    </div>
                    <Link
                        href="/servers"
                        class="rounded-lg border border-zinc-300 bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-400 hover:bg-zinc-200 dark:border-zinc-600 dark:bg-zinc-800/50 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:bg-zinc-700/50"
                    >
                        Все сервера
                    </Link>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <component
                        :is="card.available && card.href ? 'a' : 'div'"
                        v-for="card in serverCards"
                        :key="card.name"
                        :href="card.available && card.href ? card.href : undefined"
                        class="group relative overflow-hidden rounded-xl border transition-all"
                        :class="[
                            card.available && card.href ? 'hover:scale-[1.02] cursor-pointer' : 'cursor-default opacity-80',
                            {
                                'border-emerald-500/50 bg-gradient-to-b from-emerald-100 to-emerald-200/80 dark:from-emerald-900/40 dark:to-zinc-900': card.color === 'green',
                                'border-orange-500/50 bg-gradient-to-b from-orange-100 to-orange-200/80 dark:from-orange-900/40 dark:to-zinc-900': card.color === 'orange',
                                'border-blue-500/50 bg-gradient-to-b from-blue-100 to-blue-200/80 dark:from-blue-900/40 dark:to-zinc-900': card.color === 'blue',
                                'border-indigo-500/50 bg-gradient-to-b from-indigo-100 to-indigo-200/80 dark:from-indigo-900/40 dark:to-zinc-900': card.color === 'indigo',
                                'border-violet-500/50 bg-gradient-to-b from-violet-100 to-violet-200/80 dark:from-violet-900/40 dark:to-zinc-900': card.color === 'violet',
                                'border-amber-500/50 bg-gradient-to-b from-amber-100 to-amber-200/80 dark:from-amber-900/40 dark:to-zinc-900': card.color === 'amber',
                            },
                        ]"
                    >
                        <div
                            class="absolute inset-0 opacity-30"
                            :class="{
                                'bg-[url(https://images.unsplash.com/photo-1579373903781-fd5c0c30c4cd?w=400)]': card.color === 'green',
                                'bg-[url(https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400)]': card.color === 'orange',
                                'bg-[url(https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400)]': card.color === 'blue',
                                'bg-[url(https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=400)]': card.color === 'indigo' || card.color === 'violet' || card.color === 'amber',
                            }"
                            style="background-size: cover; background-position: center"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent dark:from-black/90" />
                        <div class="relative p-6">
                            <h3 class="mb-1 text-xl font-bold text-white drop-shadow-sm">{{ card.name }}</h3>
                            <p class="mb-4 text-sm text-zinc-200">
                                {{ card.available ? `• ${card.online} Онлайн` : 'В разработке' }}
                            </p>
                            <span
                                class="inline-flex items-center gap-1 rounded-lg px-4 py-2 text-sm font-semibold transition"
                                :class="[
                                    card.available && card.href ? 'group-hover:brightness-110' : '',
                                    {
                                        'bg-emerald-500/80 shadow-[0_0_15px_rgba(34,197,94,0.4)]': card.color === 'green',
                                        'bg-orange-500/80 shadow-[0_0_15px_rgba(249,115,22,0.4)]': card.color === 'orange',
                                        'bg-blue-500/80 shadow-[0_0_15px_rgba(59,130,246,0.4)]': card.color === 'blue',
                                        'bg-indigo-500/80 shadow-[0_0_15px_rgba(99,102,241,0.4)]': card.color === 'indigo',
                                        'bg-violet-500/80 shadow-[0_0_15px_rgba(139,92,246,0.4)]': card.color === 'violet',
                                        'bg-amber-500/80 shadow-[0_0_15px_rgba(245,158,11,0.4)]': card.color === 'amber',
                                    },
                                ]"
                            >
                                {{ card.available && card.href ? 'Подключиться' : 'В разработке' }}
                                <ChevronRight v-if="card.available && card.href" class="h-4 w-4" />
                            </span>
                        </div>
                    </component>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="border-b border-zinc-200 bg-zinc-50 py-12 dark:border-zinc-800 dark:bg-[#0a0a0d]">
            <div class="mx-auto max-w-7xl px-4">
                <div class="grid grid-cols-2 gap-6 md:grid-cols-4">
                    <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
                        <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-orange-500/20 text-orange-600 dark:text-orange-500">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-orange-600 md:text-3xl dark:text-orange-400">{{ totalOnline.toLocaleString('ru-RU') }}+</div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">Игроков онлайн</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
                        <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-orange-500/20 text-orange-600 dark:text-orange-500">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-orange-600 md:text-3xl dark:text-orange-400">27</div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">Серверов</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
                        <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-orange-500/20 text-orange-600 dark:text-orange-500">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-orange-600 md:text-3xl dark:text-orange-400">1000+</div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">Уникальных режимов</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
                        <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-orange-500/20 text-orange-600 dark:text-orange-500">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-orange-600 md:text-3xl dark:text-orange-400">24/7</div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">Поддержка</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Three columns: News, Top Donators, Quick Login (только для гостей) -->
        <section class="border-b border-zinc-200 bg-white py-16 dark:border-zinc-800 dark:bg-[#0f0f12]">
            <div class="mx-auto max-w-7xl px-4">
                <div class="grid gap-8" :class="isAuthenticated ? 'lg:grid-cols-2' : 'lg:grid-cols-3'">
                    <!-- News -->
                    <div>
                        <div class="mb-6 flex items-center gap-2">
                            <span class="text-orange-500">◆</span>
                            <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Последние Новости</h2>
                        </div>
                        <div class="space-y-4">
                            <Link
                                v-for="(item, i) in props.news"
                                :key="i"
                                :href="item.slug ? `/news/${item.slug}` : '/news'"
                                class="group flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50 p-4 transition hover:border-orange-500/50 dark:border-zinc-700 dark:bg-zinc-900/50"
                            >
                                <div
                                    class="h-16 w-24 shrink-0 rounded-lg bg-gradient-to-br from-orange-400/50 to-zinc-300 dark:from-orange-600/50 dark:to-zinc-800"
                                    :style="{ backgroundImage: item.image ? `url(${item.image})` : undefined }"
                                    style="background-size: cover"
                                />
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-medium text-zinc-900 transition group-hover:text-orange-500 dark:text-white dark:group-hover:text-orange-400">{{ item.title }}</h3>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-500">{{ item.date }}</p>
                                </div>
                                <ChevronRight class="h-5 w-5 shrink-0 text-zinc-500 transition group-hover:translate-x-1 group-hover:text-orange-500" />
                            </Link>
                        </div>
                        <Link
                            href="/news"
                            class="mt-4 inline-flex items-center gap-1 rounded-lg bg-orange-500/20 px-4 py-2 text-sm font-medium text-orange-600 transition hover:bg-orange-500/30 dark:text-orange-400"
                        >
                            Все новости
                            <ChevronRight class="h-4 w-4" />
                        </Link>
                    </div>

                    <!-- Top Playtime -->
                    <div>
                        <div class="mb-6 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-orange-500">◆</span>
                                <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Топ по времени</h2>
                            </div>
                            <Link href="/tops#playtime" class="text-sm text-orange-600 hover:underline dark:text-orange-400">Все →</Link>
                        </div>
                        <div class="space-y-3">
                            <div
                                v-for="(row, i) in props.topPlaytime"
                                :key="row.name + row.rank"
                                class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900/50"
                            >
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 font-bold"
                                    :class="{
                                        'border-amber-400 bg-amber-500/20 text-amber-600 dark:text-amber-400': row.rank === 1,
                                        'border-zinc-400 bg-zinc-400/20 text-zinc-600 dark:bg-zinc-500/20 dark:text-zinc-300': row.rank === 2,
                                        'border-amber-700 bg-amber-700/20 text-amber-700 dark:bg-amber-800/20 dark:text-amber-600': row.rank === 3,
                                        'border-zinc-500 bg-zinc-400/20 text-zinc-500 dark:border-zinc-600 dark:bg-zinc-700/20 dark:text-zinc-400': row.rank > 3,
                                    }"
                                >
                                    {{ row.rank }}
                                </div>
                                <span class="min-w-0 flex-1 truncate font-medium text-zinc-900 dark:text-white">{{ row.name }}</span>
                                <span class="shrink-0 text-sm tabular-nums text-amber-600 dark:text-amber-400">{{ formatPlaytime(row.minutes) }}</span>
                            </div>
                            <div v-if="props.topPlaytime.length === 0" class="rounded-xl border border-zinc-200 bg-zinc-50 p-6 text-center text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-400">
                                Нет данных
                            </div>
                        </div>
                    </div>

                    <!-- Quick Login (только для гостей) -->
                    <div v-if="!isAuthenticated">
                        <h2 class="mb-6 text-xl font-bold text-zinc-900 dark:text-white">Быстрый Вход</h2>
                        <div class="grid grid-cols-2 gap-3">
                            <a
                                v-for="p in socialProviders"
                                :key="p.provider"
                                :href="socialRedirectUrl(p.provider)"
                                class="flex items-center justify-center gap-2 rounded-lg px-4 py-3 font-medium text-white transition"
                                :style="{ backgroundColor: p.color }"
                            >
                                {{ p.name }}
                            </a>
                        </div>
                        <form class="mt-6 space-y-4" @submit.prevent>
                            <div class="relative">
                                <Mail class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-500" />
                                <input
                                    type="email"
                                    placeholder="E-mail"
                                    class="w-full rounded-lg border border-zinc-300 bg-white py-3 pl-10 pr-4 text-zinc-900 placeholder-zinc-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-white dark:placeholder-zinc-500"
                                />
                            </div>
                            <div class="relative">
                                <Lock class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-500" />
                                <input
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Пароль"
                                    class="w-full rounded-lg border border-zinc-300 bg-white py-3 pl-10 pr-12 text-zinc-900 placeholder-zinc-500 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-white dark:placeholder-zinc-500"
                                />
                                <button
                                    type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"
                                    @click="showPassword = !showPassword"
                                >
                                    <Eye class="h-5 w-5" />
                                </button>
                            </div>
                            <Link
                                :href="login()"
                                class="block w-full rounded-lg bg-gradient-to-r from-orange-500 to-orange-600 py-3 text-center font-semibold text-white transition hover:from-orange-600 hover:to-orange-700"
                            >
                                Войти
                            </Link>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <SiteFooter />
    </div>
</template>

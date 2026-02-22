<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
    Clock,
    Crown,
    Coins,
    Skull,
    ThumbsUp,
    Trophy,
    ChevronDown,
    Server,
} from 'lucide-vue-next';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';

interface ServerOption {
    name: string;
    slug: string;
    version: string;
}

interface TopRow {
    rank: number;
    name: string;
    amount?: number;
    minutes?: number;
    kills?: number;
    votes?: number;
    silver?: number;
}

const props = defineProps<{
    servers: ServerOption[];
    currentServer: string | null;
    tops: {
        donators: TopRow[];
        playtime: TopRow[];
        mobKills: TopRow[];
        votes: TopRow[];
        silver: TopRow[];
    };
}>();

const tabIds = ['donators', 'playtime', 'mobKills', 'votes', 'silver'] as const;
type TabId = (typeof tabIds)[number];

function tabFromHash(): TabId {
    const hash = typeof window !== 'undefined' ? window.location.hash.slice(1) : '';
    return tabIds.includes(hash as TabId) ? (hash as TabId) : 'donators';
}

const activeTab = ref<TabId>(tabFromHash());

function selectTab(id: TabId) {
    activeTab.value = id;
    if (typeof window !== 'undefined') {
        window.history.replaceState(null, '', `#${id}`);
    }
}
const serverSelectOpen = ref(false);

const currentServerLabel = computed(() => {
    if (!props.currentServer) return 'Все серверы';
    const s = props.servers.find((x) => x.slug === props.currentServer);
    return s ? `${s.name} (${s.version})` : props.currentServer;
});

function selectServer(slug: string | null) {
    serverSelectOpen.value = false;
    router.get('/tops', slug ? { server: slug } : {}, { preserveState: true });
}

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

const tabs = [
    { id: 'donators' as const, label: 'Донатеры', icon: Crown },
    { id: 'playtime' as const, label: 'По времени', icon: Clock },
    { id: 'mobKills' as const, label: 'Убийства мобов', icon: Skull },
    { id: 'votes' as const, label: 'Голосовавшие', icon: ThumbsUp },
    { id: 'silver' as const, label: 'По серебру', icon: Coins },
];

const currentTop = computed(() => props.tops[activeTab.value]);
const serverSelectRef = ref<HTMLElement | null>(null);

function handleClickOutside(e: MouseEvent) {
    if (serverSelectRef.value && !serverSelectRef.value.contains(e.target as Node)) {
        serverSelectOpen.value = false;
    }
}

function syncTabFromHash() {
    activeTab.value = tabFromHash();
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    activeTab.value = tabFromHash();
    window.addEventListener('hashchange', syncTabFromHash);
});
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('hashchange', syncTabFromHash);
});
const hasSilverData = computed(() => props.currentServer && props.tops.silver.length > 0);
const silverNeedsServer = computed(() => !props.currentServer && activeTab.value === 'silver');
</script>

<template>
    <Head title="Топы — GrindZone" />

    <div class="min-h-screen overflow-x-hidden bg-zinc-50 text-zinc-900 dark:bg-[#0a0a0d] dark:text-white [padding:env(safe-area-inset-top)_env(safe-area-inset-right)_env(safe-area-inset-bottom)_env(safe-area-inset-left)]">
        <SiteHeader />

        <div class="mx-auto max-w-5xl px-4 py-8 sm:py-12">
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400">
                        <Trophy class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Топы</h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Лучшие игроки по серверам и режимам</p>
                    </div>
                </div>

                <div ref="serverSelectRef" class="relative">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-300 dark:hover:border-zinc-600"
                        @click="serverSelectOpen = !serverSelectOpen"
                        aria-haspopup="listbox"
                        :aria-expanded="serverSelectOpen"
                    >
                        <Server class="h-4 w-4" />
                        {{ currentServerLabel }}
                        <ChevronDown class="h-4 w-4 transition" :class="serverSelectOpen && 'rotate-180'" />
                    </button>
                    <div
                        v-show="serverSelectOpen"
                        class="absolute right-0 top-full z-10 mt-1 min-w-[200px] overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                    >
                        <button
                            type="button"
                            class="block w-full px-4 py-2.5 text-left text-sm transition hover:bg-zinc-100 dark:hover:bg-zinc-800"
                            :class="!currentServer ? 'font-medium text-orange-600 dark:text-orange-400' : 'text-zinc-700 dark:text-zinc-300'"
                            @click="selectServer(null)"
                        >
                            Все серверы
                        </button>
                        <button
                            v-for="s in servers"
                            :key="s.slug"
                            type="button"
                            class="block w-full px-4 py-2.5 text-left text-sm transition hover:bg-zinc-100 dark:hover:bg-zinc-800"
                            :class="currentServer === s.slug ? 'font-medium text-orange-600 dark:text-orange-400' : 'text-zinc-700 dark:text-zinc-300'"
                            @click="selectServer(s.slug)"
                        >
                            {{ s.name }} ({{ s.version }})
                        </button>
                    </div>
                </div>
            </div>

            <nav class="mb-6 flex flex-wrap gap-1 rounded-xl border border-zinc-200 bg-white p-1 dark:border-zinc-800 dark:bg-[#0f0f12]" aria-label="Вкладки топов">
                <a
                    v-for="t in tabs"
                    :key="t.id"
                    :href="`#${t.id}`"
                    :id="t.id"
                    class="flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition no-underline"
                    :class="activeTab === t.id ? 'bg-orange-500 text-white dark:bg-orange-500' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    @click.prevent="selectTab(t.id)"
                >
                    <component :is="t.icon" class="h-4 w-4" />
                    {{ t.label }}
                </a>
            </nav>

            <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-[#0f0f12]">
                <div v-if="silverNeedsServer" class="p-12 text-center">
                    <Coins class="mx-auto mb-4 h-12 w-12 text-zinc-400" />
                    <p class="text-zinc-600 dark:text-zinc-400">
                        Выберите сервер, чтобы увидеть топ по серебру (игровая валюта)
                    </p>
                </div>

                <div v-else-if="activeTab === 'donators' && currentTop.length === 0" class="p-12 text-center">
                    <Crown class="mx-auto mb-4 h-12 w-12 text-zinc-400" />
                    <p class="text-zinc-600 dark:text-zinc-400">Пока нет донатеров</p>
                </div>

                <div v-else-if="activeTab === 'playtime' && currentTop.length === 0" class="p-12 text-center">
                    <Clock class="mx-auto mb-4 h-12 w-12 text-zinc-400" />
                    <p class="text-zinc-600 dark:text-zinc-400">Нет данных об онлайне</p>
                </div>

                <div v-else-if="(activeTab === 'mobKills' || activeTab === 'votes') && currentTop.length === 0" class="p-12 text-center">
                    <component :is="activeTab === 'mobKills' ? Skull : ThumbsUp" class="mx-auto mb-4 h-12 w-12 text-zinc-400" />
                    <p class="text-zinc-600 dark:text-zinc-400">
                        {{ activeTab === 'mobKills' ? 'Нет данных об убийствах мобов' : 'Нет данных о голосованиях' }}
                    </p>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-500">
                        Данные синхронизируются с серверами
                    </p>
                </div>

                <div v-else-if="activeTab === 'silver' && currentTop.length === 0" class="p-12 text-center">
                    <Coins class="mx-auto mb-4 h-12 w-12 text-zinc-400" />
                    <p class="text-zinc-600 dark:text-zinc-400">Нет данных по серебру на выбранном сервере</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="px-6 py-4 text-left text-sm font-semibold text-zinc-500 dark:text-zinc-400">#</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-zinc-500 dark:text-zinc-400">Игрок</th>
                                <th class="px-6 py-4 text-right text-sm font-semibold text-zinc-500 dark:text-zinc-400">
                                    <template v-if="activeTab === 'donators'">Сумма (₽)</template>
                                    <template v-else-if="activeTab === 'playtime'">Время</template>
                                    <template v-else-if="activeTab === 'mobKills'">Убийств</template>
                                    <template v-else-if="activeTab === 'votes'">Голосов</template>
                                    <template v-else>Серебро</template>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in currentTop"
                                :key="row.rank"
                                class="border-b border-zinc-100 transition hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900/50"
                            >
                                <td class="px-6 py-3">
                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
                                        :class="{
                                            'bg-amber-500/20 text-amber-700 dark:text-amber-400': row.rank === 1,
                                            'bg-zinc-200/80 text-zinc-600 dark:bg-zinc-600 dark:text-zinc-300': row.rank === 2,
                                            'bg-amber-800/20 text-amber-800 dark:bg-amber-700/30 dark:text-amber-600': row.rank === 3,
                                            'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400': row.rank > 3,
                                        }"
                                    >
                                        {{ row.rank }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 font-medium text-zinc-900 dark:text-white">{{ row.name }}</td>
                                <td class="px-6 py-3 text-right tabular-nums">
                                    <template v-if="activeTab === 'donators' && row.amount != null">
                                        {{ row.amount.toLocaleString('ru-RU') }} ₽
                                    </template>
                                    <template v-else-if="activeTab === 'playtime' && row.minutes != null">
                                        {{ formatPlaytime(row.minutes) }}
                                    </template>
                                    <template v-else-if="activeTab === 'mobKills' && row.kills != null">
                                        {{ row.kills.toLocaleString('ru-RU') }}
                                    </template>
                                    <template v-else-if="activeTab === 'votes' && row.votes != null">
                                        {{ row.votes.toLocaleString('ru-RU') }}
                                    </template>
                                    <template v-else-if="activeTab === 'silver' && row.silver != null">
                                        {{ row.silver.toLocaleString('ru-RU') }}
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <SiteFooter />
    </div>
</template>

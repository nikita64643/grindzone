<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CabinetLayout from '@/layouts/CabinetLayout.vue';

const refreshing = ref(false);

function refreshStats() {
    refreshing.value = true;
    router.reload({ onFinish: () => { refreshing.value = false; } });
}

type DailyPlaytime = {
    date: string;
    minutes: number;
};

const props = withDefaults(
    defineProps<{
        totalPlaytimeMinutes?: number;
        last10DaysPlaytimeMinutes?: number | null;
        dailyPlaytime?: DailyPlaytime[];
    }>(),
    {
        totalPlaytimeMinutes: 0,
        last10DaysPlaytimeMinutes: null,
        dailyPlaytime: () => [],
    }
);

function formatPlaytime(minutes: number): string {
    if (minutes <= 0) return '0 минут';
    if (minutes < 60) return `${minutes} мин`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours === 1 && mins === 0) return '1 час';
    if (hours < 24 && mins === 0) return `${hours} ч`;
    if (hours < 24) return `${hours} ч ${mins} мин`;
    const days = Math.floor(hours / 24);
    const remHours = hours % 24;
    if (days === 1 && remHours === 0) return '1 день';
    if (remHours === 0) return `${days} дн`;
    return `${days} дн ${remHours} ч`;
}

const totalFormatted = computed(() => formatPlaytime(props.totalPlaytimeMinutes ?? 0));

const last10Formatted = computed(() => {
    const v = props.last10DaysPlaytimeMinutes;
    if (v == null || v <= 0) return 'Не заходил 😢';
    return formatPlaytime(v);
});

function getLast10Days(): { date: string; label: string; minutes: number }[] {
    const result: { date: string; label: string; minutes: number }[] = [];
    const byDate = new Map<string, number>();
    for (const d of props.dailyPlaytime ?? []) {
        byDate.set(d.date, d.minutes);
    }
    for (let i = 9; i >= 0; i--) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        const dateStr = d.toISOString().slice(0, 10);
        const label = `${d.getDate().toString().padStart(2, '0')}.${(d.getMonth() + 1).toString().padStart(2, '0')}`;
        result.push({
            date: dateStr,
            label,
            minutes: byDate.get(dateStr) ?? 0,
        });
    }
    return result;
}

const graphData = computed(() => getLast10Days());

const maxMinutes = computed(() => {
    const max = Math.max(...graphData.value.map((x) => x.minutes), 1);
    return max;
});

const hasAnyPlaytime = computed(
    () => (props.totalPlaytimeMinutes ?? 0) > 0 || (props.dailyPlaytime ?? []).some((d) => d.minutes > 0)
);
</script>

<template>
    <CabinetLayout>
        <Head title="Статистика — GrindZone" />

        <div class="space-y-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Статистика онлайна</h3>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Здесь ты можешь посмотреть свою статистику игры на серверах проекта. Данные обновляются по расписанию (каждые 30 мин).
                    </p>
                </div>
                <button
                    type="button"
                    :disabled="refreshing"
                    class="shrink-0 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700"
                    @click="refreshStats"
                >
                    {{ refreshing ? 'Обновление…' : 'Обновить' }}
                </button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-6 dark:border-zinc-700 dark:bg-zinc-800/30">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                        {{ totalFormatted }}
                    </div>
                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Проведено в игре за всё время
                    </div>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-6 dark:border-zinc-700 dark:bg-zinc-800/30">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">
                        {{ last10Formatted }}
                    </div>
                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Проведено в игре за 10 дней
                    </div>
                </div>
            </div>

            <div class="relative min-h-[200px] rounded-xl border border-zinc-200 bg-zinc-50/30 p-6 dark:border-zinc-700 dark:bg-zinc-800/20">
                <div
                    v-if="!hasAnyPlaytime"
                    class="absolute inset-0 flex flex-col items-center justify-center rounded-xl bg-zinc-50/80 text-zinc-500 dark:bg-zinc-900/50 dark:text-zinc-400"
                >
                    <span class="text-lg">Онлайн не найден 😢</span>
                </div>
                <div
                    class="flex h-40 items-end justify-between gap-1"
                    :class="{ 'opacity-40': !hasAnyPlaytime }"
                >
                    <div
                        v-for="col in graphData"
                        :key="col.date"
                        class="flex min-h-0 min-w-0 flex-1 flex-col items-center gap-2"
                        :title="`${col.label}: ${formatPlaytime(col.minutes)}`"
                    >
                        <div class="flex min-h-[80px] w-full flex-1 items-end justify-center">
                            <div
                                class="w-full max-w-8 shrink-0 rounded-t transition-all"
                                :style="{
                                    height: `${Math.max(col.minutes > 0 ? 8 : 2, (col.minutes / maxMinutes) * 100)}%`,
                                    minHeight: col.minutes > 0 ? '8px' : '2px',
                                    backgroundColor: 'rgb(245 158 11)',
                                }"
                            />
                        </div>
                        <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">{{ col.label }}</span>
                    </div>
                </div>
            </div>
        </div>
    </CabinetLayout>
</template>

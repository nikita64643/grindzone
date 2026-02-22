<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, RefreshCw } from 'lucide-vue-next';
import { computed, ref, onMounted, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const page = usePage();
const flashStatus = computed(() => (page.props.flash as { status?: string })?.status);
const flashError = computed(() => (page.props.flash as { error?: string })?.error);
const formErrors = computed(() => (page.props.errors as Record<string, string>) ?? {});

interface ServerData {
    id: number;
    name: string;
    slug: string;
    version: string;
    port: number;
    description: string | null;
    log_path: string;
    has_log: boolean;
    easydonate_server_id: number;
}

const props = defineProps<{
    server: ServerData;
}>();

const serverForm = useForm({
    easydonate_server_id: props.server.easydonate_server_id || '',
});

const logContent = ref('');
const logLoading = ref(false);
const restarting = ref(false);
const logLines = ref(500);

function loadLog() {
    if (!props.server.has_log) return;
    logLoading.value = true;
    fetch(`/admin/servers/${props.server.slug}/log?lines=${logLines.value}`)
        .then((r) => r.json())
        .then((data) => {
            logContent.value = data.content ?? data.error ?? '';
        })
        .finally(() => { logLoading.value = false; });
}

function restart() {
    if (restarting.value) return;
    if (!confirm(`Перезапустить сервер ${props.server.name} (порт ${props.server.port})?`)) return;
    restarting.value = true;
    router.post(`/admin/servers/${props.server.slug}/restart`, {}, {
        preserveScroll: true,
        onFinish: () => { restarting.value = false; },
    });
}

onMounted(() => loadLog());
watch(() => props.server.slug, () => loadLog());
</script>

<template>
    <Head :title="`${server.name} — Админка | GrindZone`" />

    <AdminLayout>
        <div class="space-y-6">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
                <Link
                    href="/admin/servers"
                    class="mb-4 inline-flex items-center gap-2 text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                >
                    <ArrowLeft class="h-4 w-4" />
                    К списку серверов
                </Link>

                <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ server.name }}
                        </h1>
                        <p class="text-sm text-amber-600 dark:text-amber-400">
                            {{ server.version }} · порт {{ server.port }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2 font-medium text-zinc-950 shadow transition hover:bg-amber-400 disabled:opacity-50"
                        :disabled="restarting"
                        @click="restart"
                    >
                        <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': restarting }" />
                        {{ restarting ? 'Перезапуск…' : 'Перезапустить сервер' }}
                    </button>
                </div>

                <p v-if="server.description" class="mb-6 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ server.description }}
                </p>

                <form
                    class="mb-6 flex flex-wrap items-end gap-4 rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30"
                    @submit.prevent="serverForm.put(`/admin/servers/${server.slug}`)"
                >
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">EasyDonate ID сервера</label>
                        <input
                            v-model.number="serverForm.easydonate_server_id"
                            type="number"
                            min="0"
                            class="w-28 rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                            placeholder="0"
                        />
                    </div>
                    <button
                        type="submit"
                        class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-zinc-950 transition hover:bg-amber-400 disabled:opacity-50"
                        :disabled="serverForm.processing"
                    >
                        {{ serverForm.processing ? '…' : 'Сохранить' }}
                    </button>
                </form>

                <div v-if="flashStatus" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200">
                    {{ flashStatus }}
                </div>
                <div v-if="flashError || formErrors.restart" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/50 dark:text-red-200">
                    {{ flashError || formErrors.restart }}
                </div>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700 sm:px-6">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">
                        Лог (latest.log)
                    </h2>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-zinc-500 dark:text-zinc-400">
                            Строк:
                            <select
                                v-model.number="logLines"
                                class="ml-1 rounded border border-zinc-300 bg-white px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200"
                                @change="loadLog"
                            >
                                <option :value="100">100</option>
                                <option :value="300">300</option>
                                <option :value="500">500</option>
                                <option :value="1000">1000</option>
                                <option :value="2000">2000</option>
                            </select>
                        </label>
                        <button
                            type="button"
                            class="rounded-lg border border-zinc-200 px-3 py-1.5 text-sm transition hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800"
                            :disabled="logLoading"
                            @click="loadLog"
                        >
                            {{ logLoading ? '…' : 'Обновить' }}
                        </button>
                    </div>
                </div>
                <div class="max-h-[60vh] overflow-auto p-4 sm:p-6">
                    <pre
                        v-if="server.has_log"
                        class="whitespace-pre-wrap font-mono text-xs text-zinc-700 dark:text-zinc-300"
                    >{{ logLoading ? 'Загрузка…' : logContent }}</pre>
                    <p v-else class="text-sm text-zinc-500 dark:text-zinc-400">
                        Файл лога не найден на диске. Убедитесь, что MINECRAFT_SERVERS_PATH в .env указывает на папку с серверами.
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

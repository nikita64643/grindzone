<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Crown, Pencil, User } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';

interface UserData {
    id: number;
    name: string;
    email: string;
    balance: number;
    status: string | null;
    is_admin: boolean;
    email_verified_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}

interface ServerOption {
    slug: string;
    name: string;
    version: string;
}

interface PrivilegeOption {
    key: string;
    name: string;
}

const props = defineProps<{
    user: UserData;
    donations_count: number;
    server_options: ServerOption[];
    version_options: string[];
    privileges_by_server: Record<string, PrivilegeOption[]>;
}>();

const page = usePage();
const flashStatus = (page.props.flash as { status?: string })?.status;
const grantError = computed(
    () => (page.props.errors as Record<string, string> | undefined)?.grant ?? grantForm.errors.grant ?? null
);

const grantForm = useForm({
    version: '',
    server_slug: '',
    privilege_key: '',
});

const serversForSelectedVersion = computed(() => {
    if (!grantForm.version) return [];
    return props.server_options.filter((s) => s.version === grantForm.version);
});

const privilegesForSelectedServer = computed(() => {
    if (!grantForm.server_slug) return [];
    return props.privileges_by_server[grantForm.server_slug] ?? [];
});

function onVersionChange() {
    grantForm.server_slug = '';
    grantForm.privilege_key = '';
}

function submitGrant() {
    grantForm.post(`/admin/users/${props.user.id}/grant-privilege`, {
        preserveScroll: true,
        onSuccess: () => {
            grantForm.reset('version', 'server_slug', 'privilege_key');
        },
    });
}
</script>

<template>
    <Head :title="`${user.name} — Пользователи | Админка`" />

    <AdminLayout>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
            <Link
                href="/admin/users"
                class="mb-6 inline-flex items-center gap-2 text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
            >
                <ArrowLeft class="h-4 w-4" />
                К списку пользователей
            </Link>
            <div
                v-if="flashStatus"
                class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200"
            >
                {{ flashStatus }}
            </div>
            <div
                v-if="grantError"
                class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/50 dark:text-red-200"
                role="alert"
            >
                {{ grantError }}
            </div>

            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-500">
                        <User class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ user.name }}
                        </h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ user.email }}
                        </p>
                    </div>
                </div>
                <Link
                    :href="`/admin/users/${user.id}/edit`"
                    class="inline-flex items-center gap-2 rounded-lg border border-amber-500/60 px-4 py-2 font-medium text-amber-600 transition hover:bg-amber-500/10 dark:text-amber-500"
                >
                    <Pencil class="h-4 w-4" />
                    Редактировать
                </Link>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Имя (ник в Minecraft)</dt>
                    <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ user.name || '—' }}</dd>
                </div>
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Баланс</dt>
                    <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ user.balance?.toLocaleString('ru-RU') }} ₽</dd>
                </div>
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Статус</dt>
                    <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ user.status || '—' }}</dd>
                </div>
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Админ</dt>
                    <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ user.is_admin ? 'Да' : 'Нет' }}</dd>
                </div>
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Донатов</dt>
                    <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ donations_count }}</dd>
                </div>
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 sm:col-span-2">
                    <dt class="text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">Зарегистрирован</dt>
                    <dd class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ user.created_at ? new Date(user.created_at).toLocaleString('ru-RU') : '—' }}</dd>
                </div>
            </dl>

            <section class="mt-8 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-900 dark:text-white">
                    <Crown class="h-5 w-5 text-amber-500" />
                    Выдать привилегию
                </h2>
                <p class="mb-4 text-sm text-zinc-500 dark:text-zinc-400">
                    Выберите сервер и привилегию. На каждом сервере свой набор привилегий. Выдача запишется как донат с суммой 0 и при необходимости синхронизируется с LuckPerms (1.21).
                </p>
                <form class="flex flex-wrap items-end gap-4" @submit.prevent="submitGrant">
                    <div class="min-w-[200px] space-y-2">
                        <Label for="grant-version">Версия Minecraft</Label>
                        <select
                            id="grant-version"
                            v-model="grantForm.version"
                            required
                            class="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                            @change="onVersionChange"
                        >
                            <option value="">Выберите версию</option>
                            <option
                                v-for="v in version_options"
                                :key="v"
                                :value="v"
                            >
                                {{ v }}
                            </option>
                        </select>
                    </div>
                    <div class="min-w-[200px] space-y-2">
                        <Label for="grant-server">Сервер</Label>
                        <select
                            id="grant-server"
                            v-model="grantForm.server_slug"
                            required
                            class="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white disabled:opacity-50"
                            :disabled="!grantForm.version || serversForSelectedVersion.length === 0"
                            @change="grantForm.privilege_key = ''"
                        >
                            <option value="">Выберите сервер</option>
                            <option
                                v-for="s in serversForSelectedVersion"
                                :key="s.slug"
                                :value="s.slug"
                            >
                                {{ s.name }}
                            </option>
                        </select>
                        <p v-if="grantForm.version && serversForSelectedVersion.length === 0" class="text-xs text-amber-600 dark:text-amber-400">
                            Для этой версии серверы не найдены.
                        </p>
                    </div>
                    <div class="min-w-[200px] space-y-2">
                        <Label for="grant-privilege">Привилегия</Label>
                        <select
                            id="grant-privilege"
                            v-model="grantForm.privilege_key"
                            required
                            class="flex h-9 w-full rounded-md border border-zinc-200 bg-white px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white disabled:opacity-50"
                            :disabled="!grantForm.server_slug || privilegesForSelectedServer.length === 0"
                        >
                            <option value="">Выберите привилегию</option>
                            <option
                                v-for="p in privilegesForSelectedServer"
                                :key="p.key"
                                :value="p.key"
                            >
                                {{ p.name }}
                            </option>
                        </select>
                        <p v-if="grantForm.server_slug && privilegesForSelectedServer.length === 0" class="text-xs text-amber-600 dark:text-amber-400">
                            Для этого сервера привилегии не настроены (привяжите в разделе «Привилегии»).
                        </p>
                    </div>
                    <Button type="submit" :disabled="grantForm.processing || !grantForm.version || !grantForm.server_slug || !grantForm.privilege_key">
                        {{ grantForm.processing ? 'Выдача…' : 'Выдать' }}
                    </Button>
                    <InputError v-if="grantForm.errors.privilege_key" :message="grantForm.errors.privilege_key" />
                    <InputError v-if="grantForm.errors.server_slug" :message="grantForm.errors.server_slug" />
                </form>
            </section>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { User } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface UserRow {
    id: number;
    name: string;
    email: string;
    balance: number;
    status: string | null;
    is_admin: boolean;
    created_at: string | null;
}

defineProps<{
    users: UserRow[];
}>();
</script>

<template>
    <Head title="Пользователи — Админка | GrindZone" />

    <AdminLayout>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
            <h1 class="mb-6 text-2xl font-bold text-zinc-900 dark:text-white">
                Пользователи
            </h1>
            <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                Список пользователей. Открой для просмотра и редактирования.
            </p>

            <div v-if="users.length === 0" class="rounded-xl border border-zinc-200 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-800/50">
                <p class="text-zinc-600 dark:text-zinc-400">
                    Пользователей нет.
                </p>
            </div>

            <div v-else class="space-y-3">
                <Link
                    v-for="u in users"
                    :key="u.id"
                    :href="`/admin/users/${u.id}`"
                    class="flex items-center gap-4 rounded-xl border border-zinc-200 p-4 transition hover:border-amber-500/40 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-amber-500/30 dark:hover:bg-zinc-800/50"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-500">
                        <User class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-zinc-900 dark:text-white">
                            {{ u.name }}
                        </div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ u.email }} · баланс {{ u.balance?.toLocaleString('ru-RU') }} ₽
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span
                            v-if="u.is_admin"
                            class="rounded bg-amber-500/20 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-400"
                        >
                            Админ
                        </span>
                        <span class="text-zinc-400 dark:text-zinc-500">→</span>
                    </div>
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>

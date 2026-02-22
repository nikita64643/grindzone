<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

interface UserData {
    id: number;
    name: string;
    email: string;
    balance: number;
    status: string | null;
    is_admin: boolean;
}

const props = defineProps<{
    user: UserData;
}>();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    balance: props.user.balance,
    status: props.user.status ?? '',
    is_admin: props.user.is_admin,
});
</script>

<template>
    <Head :title="`Редактировать ${props.user.name} | Админка`" />

    <AdminLayout>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
            <Link
                :href="`/admin/users/${props.user.id}`"
                class="mb-6 inline-flex items-center gap-2 text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
            >
                <ArrowLeft class="h-4 w-4" />
                Назад к пользователю
            </Link>

            <h1 class="mb-6 text-2xl font-bold text-zinc-900 dark:text-white">
                Редактировать пользователя
            </h1>

            <form
                class="space-y-6"
                @submit.prevent="form.put(`/admin/users/${props.user.id}`)"
            >
                <div class="space-y-2">
                    <Label for="name">Имя (ник в Minecraft)</Label>
                    <Input id="name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="space-y-2">
                    <Label for="email">Эл. почта</Label>
                    <Input id="email" v-model="form.email" type="email" required />
                    <InputError :message="form.errors.email" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="balance">Баланс (₽)</Label>
                        <Input id="balance" v-model.number="form.balance" type="number" step="0.01" min="0" />
                        <InputError :message="form.errors.balance" />
                    </div>
                    <div class="space-y-2">
                        <Label for="status">Статус</Label>
                        <Input id="status" v-model="form.status" />
                        <InputError :message="form.errors.status" />
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        id="is_admin"
                        v-model="form.is_admin"
                        type="checkbox"
                        class="h-4 w-4 rounded border-zinc-300 text-amber-500 focus:ring-amber-500 dark:border-zinc-600"
                    />
                    <Label for="is_admin" class="cursor-pointer">Администратор</Label>
                </div>
                <InputError v-if="form.errors.is_admin" :message="form.errors.is_admin" />
                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Сохранение…' : 'Сохранить' }}
                    </Button>
                    <Link :href="`/admin/users/${props.user.id}`" class="inline-flex items-center rounded-lg border border-zinc-200 px-4 py-2 text-sm font-medium dark:border-zinc-700">
                        Отмена
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        title="Восстановление пароля"
        description="Введите email — мы отправим ссылку для сброса пароля"
    >
        <Head title="Восстановление пароля" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-emerald-600 dark:text-emerald-400"
        >
            {{ status }}
        </div>

        <div class="space-y-6">
            <Form v-bind="email.form()" v-slot="{ errors, processing }">
                <div class="grid gap-2">
                    <Label for="email" class="text-zinc-700 dark:text-zinc-300">Эл. почта</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="off"
                        autofocus
                        placeholder="example@mail.ru"
                        class="border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="my-6 flex items-center justify-start">
                    <Button
                        class="w-full min-h-[2.75rem] bg-amber-500 font-medium text-zinc-950 hover:bg-amber-400 dark:bg-amber-500 dark:text-zinc-950 dark:hover:bg-amber-400"
                        :disabled="processing"
                        data-test="email-password-reset-link-button"
                    >
                        <Spinner v-if="processing" />
                        Отправить ссылку для сброса пароля
                    </Button>
                </div>
            </Form>

            <div class="space-x-1 text-center text-sm text-zinc-500 dark:text-zinc-400">
                <span>Вернуться к</span>
                <TextLink :href="login()" class="font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">входу</TextLink>
            </div>
        </div>
    </AuthLayout>
</template>

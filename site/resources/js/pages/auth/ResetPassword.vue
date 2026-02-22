<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { update } from '@/routes/password';

const props = defineProps<{
    token: string;
    email: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <AuthLayout
        title="Новый пароль"
        description="Введите новый пароль ниже"
    >
        <Head title="Сброс пароля" />

        <Form
            v-bind="update.form()"
            :transform="(data) => ({ ...data, token, email })"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email" class="text-zinc-700 dark:text-zinc-300">Эл. почта</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        v-model="inputEmail"
                        class="mt-1 block w-full border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-400"
                        readonly
                    />
                    <InputError :message="errors.email" class="mt-2" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" class="text-zinc-700 dark:text-zinc-300">Новый пароль</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        class="mt-1 block w-full border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        autofocus
                        placeholder="Пароль"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation" class="text-zinc-700 dark:text-zinc-300">Подтверждение пароля</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        class="mt-1 block w-full border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                        placeholder="Повторите пароль"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full min-h-[2.75rem] bg-amber-500 font-medium text-zinc-950 hover:bg-amber-400 dark:bg-amber-500 dark:text-zinc-950 dark:hover:bg-amber-400"
                    :disabled="processing"
                    data-test="reset-password-button"
                >
                    <Spinner v-if="processing" />
                    Сохранить пароль
                </Button>
            </div>
        </Form>
    </AuthLayout>
</template>

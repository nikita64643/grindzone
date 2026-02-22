<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
</script>

<template>
    <AuthBase
        title="Регистрация"
        description="Заполните данные для создания аккаунта"
    >
        <Head title="Регистрация" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name" class="text-zinc-700 dark:text-zinc-300">Имя</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Как к вам обращаться"
                        class="border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email" class="text-zinc-700 dark:text-zinc-300">Эл. почта</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="example@mail.ru"
                        class="border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" class="text-zinc-700 dark:text-zinc-300">Пароль</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Пароль"
                        class="border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation" class="text-zinc-700 dark:text-zinc-300">Подтверждение пароля</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Повторите пароль"
                        class="border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full min-h-[2.75rem] bg-amber-500 font-medium text-zinc-950 hover:bg-amber-400 dark:bg-amber-500 dark:text-zinc-950 dark:hover:bg-amber-400"
                    tabindex="5"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    Создать аккаунт
                </Button>
            </div>

            <div class="text-center text-sm text-zinc-500 dark:text-zinc-400">
                Уже есть аккаунт?
                <TextLink :href="login()" class="font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400" :tabindex="6">Войти</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>

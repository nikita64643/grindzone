<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import auth from '@/routes/auth';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    error?: string;
    canResetPassword: boolean;
    canRegister: boolean;
    socialProviders?: Array<{ provider: string; name: string; color: string }>;
}>();

function socialRedirectUrl(provider: string): string {
    return auth.social.redirect.url(provider);
}
</script>

<template>
    <AuthBase
        title="Вход в аккаунт"
        description="Введите email и пароль для входа"
    >
        <Head title="Вход" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-emerald-600 dark:text-emerald-400"
        >
            {{ status }}
        </div>
        <div
            v-if="error"
            class="mb-4 text-center text-sm font-medium text-red-600 dark:text-red-400"
        >
            {{ error }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email" class="text-zinc-700 dark:text-zinc-300">Эл. почта</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="example@mail.ru"
                        class="border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="text-zinc-700 dark:text-zinc-300">Пароль</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm text-amber-600 hover:text-amber-500 dark:text-amber-400 dark:hover:text-amber-300"
                            :tabindex="5"
                        >
                            Забыли пароль?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Пароль"
                        class="border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-100"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex cursor-pointer items-center gap-3 text-zinc-700 dark:text-zinc-300">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>Запомнить меня</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full min-h-[2.75rem] bg-amber-500 font-medium text-zinc-950 hover:bg-amber-400 dark:bg-amber-500 dark:text-zinc-950 dark:hover:bg-amber-400"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    Войти
                </Button>

                <div v-if="socialProviders?.length" class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <span class="w-full border-t border-zinc-200 dark:border-zinc-700" />
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-white px-2 text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">Или войти через</span>
                    </div>
                </div>
                <div v-if="socialProviders?.length" class="grid grid-cols-2 gap-2">
                    <a
                        v-for="p in socialProviders"
                        :key="p.provider"
                        :href="socialRedirectUrl(p.provider)"
                        class="flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition"
                        :style="{ backgroundColor: p.color }"
                    >
                        {{ p.name }}
                    </a>
                </div>
            </div>

            <div
                v-if="canRegister"
                class="text-center text-sm text-zinc-500 dark:text-zinc-400"
            >
                Нет аккаунта?
                <TextLink :href="register()" class="font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400" :tabindex="5">Регистрация</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>

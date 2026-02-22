<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CabinetLayout from '@/layouts/CabinetLayout.vue';
</script>

<template>
    <CabinetLayout>
        <Head title="Безопасность — GrindZone" />

        <div class="max-w-2xl space-y-6">
            <div>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Смена пароля</h3>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Используйте надёжный пароль для защиты аккаунта
                </p>
            </div>

            <Form
                v-bind="PasswordController.update.form()"
                :options="{ preserveScroll: true }"
                reset-on-success
                :reset-on-error="['password', 'password_confirmation', 'current_password']"
                class="space-y-6"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <div class="grid gap-2">
                    <Label for="current_password">Текущий пароль</Label>
                    <Input
                        id="current_password"
                        name="current_password"
                        type="password"
                        class="mt-1 block w-full"
                        autocomplete="current-password"
                        placeholder="Текущий пароль"
                    />
                    <InputError :message="errors.current_password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Новый пароль</Label>
                    <Input
                        id="password"
                        name="password"
                        type="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        placeholder="Новый пароль"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Подтвердите пароль</Label>
                    <Input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        placeholder="Подтвердите пароль"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing"
                        data-test="update-password-button"
                        class="rounded-lg border-0 bg-amber-500 px-4 py-2 text-sm font-medium text-zinc-950 shadow-sm transition hover:bg-amber-400 dark:bg-amber-500 dark:text-zinc-950 dark:hover:bg-amber-400"
                    >
                        Сохранить пароль
                    </Button>
                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-show="recentlySuccessful"
                            class="text-sm text-zinc-600 dark:text-zinc-400"
                        >
                            Сохранено.
                        </p>
                    </Transition>
                </div>
            </Form>
        </div>
    </CabinetLayout>
</template>

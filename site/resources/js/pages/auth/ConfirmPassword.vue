<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CabinetLayout from '@/layouts/CabinetLayout.vue';
import { store } from '@/routes/password/confirm';
</script>

<template>
    <CabinetLayout>
        <Head title="Подтверждение пароля — GrindZone" />

        <div class="max-w-2xl space-y-6">
            <div>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Подтвердите пароль</h3>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Это защищённая страница. Введите пароль для продолжения.
                </p>
            </div>

            <Form
                v-bind="store.form()"
                reset-on-success
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="password">Пароль</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="current-password"
                        autofocus
                        placeholder="Пароль"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing"
                        data-test="confirm-password-button"
                        class="rounded-lg border-0 bg-amber-500 px-4 py-2 text-sm font-medium text-zinc-950 shadow-sm transition hover:bg-amber-400 dark:bg-amber-500 dark:text-zinc-950 dark:hover:bg-amber-400"
                    >
                        Подтвердить пароль
                    </Button>
                </div>
            </Form>
        </div>
    </CabinetLayout>
</template>

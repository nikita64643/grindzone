<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="space-y-6">
        <div>
            <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Удаление аккаунта</h3>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Удалите аккаунт и все связанные с ним данные
            </p>
        </div>
        <div
            class="space-y-4 rounded-xl border border-red-200/60 bg-red-50/80 p-4 dark:border-red-500/20 dark:bg-red-950/30"
        >
            <div class="space-y-0.5 text-red-700 dark:text-red-400">
                <p class="font-medium">Внимание</p>
                <p class="text-sm">
                    Это действие необратимо. Все данные аккаунта будут удалены безвозвратно.
                </p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        class="rounded-lg border border-red-500/50 bg-red-500/10 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-500/20 dark:border-red-500/30 dark:bg-red-500/20 dark:text-red-400 dark:hover:bg-red-500/30"
                        data-test="delete-user-button"
                    >
                        Удалить аккаунт
                    </Button>
                </DialogTrigger>
                <DialogContent
                    class="border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.$el?.focus()"
                        :options="{ preserveScroll: true }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle class="text-zinc-900 dark:text-white">
                                Вы уверены, что хотите удалить аккаунт?
                            </DialogTitle>
                            <DialogDescription class="text-zinc-600 dark:text-zinc-400">
                                После удаления все данные аккаунта будут безвозвратно удалены. Введите пароль для подтверждения.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">Пароль</Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                ref="passwordInput"
                                placeholder="Пароль"
                                class="border-zinc-200 dark:border-zinc-700"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    class="rounded-lg border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                    variant="ghost"
                                    @click="() => { clearErrors(); reset(); }"
                                >
                                    Отмена
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                class="rounded-lg border border-red-500/50 bg-red-500/10 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-500/20 dark:border-red-500/30 dark:bg-red-500/20 dark:text-red-400 dark:hover:bg-red-500/30"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                Удалить аккаунт
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>

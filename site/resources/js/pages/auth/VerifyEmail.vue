<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        title="Подтверждение email"
        description="Перейдите по ссылке из письма, которое мы отправили вам при регистрации."
    >
        <Head title="Подтверждение email" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-emerald-600 dark:text-emerald-400"
        >
            Новая ссылка для подтверждения отправлена на указанный при регистрации email.
        </div>

        <Form
            v-bind="send.form()"
            class="space-y-6 text-center"
            v-slot="{ processing }"
        >
            <Button
                :disabled="processing"
                class="min-h-[2.75rem] w-full bg-amber-500 font-medium text-zinc-950 hover:bg-amber-400 dark:bg-amber-500 dark:text-zinc-950 dark:hover:bg-amber-400"
            >
                <Spinner v-if="processing" />
                Отправить письмо повторно
            </Button>

            <TextLink
                :href="logout()"
                as="button"
                class="mx-auto block text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
            >
                Выйти
            </TextLink>
        </Form>
    </AuthLayout>
</template>

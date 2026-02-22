<script setup lang="ts">
import { Form, Head, Link, usePage, router } from '@inertiajs/vue3';
import { Link2 } from 'lucide-vue-next';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CabinetLayout from '@/layouts/CabinetLayout.vue';
import auth from '@/routes/auth';
import { send } from '@/routes/verification';

interface SocialAccount {
    provider: string;
    name: string;
    color: string;
    linked: boolean;
    display_name: string | null;
}

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
    socialAccounts?: SocialAccount[];
    socialError?: string;
};

defineProps<Props>();

const page = usePage();
const user = page.props.auth.user;

function linkUrl(provider: string): string {
    return auth.social.link.url(provider);
}

function unlinkUrl(provider: string): string {
    return auth.social.unlink.url(provider);
}

function unlink(provider: string) {
    router.delete(unlinkUrl(provider));
}
</script>

<template>
    <CabinetLayout>
        <Head title="Настройки профиля — GrindZone" />

        <div class="flex max-w-2xl flex-col space-y-8">
            <div>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Данные профиля</h3>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Обновите имя (ник в Minecraft) и эл. почту
                </p>
            </div>

            <Form
                v-bind="ProfileController.update.form()"
                class="space-y-6"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <div class="grid gap-2">
                    <Label for="name">Имя (ник в Minecraft)</Label>
                    <Input
                        id="name"
                        class="mt-1 block w-full"
                        name="name"
                        :default-value="user.name"
                        required
                        autocomplete="name"
                        placeholder="Ник для входа на серверах"
                    />
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Используется для выдачи привилегий (VIP/Premium/Legend) после оплаты на серверах 1.21.
                    </p>
                    <InputError class="mt-2" :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Эл. почта</Label>
                    <Input
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        name="email"
                        :default-value="user.email"
                        required
                        autocomplete="username"
                        placeholder="example@mail.ru"
                    />
                    <InputError class="mt-2" :message="errors.email" />
                </div>

                <div v-if="mustVerifyEmail && !user.email_verified_at">
                    <p class="-mt-4 text-sm text-zinc-500 dark:text-zinc-400">
                        Эл. почта не подтверждена.
                        <Link
                            :href="send()"
                            as="button"
                            class="text-amber-600 underline underline-offset-4 hover:text-amber-500 dark:text-amber-400"
                        >
                            Отправить письмо повторно
                        </Link>
                    </p>
                    <div
                        v-if="status === 'verification-link-sent'"
                        class="mt-2 text-sm font-medium text-emerald-600 dark:text-emerald-400"
                    >
                        Ссылка для подтверждения отправлена на вашу эл. почту.
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing"
                        data-test="update-profile-button"
                        class="rounded-lg border-0 bg-amber-500 px-4 py-2 text-sm font-medium text-zinc-950 shadow-sm transition hover:bg-amber-400 dark:bg-amber-500 dark:text-zinc-950 dark:hover:bg-amber-400"
                    >
                        Сохранить
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

            <div>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Привязка соц. сетей</h3>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Привяжите аккаунты VK, Discord, Telegram, Google или Max — после привязки вы сможете входить через них.
                </p>
            </div>
            <div v-if="status && status !== 'verification-link-sent'" class="rounded-lg bg-emerald-50 p-4 text-sm font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                {{ status }}
            </div>
            <div v-if="socialError" class="rounded-lg bg-red-50 p-4 text-sm font-medium text-red-800 dark:bg-red-900/30 dark:text-red-400">
                {{ socialError }}
            </div>
            <div class="space-y-4">
                <div
                    v-for="account in socialAccounts ?? []"
                    :key="account.provider"
                    class="flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900/50"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-white"
                            :style="{ backgroundColor: account.color }"
                        >
                            {{ account.name.charAt(0) }}
                        </div>
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ account.name }}</p>
                            <p v-if="account.linked && account.display_name" class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ account.display_name }}
                            </p>
                            <p v-else-if="account.linked" class="text-sm text-zinc-500 dark:text-zinc-400">
                                Привязан
                            </p>
                            <p v-else class="text-sm text-zinc-500 dark:text-zinc-400">
                                Не привязан
                            </p>
                        </div>
                    </div>
                    <div>
                        <a
                            v-if="!account.linked"
                            :href="linkUrl(account.provider)"
                            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white transition"
                            :style="{ backgroundColor: account.color }"
                        >
                            <Link2 class="h-4 w-4" />
                            Привязать
                        </a>
                        <button
                            v-else
                            type="button"
                            class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            @click="unlink(account.provider)"
                        >
                            Отвязать
                        </button>
                    </div>
                </div>
            </div>

            <DeleteUser />
        </div>
    </CabinetLayout>
</template>

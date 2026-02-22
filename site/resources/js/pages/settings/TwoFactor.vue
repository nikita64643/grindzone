<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ShieldBan, ShieldCheck } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import CabinetLayout from '@/layouts/CabinetLayout.vue';
import { disable, enable } from '@/routes/two-factor';

type Props = {
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => {
    clearTwoFactorAuthData();
});
</script>

<template>
    <CabinetLayout>
        <Head title="Двухфакторная аутентификация — GrindZone" />

        <div class="max-w-2xl space-y-6">
            <div>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Двухфакторная аутентификация</h3>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Настройка 2FA для дополнительной защиты аккаунта
                </p>
            </div>

            <div
                v-if="!twoFactorEnabled"
                class="flex flex-col items-start justify-start space-y-4"
            >
                <Badge variant="destructive">Выключена</Badge>
                <p class="text-zinc-600 dark:text-zinc-400">
                    При включении 2FA при входе потребуется код из приложения TOTP (Google Authenticator, Authy и др.).
                </p>
                <div>
                    <Button
                        v-if="hasSetupData"
                        @click="showSetupModal = true"
                        class="rounded-lg border-0 bg-amber-500 px-4 py-2 text-sm font-medium text-zinc-950 shadow-sm transition hover:bg-amber-400 dark:bg-amber-500 dark:text-zinc-950 dark:hover:bg-amber-400"
                    >
                        <ShieldCheck class="mr-2 h-4 w-4" />
                        Продолжить настройку
                    </Button>
                    <Form
                        v-else
                        v-bind="enable.form()"
                        @success="showSetupModal = true"
                        #default="{ processing }"
                    >
                        <Button
                            type="submit"
                            :disabled="processing"
                            class="rounded-lg border-0 bg-amber-500 px-4 py-2 text-sm font-medium text-zinc-950 shadow-sm transition hover:bg-amber-400 dark:bg-amber-500 dark:text-zinc-950 dark:hover:bg-amber-400"
                        >
                            <ShieldCheck class="mr-2 h-4 w-4" />
                            Включить 2FA
                        </Button>
                    </Form>
                </div>
            </div>

            <div
                v-else
                class="flex flex-col items-start justify-start space-y-4"
            >
                <Badge variant="default">Включена</Badge>
                <p class="text-zinc-600 dark:text-zinc-400">
                    Двухфакторная аутентификация активна. Код запрашивается при входе.
                </p>
                <TwoFactorRecoveryCodes />
                <div class="relative inline">
                    <Form v-bind="disable.form()" #default="{ processing }">
                        <Button
                            type="submit"
                            :disabled="processing"
                            class="rounded-lg border border-red-500/50 bg-red-500/10 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-500/20 dark:border-red-500/30 dark:bg-red-500/20 dark:text-red-400 dark:hover:bg-red-500/30"
                        >
                            <ShieldBan class="mr-2 h-4 w-4" />
                            Отключить 2FA
                        </Button>
                    </Form>
                </div>
            </div>

            <TwoFactorSetupModal
                v-model:isOpen="showSetupModal"
                :requiresConfirmation="requiresConfirmation"
                :twoFactorEnabled="twoFactorEnabled"
            />
        </div>
    </CabinetLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import CabinetLayout from '@/layouts/CabinetLayout.vue';
</script>

<template>
    <CabinetLayout>
        <Head title="Оформление — GrindZone" />

        <div class="max-w-2xl space-y-6">
            <div>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Оформление</h3>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Тема и настройки отображения
                </p>
            </div>
            <AppearanceTabs />
        </div>
    </CabinetLayout>
</template>

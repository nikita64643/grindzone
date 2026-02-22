<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineOptions({
    inheritAttrs: false,
});

withDefaults(
    defineProps<{
        className?: HTMLAttributes['class'];
        large?: boolean;
    }>(),
    { large: false },
);
</script>

<template>
    <div class="relative inline-flex items-center gap-2" :class="$attrs.class">
        <!-- Glow effect behind logo -->
        <div
            class="absolute -inset-2 rounded-lg opacity-60 blur-xl"
            :class="large ? 'bg-orange-500/40' : 'bg-orange-500/30'"
        />
        <AppLogoIcon
            :class="[
                large ? 'h-14 w-14 sm:h-16 sm:w-16' : 'h-7 w-7 sm:h-8 sm:w-8',
                'relative z-10 text-orange-500 drop-shadow-[0_0_12px_rgba(249,115,22,0.7)]',
            ]"
        />
        <span
            class="relative z-10 font-bold tracking-wide text-zinc-900 drop-shadow-[0_0_8px_rgba(249,115,22,0.4)] dark:text-white dark:drop-shadow-[0_0_8px_rgba(249,115,22,0.6)]"
            :class="large ? 'text-3xl sm:text-4xl md:text-5xl lg:text-6xl' : 'text-lg sm:text-xl md:text-2xl'"
        >
            GRINDZONE
        </span>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Newspaper } from 'lucide-vue-next';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';

const props = defineProps<{
    news: {
        id: number;
        title: string;
        slug: string;
        body: string;
        image: string | null;
        date: string | null;
    };
}>();
</script>

<template>
    <Head :title="`${news.title} — Новости | GrindZone`" />

    <div class="min-h-screen overflow-x-hidden bg-zinc-50 text-zinc-900 dark:bg-[#0a0a0d] dark:text-white [padding:env(safe-area-inset-top)_env(safe-area-inset-right)_env(safe-area-inset-bottom)_env(safe-area-inset-left)]">
        <SiteHeader />

        <article class="mx-auto max-w-3xl px-4 py-8 sm:py-12">
            <Link
                href="/news"
                class="mb-8 inline-flex items-center gap-2 text-sm font-medium text-zinc-600 transition hover:text-orange-500 dark:text-zinc-400 dark:hover:text-orange-400"
            >
                <ArrowLeft class="h-4 w-4" />
                Все новости
            </Link>

            <header class="mb-8">
                <div class="mb-4 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-orange-500/15 px-3 py-1 text-sm font-medium text-orange-600 dark:text-orange-400">
                        <Newspaper class="h-4 w-4" />
                        Новость
                    </span>
                    <span v-if="news.date" class="text-sm text-zinc-500 dark:text-zinc-500">{{ news.date }}</span>
                </div>
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white sm:text-4xl md:text-5xl">
                    {{ news.title }}
                </h1>
            </header>

            <div
                v-if="news.image"
                class="mb-8 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800"
            >
                <img
                    :src="news.image"
                    :alt="news.title"
                    class="h-auto w-full object-cover"
                />
            </div>

            <div
                class="prose prose-zinc dark:prose-invert max-w-none
                    prose-headings:text-zinc-900 dark:prose-headings:text-white
                    prose-p:text-zinc-700 dark:prose-p:text-zinc-300
                    prose-a:text-orange-600 prose-a:no-underline hover:prose-a:underline dark:prose-a:text-orange-400
                    prose-strong:text-zinc-900 dark:prose-strong:text-white
                    prose-ul:text-zinc-700 dark:prose-ul:text-zinc-300
                    prose-li:text-zinc-700 dark:prose-li:text-zinc-300
                    prose-blockquote:border-orange-500 prose-blockquote:bg-orange-500/5 prose-blockquote:text-zinc-700 dark:prose-blockquote:text-zinc-300"
                v-html="news.body"
            />

            <footer class="mt-12 border-t border-zinc-200 pt-8 dark:border-zinc-800">
                <Link
                    href="/news"
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-orange-500/50 hover:bg-orange-500/10 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300 dark:hover:border-orange-500/30 dark:hover:bg-orange-500/10"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Вернуться к новостям
                </Link>
            </footer>
        </article>

        <SiteFooter />
    </div>
</template>

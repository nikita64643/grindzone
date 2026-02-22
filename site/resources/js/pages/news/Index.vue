<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight, Newspaper } from 'lucide-vue-next';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';

interface NewsItem {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    image: string | null;
    date: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    news: {
        data: NewsItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: PaginationLink[];
    };
}>();
</script>

<template>
    <Head title="Новости — GrindZone" />

    <div class="min-h-screen overflow-x-hidden bg-zinc-50 text-zinc-900 dark:bg-[#0a0a0d] dark:text-white [padding:env(safe-area-inset-top)_env(safe-area-inset-right)_env(safe-area-inset-bottom)_env(safe-area-inset-left)]">
        <SiteHeader />

        <div class="mx-auto max-w-6xl px-4 py-8 sm:py-12">
            <div class="mb-8 flex items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-orange-500/15 text-orange-600 dark:text-orange-400">
                    <Newspaper class="h-6 w-6" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Новости</h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Свежие анонсы и обновления проекта</p>
                </div>
            </div>

            <div v-if="news.data.length === 0" class="rounded-2xl border border-zinc-200 bg-white p-12 text-center dark:border-zinc-800 dark:bg-[#0f0f12]">
                <p class="text-zinc-600 dark:text-zinc-400">Пока нет новостей.</p>
            </div>

            <div v-else class="space-y-6">
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="item in news.data"
                        :key="item.id"
                        :href="`/news/${item.slug}`"
                        class="group flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white transition hover:border-orange-500/50 hover:shadow-lg dark:border-zinc-800 dark:bg-[#0f0f12] dark:hover:border-orange-500/30"
                    >
                        <div
                            class="h-40 shrink-0 bg-cover bg-center"
                            :style="{
                                backgroundImage: item.image
                                    ? `url(${item.image})`
                                    : 'linear-gradient(135deg, rgba(249,115,22,0.3), rgba(234,88,12,0.2))',
                            }"
                        />
                        <div class="flex flex-1 flex-col p-5">
                            <h2 class="mb-2 line-clamp-2 font-bold text-zinc-900 transition group-hover:text-orange-500 dark:text-white dark:group-hover:text-orange-400">
                                {{ item.title }}
                            </h2>
                            <p v-if="item.excerpt" class="mb-4 line-clamp-2 flex-1 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ item.excerpt }}
                            </p>
                            <div class="mt-auto flex items-center justify-between">
                                <span class="text-xs text-zinc-500 dark:text-zinc-500">{{ item.date }}</span>
                                <span class="inline-flex items-center gap-1 text-sm font-medium text-orange-600 transition group-hover:gap-2 dark:text-orange-400">
                                    Читать
                                    <ChevronRight class="h-4 w-4" />
                                </span>
                            </div>
                        </div>
                    </Link>
                </div>

                <nav
                    v-if="news.last_page > 1"
                    class="flex flex-wrap items-center justify-center gap-2 pt-4"
                    aria-label="Пагинация"
                >
                    <template v-for="(link, i) in news.links" :key="i">
                        <span
                            v-if="!link.url"
                            class="flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-zinc-200 px-3 text-sm text-zinc-400 dark:border-zinc-700 dark:text-zinc-500"
                        >
                            {{ link.label }}
                        </span>
                        <Link
                            v-else
                            :href="link.url"
                            class="flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border px-3 text-sm font-medium transition"
                            :class="
                                link.active
                                    ? 'border-orange-500 bg-orange-500 text-white dark:border-orange-500 dark:bg-orange-500'
                                    : 'border-zinc-200 text-zinc-700 hover:border-orange-500/50 hover:bg-orange-500/10 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-orange-500/30 dark:hover:bg-orange-500/10'
                            "
                            preserve-scroll
                        >
                            {{ link.label }}
                        </Link>
                    </template>
                </nav>
            </div>
        </div>

        <SiteFooter />
    </div>
</template>

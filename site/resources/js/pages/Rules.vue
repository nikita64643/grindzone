<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { ChevronDown, Scale } from 'lucide-vue-next';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';

interface RuleItem {
    num: string;
    text: string;
    note?: string;
    penalty?: string;
}

interface RuleChapter {
    id: number;
    title: string;
    slug: string;
    items: RuleItem[];
}

interface RulesIntro {
    headline: string;
    paragraphs: string[];
}

interface SidebarConfig {
    links?: Array<{ url: string; label: string }>;
}

interface ServerStatus {
    online: boolean;
    players_online: number;
    players_max: number;
}

const props = withDefaults(
    defineProps<{
        intro?: RulesIntro;
        chapters?: RuleChapter[];
        sidebar?: SidebarConfig;
        initialServerStatus?: Record<number, ServerStatus>;
    }>(),
    {
        intro: () => ({ headline: 'Правила проекта', paragraphs: [] }),
        chapters: () => [],
        sidebar: () => ({}),
        initialServerStatus: () => ({}),
    }
);

const page = usePage();
const headerServers = (page.props.headerServers as Array<{ name: string; version: string; port: number; slug?: string | null }>) ?? [];

function getServerStatus(port: number): ServerStatus | null {
    const status = props.initialServerStatus?.[port];
    return status ?? null;
}
</script>

<template>
    <Head title="Правила — GrindZone" />

    <div class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
        <SiteHeader />

        <div class="mx-auto max-w-6xl px-4 py-8 sm:py-12">
            <div class="mb-8 flex items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400">
                    <Scale class="h-6 w-6" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ intro.headline }}</h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Правила наших серверов Minecraft</p>
                </div>
            </div>

            <div class="flex flex-col gap-8 lg:flex-row lg:gap-10">
                <div class="min-w-0 flex-1">
                    <div
                        v-if="intro.paragraphs?.length"
                        class="mb-8 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8"
                    >
                        <p
                            v-for="(paragraph, i) in intro.paragraphs"
                            :key="i"
                            class="text-zinc-600 dark:text-zinc-400"
                            :class="{ 'mt-3': i > 0 }"
                        >
                            {{ paragraph }}
                        </p>
                    </div>

                    <div class="space-y-3">
                        <Collapsible
                            v-for="chapter in chapters"
                            :key="chapter.slug"
                            :id="chapter.slug"
                            :default-open="false"
                            class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50"
                        >
                            <CollapsibleTrigger
                                as-child
                                class="block w-full"
                            >
                                <button
                                    type="button"
                                    class="group flex w-full items-center justify-start gap-3 px-6 py-4 text-left transition hover:bg-zinc-50 dark:hover:bg-zinc-800/50 sm:px-8 rounded-t-2xl focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-inset"
                                >
                                    <span class="shrink-0 text-xl font-bold text-amber-600 dark:text-amber-400">{{ chapter.id }}.</span>
                                    <span class="min-w-0 flex-1 font-bold text-zinc-900 dark:text-white">{{ chapter.title }}</span>
                                    <ChevronDown
                                        class="ml-auto h-5 w-5 shrink-0 text-zinc-500 transition group-data-[state=open]:rotate-180"
                                        aria-hidden
                                    />
                                </button>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <ol class="divide-y divide-zinc-100 dark:divide-zinc-800/50">
                                    <li
                                        v-for="item in chapter.items"
                                        :key="item.num"
                                        :id="item.num"
                                        class="scroll-mt-24 px-6 py-4 sm:px-8"
                                    >
                                        <div class="flex gap-3">
                                            <span class="shrink-0 text-sm font-semibold text-amber-600 dark:text-amber-400">
                                                {{ item.num }}
                                            </span>
                                            <div class="min-w-0 flex-1 space-y-2">
                                                <p class="text-zinc-700 dark:text-zinc-300">{{ item.text }}</p>
                                                <p v-if="item.note" class="text-sm text-zinc-500 dark:text-zinc-400">
                                                    {{ item.note }}
                                                </p>
                                                <p
                                                    v-if="item.penalty"
                                                    class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200"
                                                >
                                                    {{ item.penalty }}
                                                </p>
                                            </div>
                                        </div>
                                    </li>
                                </ol>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                </div>

                <aside class="w-full shrink-0 lg:w-72">
                    <div class="sticky top-24 space-y-5 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-5">
                        <div v-if="sidebar.links?.length" class="space-y-2">
                            <Link
                                v-for="(link, i) in sidebar.links"
                                :key="i"
                                :href="link.url"
                                target="_blank"
                                rel="noopener noreferrer nofollow"
                                class="flex w-full items-center justify-center rounded-xl border border-zinc-200 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            >
                                {{ link.label }}
                            </Link>
                        </div>

                        <div v-if="headerServers.length" class="border-t border-zinc-200 pt-5 dark:border-zinc-800">
                            <h3 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-white">Наши сервера</h3>
                            <ul class="space-y-2">
                                <li v-for="server in headerServers" :key="server.port">
                                    <Link
                                        :href="server.slug ? `/servers/${server.slug}` : '/servers'"
                                        class="flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm transition hover:bg-zinc-100 dark:hover:bg-zinc-800"
                                    >
                                        <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ server.name }}</span>
                                        <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">
                                            <template v-if="getServerStatus(server.port)">
                                                {{ getServerStatus(server.port)!.players_online }} из {{ getServerStatus(server.port)!.players_max }}
                                            </template>
                                            <template v-else>—</template>
                                        </span>
                                    </Link>
                                </li>
                            </ul>
                            <Link
                                href="/servers"
                                class="mt-3 block text-center text-sm font-medium text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300"
                            >
                                Все сервера →
                            </Link>
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        <SiteFooter />
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, Gamepad2, ChevronRight, Copy, Check } from 'lucide-vue-next';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        serverAddress?: string;
        recommendedVersions?: string[];
    }>(),
    {
        serverAddress: 'localhost',
        recommendedVersions: () => ['1.16.5', '1.21.10'],
    },
);

const copied = ref(false);
function copyAddress() {
    navigator.clipboard.writeText(props.serverAddress).then(() => {
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    });
}

const steps = [
    {
        id: 1,
        title: 'Скачайте и установите лаунчер',
        description: 'Для начала игры на сервере потребуется лаунчер. Скачайте и установите его на свой компьютер.',
        links: [
            { label: 'Скачать LLauncher', url: 'https://llauncher.ru/' },
            { label: 'Minecraft Launcher', url: 'https://www.minecraft.net/ru-ru/download', note: 'При наличии лицензионной редакции Java & Bedrock' },
        ] as Array<{ label: string; url: string; note?: string }>,
        extra: 'А можно поиграть с телефона? Да, есть легальные способы игры с помощью приложений на смартфоне, если у вас лицензия Minecraft: Java & Bedrock.',
    },
    {
        id: 2,
        title: 'Выберите любую из версий',
        description: 'После запуска лаунчера найдите поле выбора версии и выберите одну из поддерживаемых версий.',
        versions: props.recommendedVersions,
        note: '*Подождите скачивания выбранной версии.',
    },
    {
        id: 3,
        title: 'Добавьте наш сервер',
        description: 'Как только скачалась и успешно запустилась выбранная вами версия Minecraft, добавьте сервер.',
        substeps: [
            'Нажмите на «Сетевая игра» или «Multiplayer»',
            'Найдите и нажмите на кнопку «Добавить сервер» или «Add server»',
            'В поле «Адрес сервера» или «Server Address» введите адрес нашего сервера',
        ],
    },
    {
        id: 4,
        title: 'Играем на сервере',
        description: 'Выберите в списке серверов наш сервер и нажмите кнопку «Подключиться» или «Join Server».',
        afterConnect: [
            'При входе на сервер защита проверит вас на робота. Обычно проверка длится быстро, но если вас просят ввести капчу — нажмите T, введите её в чат и нажмите Enter.',
            'После успешного прохождения проверки зарегистрируйтесь командой: /register (пароль) (повтор пароля)',
            'Если сервер просит авторизоваться: /login (пароль)',
            'Если вы на сервере впервые, но команда /login не срабатывает — возможно, никнейм уже занят. Поменяйте его в лаунчере и войдите снова.',
        ],
    },
];

const faq = [
    {
        question: 'Можно ли играть на сервере с телефона?',
        answer: 'Да, есть легальные способы игры с помощью приложений на смартфоне, если вы имеете лицензию Minecraft: Java & Bedrock. Способ игры через Minecraft: Bedrock Edition отдельно может быть недоступен на некоторых серверах.',
    },
    {
        question: 'Что делать, если меня забанили или замутили просто так?',
        answer: 'Если вы считаете, что вас заблокировали неправильно, вы можете подать жалобу и доказать свою правоту. Обратитесь в поддержку или на форум проекта. Подробнее ознакомьтесь с правилами на нашем сайте.',
    },
    {
        question: 'Я нашёл ошибку, что мне делать?',
        answer: 'При выявлении ошибок в тексте, работе сервера или в любых других местах просим сообщить об этом в поддержку. Помогите нам стать лучше! За вашу заявку также может быть выдано вознаграждение в зависимости от сложности проблемы.',
    },
    {
        question: 'Хочу быть частью вашей команды. Как стать модератором?',
        answer: 'Чтобы стать частью команды, необходимо иметь определённый опыт игры на нашем сервере. Если вы считаете, что готовы — рекомендуем ознакомиться с актуальными направлениями на форуме проекта.',
    },
];
</script>

<template>
    <Head title="Как начать игру — GrindZone" />

    <div class="min-h-screen overflow-x-hidden bg-zinc-50 text-zinc-900 dark:bg-[#0a0a0d] dark:text-white">
        <SiteHeader />

        <main class="mx-auto max-w-7xl px-4 py-12 sm:py-16">
            <div class="mb-12 text-center">
                <h1 class="mb-3 text-3xl font-bold text-zinc-900 dark:text-white sm:text-4xl md:text-5xl">Как начать игру</h1>
                <p class="text-lg text-zinc-600 dark:text-zinc-400">
                    Пошаговая инструкция для подключения к нашим серверам
                </p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <section
                    v-for="step in steps"
                    :key="step.id"
                    class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-orange-500/20 text-xl font-bold text-orange-600 dark:text-orange-500">
                            {{ step.id }}
                        </span>
                        <h2 class="text-xl font-bold text-zinc-900 dark:text-white sm:text-2xl">{{ step.title }}</h2>
                    </div>
                    <p class="mb-6 text-zinc-600 dark:text-zinc-400">{{ step.description }}</p>

                    <div v-if="step.links" class="mb-4 space-y-3">
                        <div v-for="link in step.links" :key="link.label" class="space-y-1">
                            <a
                                :href="link.url"
                                target="_blank"
                                rel="noopener noreferrer nofollow"
                                class="inline-flex items-center gap-2 rounded-lg border border-orange-500/50 bg-orange-500/10 px-4 py-2 text-orange-600 transition hover:bg-orange-500/20 dark:text-orange-400"
                            >
                                <Download class="h-4 w-4" />
                                {{ link.label }}
                                <ChevronRight class="h-4 w-4" />
                            </a>
                            <p v-if="link.note" class="text-sm text-zinc-500 dark:text-zinc-500">{{ link.note }}</p>
                        </div>
                    </div>

                    <div v-if="step.versions" class="mb-4">
                        <p class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">Наши рекомендации:</p>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="v in step.versions"
                                :key="v"
                                class="rounded-lg bg-emerald-500/20 px-3 py-1.5 font-mono text-sm text-emerald-700 dark:text-emerald-400"
                            >
                                {{ v }}
                            </span>
                        </div>
                        <p v-if="step.note" class="mt-2 text-sm text-zinc-500 dark:text-zinc-500">{{ step.note }}</p>
                    </div>

                    <div v-if="step.substeps && step.id === 3" class="mb-4 space-y-2">
                        <div
                            v-for="(sub, i) in step.substeps"
                            :key="i"
                            class="flex items-start gap-3 text-zinc-700 dark:text-zinc-300"
                        >
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-orange-500" />
                            <span>{{ sub }}</span>
                        </div>
                        <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                            <p class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">Наш адрес:</p>
                            <div class="flex items-center gap-2">
                                <code class="flex-1 rounded bg-zinc-100 px-3 py-2 font-mono text-orange-600 dark:bg-zinc-900 dark:text-orange-400">{{ serverAddress }}</code>
                                <button
                                    type="button"
                                    class="flex shrink-0 items-center gap-2 rounded-lg bg-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600"
                                    @click="copyAddress"
                                >
                                    <Check v-if="copied" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                    <Copy v-else class="h-4 w-4" />
                                    {{ copied ? 'Скопировано' : 'Скопировать' }}
                                </button>
                            </div>
                        </div>
                        <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-500">Нажмите «Готово» или «Done», затем выберите сервер и нажмите «Подключиться».</p>
                    </div>

                    <div v-if="step.afterConnect" class="space-y-3">
                        <div
                            v-for="(item, i) in step.afterConnect"
                            :key="i"
                            class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800/30 dark:text-zinc-300"
                        >
                            {{ item }}
                        </div>
                    </div>

                    <p v-if="step.extra" class="mt-4 text-sm text-zinc-500 dark:text-zinc-500">{{ step.extra }}</p>
                </section>
            </div>

            <section class="mt-16">
                <h2 class="mb-6 flex items-center gap-2 text-2xl font-bold text-zinc-900 dark:text-white">
                    <span class="text-orange-500">◆</span>
                    Ответы на частые вопросы
                </h2>
                <p class="mb-8 text-zinc-600 dark:text-zinc-400">
                    Давайте разберём самые частые вопросы, которые задают игроки нашего сервера
                </p>
                <div class="space-y-4">
                    <div
                        v-for="(item, i) in faq"
                        :key="i"
                        class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50"
                    >
                        <h3 class="mb-2 font-semibold text-zinc-900 dark:text-white">{{ item.question }}</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ item.answer }}</p>
                    </div>
                </div>
            </section>

            <div class="mt-16 flex flex-wrap justify-center gap-4">
                <Link
                    href="/servers"
                    class="inline-flex items-center gap-2 rounded-lg bg-orange-500 px-6 py-3 font-semibold text-white shadow-[0_0_20px_rgba(249,115,22,0.3)] transition hover:bg-orange-600"
                >
                    <Gamepad2 class="h-5 w-5" />
                    К списку серверов
                </Link>
                <Link
                    href="/donate"
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 px-6 py-3 font-medium text-zinc-700 transition hover:border-orange-500 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-orange-500 dark:hover:bg-zinc-800/50"
                >
                    Купить донат
                </Link>
            </div>
        </main>

        <SiteFooter />
    </div>
</template>

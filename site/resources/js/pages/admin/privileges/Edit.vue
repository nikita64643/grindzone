<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

interface PrivilegeData {
    id: number;
    key: string;
    name: string;
    description: string | null;
    price: number;
    features: string[];
    lp_permissions: string[];
    server_slugs: string[];
    easydonate_product_id: number;
}

const props = defineProps<{
    privilege: PrivilegeData;
    serverOptions: string[];
}>();

const form = useForm({
    name: props.privilege.name,
    description: props.privilege.description ?? '',
    price: props.privilege.price,
    features: props.privilege.features?.length ? props.privilege.features : [''],
    lp_permissions: props.privilege.lp_permissions?.length ? [...props.privilege.lp_permissions] : [''],
    server_slugs: props.privilege.server_slugs?.length ? [...props.privilege.server_slugs] : [],
    easydonate_product_id: props.privilege.easydonate_product_id || '',
});

function addFeature() {
    form.features = [...form.features, ''];
}

function removeFeature(i: number) {
    form.features = form.features.filter((_, idx) => idx !== i);
}

function addLpPermission() {
    form.lp_permissions = [...form.lp_permissions, ''];
}

function removeLpPermission(i: number) {
    form.lp_permissions = form.lp_permissions.filter((_, idx) => idx !== i);
}

function toggleServer(slug: string) {
    const idx = form.server_slugs.indexOf(slug);
    if (idx === -1) {
        form.server_slugs = [...form.server_slugs, slug];
    } else {
        form.server_slugs = form.server_slugs.filter((s) => s !== slug);
    }
}
</script>

<template>
    <Head :title="`Редактировать ${privilege.name} | Привилегии | Админка`" />

    <AdminLayout>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 sm:p-8">
            <Link
                href="/admin/privileges"
                class="mb-6 inline-flex items-center gap-2 text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
            >
                <ArrowLeft class="h-4 w-4" />
                К списку привилегий
            </Link>

            <h1 class="mb-6 text-2xl font-bold text-zinc-900 dark:text-white">
                Редактировать привилегию: {{ privilege.name }}
            </h1>
            <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                Ключ группы в LuckPerms: <code class="rounded bg-zinc-200 px-1 dark:bg-zinc-700">{{ privilege.key }}</code>
            </p>

            <form
                class="space-y-8"
                @submit.prevent="form.put(`/admin/privileges/${privilege.id}`)"
            >
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Название</Label>
                        <Input id="name" v-model="form.name" required />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="description">Описание</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="2"
                            class="flex w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                    <div class="space-y-2">
                        <Label for="price">Цена (₽)</Label>
                        <Input id="price" v-model.number="form.price" type="number" step="0.01" min="0" required />
                        <InputError :message="form.errors.price" />
                    </div>
                    <div class="space-y-2">
                        <Label for="easydonate_product_id">EasyDonate ID товара</Label>
                        <Input
                            id="easydonate_product_id"
                            v-model.number="form.easydonate_product_id"
                            type="number"
                            min="0"
                            placeholder="ID товара из панели EasyDonate"
                        />
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Для оплаты привилегии через EasyDonate. Создайте товар в панели и укажите его ID.
                        </p>
                        <InputError :message="form.errors.easydonate_product_id" />
                    </div>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <Label>Возможности (список для отображения на донате)</Label>
                        <Button type="button" variant="outline" size="sm" @click="addFeature">
                            + Добавить
                        </Button>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="(feat, i) in form.features"
                            :key="i"
                            class="flex gap-2"
                        >
                            <Input v-model="form.features[i]" placeholder="Например: Цветной ник в чате" />
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                :disabled="form.features.length <= 1"
                                @click="removeFeature(i)"
                            >
                                ×
                            </Button>
                        </div>
                    </div>
                    <InputError :message="form.errors.features" />
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <Label>Права LuckPerms (lp_permissions)</Label>
                        <Button type="button" variant="outline" size="sm" @click="addLpPermission">
                            + Добавить
                        </Button>
                    </div>
                    <p class="mb-3 text-xs text-zinc-500 dark:text-zinc-400">
                        Эти права выдаются игроку при донате (команды /nick, /kit, /home и т.д. через EssentialsX). По одному праву на строку, например: essentials.nick, essentials.kit.premium
                    </p>
                    <div class="space-y-2">
                        <div
                            v-for="(perm, i) in form.lp_permissions"
                            :key="'lp-' + i"
                            class="flex gap-2"
                        >
                            <Input
                                v-model="form.lp_permissions[i]"
                                placeholder="например: essentials.nick"
                                class="font-mono text-sm"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                :disabled="form.lp_permissions.length <= 1"
                                @click="removeLpPermission(i)"
                            >
                                ×
                            </Button>
                        </div>
                    </div>
                    <InputError :message="form.errors.lp_permissions" />
                </div>

                <div>
                    <Label class="mb-3 block">Привязка к серверам</Label>
                    <p class="mb-3 text-xs text-zinc-500 dark:text-zinc-400">
                        Выберите серверы, на которых эта привилегия доступна для покупки. Пусто — не отображается на донате.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="slug in serverOptions"
                            :key="slug"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition"
                            :class="form.server_slugs.includes(slug)
                                ? 'border-amber-500/60 bg-amber-500/10 text-amber-700 dark:text-amber-400'
                                : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800'"
                        >
                            <input
                                type="checkbox"
                                :checked="form.server_slugs.includes(slug)"
                                class="sr-only"
                                @change="toggleServer(slug)"
                            >
                            {{ slug }}
                        </label>
                    </div>
                    <InputError :message="form.errors.server_slugs" />
                </div>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Сохранение…' : 'Сохранить' }}
                    </Button>
                    <Link href="/admin/privileges" class="inline-flex items-center rounded-lg border border-zinc-200 px-4 py-2 text-sm font-medium dark:border-zinc-700">
                        Отмена
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

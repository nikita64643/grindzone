<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Shield, User as UserIcon } from 'lucide-vue-next';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import profile from '@/routes/profile';
import { logout } from '@/routes';
import type { User } from '@/types';

type Props = {
    user: User & { is_admin?: boolean };
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="profile.index()" prefetch>
                <UserIcon class="mr-2 h-4 w-4" />
                Профиль
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem v-if="user.is_admin" :as-child="true">
            <Link class="block w-full cursor-pointer" href="/admin" prefetch>
                <Shield class="mr-2 h-4 w-4" />
                Админка
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Выйти
        </Link>
    </DropdownMenuItem>
</template>

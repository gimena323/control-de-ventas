<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import type { User } from '@/types';

type Props = {
    user: User;
    showEmail?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const page = usePage();
const userRoles = computed(() => (page.props.auth?.roles as string[]) ?? []);

const roleLabel = computed(() => {
    const roles = userRoles.value;
    if (roles.includes('admin')) return 'Administradora';
    if (roles.includes('vendedor')) return 'Vendedora';
    if (roles.includes('encargado_inventario'))
        return 'Encargado de Inventario';
    return '';
});

const avatarSource = computed(() => {
    if (props.user.avatar?.trim()) {
        return props.user.avatar;
    }

    const normalizedName = props.user.name.toLocaleLowerCase();
    const isGimena =
        normalizedName.includes('gimena') && normalizedName.includes('quinaya');

    return isGimena ? '/images/perfil-gimena.jpeg' : null;
});

const showAvatar = computed(() => avatarSource.value !== null);

const initial = computed(() =>
    props.user.name ? props.user.name.charAt(0).toUpperCase() : '',
);
</script>

<template>
    <Avatar class="h-10 w-10 overflow-hidden rounded-lg">
        <AvatarImage
            v-if="showAvatar"
            :src="avatarSource!"
            :alt="user.name"
            class="object-cover object-[center_20%]"
        />
        <AvatarFallback
            class="avatar-initial rounded-lg text-black dark:text-white"
        >
            {{ initial }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="roleLabel" class="truncate text-xs text-muted-foreground">{{
            roleLabel
        }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{
            user.email
        }}</span>
    </div>
</template>

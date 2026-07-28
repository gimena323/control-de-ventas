<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-3 py-0">
        <SidebarGroupContent>
            <SidebarGroupLabel class="mb-3 text-xs font-semibold uppercase tracking-wider text-gradient">
                <span class="mr-1.5">✦</span> Plataforma
            </SidebarGroupLabel>
            <div class="space-y-1">
                <template v-for="item in items" :key="item.title">
                    <Link
                        :href="item.href"
                        :class="[
                            'group/link flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold tracking-wide transition-all duration-200',
                            isCurrentUrl(item.href)
                                ? 'bg-gradient-to-r from-rose-50 to-pink-50/80 text-rose-700 shadow-md border border-rose-200/50'
                                : 'text-gray-700 hover:bg-white/70 hover:text-rose-600 hover:shadow-md border border-transparent',
                        ]"
                    >
                        <component
                            :is="item.icon"
                            :class="[
                                'h-5 w-5 flex-shrink-0 transition-transform duration-200 group-hover/link:scale-110',
                                isCurrentUrl(item.href) ? 'text-rose-500' : 'text-gray-400 group-hover/link:text-rose-500',
                            ]"
                        />
                        <span class="truncate">{{ item.title }}</span>
                    </Link>
                </template>
            </div>
        </SidebarGroupContent>
    </SidebarGroup>
</template>

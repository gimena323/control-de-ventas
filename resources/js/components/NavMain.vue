<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();

const openMenus = ref<Record<string, boolean>>({});
</script>

<template>
    <SidebarGroup class="px-3 py-0">
        <SidebarGroupContent>
            <SidebarGroupLabel
                class="text-gradient mb-2 text-xs font-semibold tracking-wider uppercase"
            >
                <span class="mr-1.5">✦</span> Plataforma
            </SidebarGroupLabel>
            <div class="space-y-0.5">
                <template v-for="item in items" :key="item.title">
                    <Collapsible
                        v-if="item.subItems?.length"
                        v-model:open="openMenus[item.title]"
                        class="group/collapsible"
                    >
                        <CollapsibleTrigger as-child>
                            <button
                                class="group/link flex w-full items-center gap-3 rounded-lg border border-transparent px-4 py-2 text-sm font-semibold tracking-wide text-slate-700 transition-all duration-200 hover:bg-white/70 hover:text-rose-600 hover:shadow-md"
                            >
                                <component
                                    :is="item.icon"
                                    :class="[
                                        'h-5 w-5 flex-shrink-0 transition-transform duration-200 group-hover/link:scale-110',
                                        isCurrentUrl(item.href)
                                            ? 'text-rose-500'
                                            : 'text-gray-400 group-hover/link:text-rose-500',
                                    ]"
                                />
                                <span class="flex-1 truncate text-left">{{
                                    item.title
                                }}</span>
                                <ChevronDown
                                    :class="[
                                        'h-4 w-4 text-gray-400 transition-transform duration-200',
                                        openMenus[item.title]
                                            ? 'rotate-180'
                                            : '',
                                    ]"
                                />
                            </button>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem
                                    v-for="sub in item.subItems"
                                    :key="sub.title"
                                >
                                    <SidebarMenuSubButton
                                        as-child
                                        :is-active="isCurrentUrl(sub.href)"
                                    >
                                        <Link :href="sub.href">
                                            <component
                                                :is="sub.icon"
                                                class="mr-2 h-4 w-4"
                                                v-if="sub.icon"
                                            />
                                            <span>{{ sub.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </Collapsible>
                    <Link
                        v-else
                        :href="item.href"
                        :data-active="isCurrentUrl(item.href)"
                        :class="[
                            'group/link flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-semibold tracking-wide transition-all duration-200',
                            isCurrentUrl(item.href)
                                ? 'border border-rose-200/50 bg-gradient-to-r from-rose-50 to-pink-50/80 text-rose-700 shadow-md'
                                : 'border border-transparent text-slate-700 hover:bg-white/70 hover:text-rose-600 hover:shadow-md',
                        ]"
                    >
                        <component
                            :is="item.icon"
                            :class="[
                                'h-5 w-5 flex-shrink-0 transition-transform duration-200 group-hover/link:scale-110',
                                isCurrentUrl(item.href)
                                    ? 'text-rose-500'
                                    : 'text-gray-400 group-hover/link:text-rose-500',
                            ]"
                        />
                        <span class="truncate">{{ item.title }}</span>
                    </Link>
                </template>
            </div>
        </SidebarGroupContent>
    </SidebarGroup>
</template>

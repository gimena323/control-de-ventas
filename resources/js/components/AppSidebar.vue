<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { BarChart3, BookOpen, ChevronDown, FolderGit2, Globe, Home, LayoutGrid, Package, Package2, Settings, Shield, ShoppingCart, TrendingUp, Truck, User, Users } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubItem,
    SidebarMenuSubButton,

} from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

const { can, hasRole } = usePermissions();
const { isCurrentUrl } = useCurrentUrl();

const reportesOpen = ref(true);

const dashboardUrl = dashboard().url;

const vendedorOnlyNavItems: { title: string; href: string; icon: any }[] = [
    {
        title: 'Dashboard',
        href: dashboardUrl,
        icon: LayoutGrid,
    },
    {
        title: 'Ventas',
        href: '/ventas',
        icon: TrendingUp,
    },
];

const mainNavItems = computed<NavItem[]>(() => {
    if (hasRole('vendedor')) {
        return vendedorOnlyNavItems;
    }

    const items: { title: string; href: string; permission?: string; icon: any }[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            permission: 'panel.ver',
            icon: LayoutGrid,
        },
        {
            title: 'Compras',
            href: '/compras',
            permission: 'compras.ver',
            icon: ShoppingCart,
        },
        {
            title: 'Ventas',
            href: '/ventas',
            permission: 'ventas.ver',
            icon: TrendingUp,
        },
        {
            title: 'Inventario',
            href: '/inventario',
            permission: 'inventario.ver',
            icon: Package2,
        },
         {
            title: 'Productos',
            href: '/productos',
            permission: 'productos.ver',
            icon: Package,
        },
         {
            title: 'Clientes',
            href: '/clientes',
            permission: 'clientes.ver',
            icon: Users,
        },
         {
            title: 'Proveedores',
            href: '/proveedores',
            permission: 'proveedores.ver',
            icon: Truck,
        },
            {
            title: 'Roles y Permisos',
            href: '/roles',
            permission: 'usuarios.gestion',
            icon: Shield,
        },
            {
            title: 'Usuarios',
            href: '/usuarios',
            permission: 'usuarios.gestion',
            icon: User,
        },
            {
            title: 'Configuraciones',
            href: '/configuraciones',
            permission: 'configuraciones.gestion',
            icon: Settings,
        },
    ];

    return items.filter((item) => !item.permission || can(item.permission));
});

const catalogNavItems = computed<NavItem[]>(() => [
    {
        title: 'Página Principal',
        href: '/',
        icon: Home,
    },
    {
        title: 'Ver Catálogo',
        href: '/catalogo',
        icon: Globe,
    },
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="offcanvas" class="sidebar-gradient-unified z-10 sticky top-0 h-svh">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="flex flex-col gap-0">
            <NavMain :items="mainNavItems" />

            <div v-if="can('panel.ver')" class="px-3 pt-2">
                <Collapsible v-model:open="reportesOpen">
                    <CollapsibleTrigger as-child>
                        <button
                            class="group/link flex w-full items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold tracking-wide transition-all duration-200 text-gray-700 hover:bg-white/70 hover:text-rose-600 hover:shadow-md border border-transparent"
                        >
                            <BarChart3 class="h-5 w-5 flex-shrink-0 text-gray-400 transition-transform duration-200 group-hover/link:scale-110 group-hover/link:text-rose-500" />
                            <span class="truncate flex-1 text-left">Reportes</span>
                            <ChevronDown
                                :class="[
                                    'h-4 w-4 text-gray-400 transition-transform duration-200',
                                    reportesOpen ? 'rotate-180' : '',
                                ]"
                            />
                        </button>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem>
                                <SidebarMenuSubButton
                                    as-child
                                    :is-active="isCurrentUrl('/reportes/compras')"
                                >
                                    <Link href="/reportes/compras">
                                        <ShoppingCart class="mr-2 h-4 w-4" />
                                        <span>Compras</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                            <SidebarMenuSubItem>
                                <SidebarMenuSubButton
                                    as-child
                                    :is-active="isCurrentUrl('/reportes/ventas')"
                                >
                                    <Link href="/reportes/ventas">
                                        <TrendingUp class="mr-2 h-4 w-4" />
                                        <span>Ventas</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </Collapsible>
            </div>

            <div class="px-3 pt-2">
                <div class="mb-2 px-4">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gradient">
                        <span class="mr-1.5">✦</span> Catálogo
                    </span>
                </div>
                <div class="space-y-1">
                    <template v-for="item in catalogNavItems" :key="item.title">
                        <Link
                            :href="item.href"
                            class="group/link flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold tracking-wide transition-all duration-200 text-gray-700 hover:bg-white/70 hover:text-rose-600 hover:shadow-md border border-transparent"
                        >
                            <component
                                :is="item.icon"
                                class="h-5 w-5 flex-shrink-0 text-gray-400 transition-transform duration-200 group-hover/link:scale-110 group-hover/link:text-rose-500"
                            />
                            <span class="truncate">{{ item.title }}</span>
                        </Link>
                    </template>
                </div>
            </div>

            <div class="mt-auto">
                <NavUser />
            </div>
        </SidebarContent>
    </Sidebar>
    <slot />
</template>

<style>
.sidebar-gradient-unified,
.sidebar-gradient-unified [data-sidebar="sidebar"] {
    background: linear-gradient(
        135deg,
        #fff5f7,
        #fdf2f8,
        #faf5ff,
        #f5f3ff,
        #eef2ff,
        #ecfeff,
        #fff5f7
    ) !important;
    background-size: 400% 400% !important;
    animation: unified-gradient 20s ease infinite !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
}

.dark .sidebar-gradient-unified,
.dark .sidebar-gradient-unified [data-sidebar="sidebar"] {
    background: linear-gradient(
        135deg,
        #fff5f7,
        #fdf2f8,
        #faf5ff,
        #f5f3ff,
        #eef2ff,
        #ecfeff,
        #fff5f7
    ) !important;
    background-size: 400% 400% !important;
    animation: unified-gradient 20s ease infinite !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
}

@keyframes unified-gradient {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}
</style>

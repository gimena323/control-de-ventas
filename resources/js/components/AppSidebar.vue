<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BarChart3,
    BookOpen,
    Globe,
    LayoutGrid,
    Package,
    Package2,
    Shield,
    ShoppingCart,
    TrendingUp,
    Truck,
    User,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard, principal } from '@/routes';
import {
    compras as reporteCompras,
    ventas as reporteVentas,
} from '@/routes/reportes';
import type { NavItem } from '@/types';

const { can, hasRole } = usePermissions();
const { isCurrentUrl } = useCurrentUrl();

const dashboardUrl = dashboard().url;
const principalUrl = principal().url;

const mainNavItems = computed<NavItem[]>(() => {
    const items: {
        title: string;
        href: string;
        permission?: string;
        hiddenForVendedor?: boolean;
        icon: any;
    }[] = [
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
            hiddenForVendedor: true,
            icon: Package,
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
    ];

    if (hasRole('vendedor')) {
        items.push({
            title: 'Reporte de mis ventas',
            href: reporteVentas().url,
            icon: BarChart3,
        });
    }

    return items.filter((item) => {
        if (item.permission && !can(item.permission)) {
            return false;
        }

        return !(item.hiddenForVendedor && hasRole('vendedor'));
    });
});
</script>

<template>
    <Sidebar
        collapsible="offcanvas"
        class="sidebar-gradient-unified sticky top-0 z-10 h-svh"
    >
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

            <div
                v-if="can('reportes.compras.ver')"
                class="px-3 pt-1"
            >
                <Link
                    :href="reporteCompras().url"
                    :data-active="
                        isCurrentUrl(reporteCompras().url) ||
                        isCurrentUrl(reporteVentas().url)
                    "
                    :class="[
                        'group/link flex w-full items-center gap-3 rounded-lg px-4 py-2 text-sm font-semibold tracking-wide transition-all duration-200',
                        isCurrentUrl(reporteCompras().url) ||
                        isCurrentUrl(reporteVentas().url)
                            ? 'border border-rose-200/50 bg-gradient-to-r from-rose-50 to-pink-50/80 text-rose-700 shadow-md'
                            : 'border border-transparent text-slate-700 hover:bg-white/70 hover:text-rose-600 hover:shadow-md',
                    ]"
                >
                    <BarChart3
                        :class="[
                            'h-5 w-5 flex-shrink-0 transition-transform duration-200 group-hover/link:scale-110',
                            isCurrentUrl(reporteCompras().url) ||
                            isCurrentUrl(reporteVentas().url)
                                ? 'text-rose-500'
                                : 'text-gray-400 group-hover/link:text-rose-500',
                        ]"
                    />
                    <span class="truncate">Reportes</span>
                </Link>
            </div>

            <div class="space-y-0.5 px-3 pt-1">
                <Link
                    :href="principalUrl"
                    :data-active="isCurrentUrl(principalUrl)"
                    :class="[
                        'group/link flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-semibold tracking-wide transition-all duration-200',
                        isCurrentUrl(principalUrl)
                            ? 'border border-rose-200/50 bg-gradient-to-r from-rose-50 to-pink-50/80 text-rose-700 shadow-md'
                            : 'border border-transparent text-slate-700 hover:bg-white/70 hover:text-rose-600 hover:shadow-md',
                    ]"
                >
                    <Globe
                        :class="[
                            'h-5 w-5 flex-shrink-0 transition-transform duration-200 group-hover/link:scale-110',
                            isCurrentUrl(principalUrl)
                                ? 'text-rose-500'
                                : 'text-gray-400 group-hover/link:text-rose-500',
                        ]"
                    />
                    <span class="truncate">Ver Página</span>
                </Link>
                <Link
                    href="/catalogo"
                    prefetch="hover"
                    :data-active="isCurrentUrl('/catalogo')"
                    :class="[
                        'group/link flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-semibold tracking-wide transition-all duration-200',
                        isCurrentUrl('/catalogo')
                            ? 'border border-rose-200/50 bg-gradient-to-r from-rose-50 to-pink-50/80 text-rose-700 shadow-md'
                            : 'border border-transparent text-slate-700 hover:bg-white/70 hover:text-rose-600 hover:shadow-md',
                    ]"
                >
                    <BookOpen
                        :class="[
                            'h-5 w-5 flex-shrink-0 transition-transform duration-200 group-hover/link:scale-110',
                            isCurrentUrl('/catalogo')
                                ? 'text-rose-500'
                                : 'text-gray-400 group-hover/link:text-rose-500',
                        ]"
                    />
                    <span class="truncate">Ver Catálogo</span>
                </Link>
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
.sidebar-gradient-unified [data-sidebar='sidebar'] {
    background: linear-gradient(
        160deg,
        #1f0a35 0%,
        #3b0b4f 35%,
        #500d52 68%,
        #26072f 100%
    ) !important;
    background-size: 300% 300% !important;
    animation: unified-gradient 18s ease infinite !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
}

.dark .sidebar-gradient-unified,
.dark .sidebar-gradient-unified [data-sidebar='sidebar'] {
    background: linear-gradient(
        160deg,
        #1f0a35 0%,
        #3b0b4f 35%,
        #500d52 68%,
        #26072f 100%
    ) !important;
    background-size: 300% 300% !important;
    animation: unified-gradient 18s ease infinite !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
}

.sidebar-gradient-unified a,
.sidebar-gradient-unified button,
.sidebar-gradient-unified [data-sidebar='group-label'] {
    color: #fff !important;
}

.sidebar-gradient-unified svg {
    color: #fce7f3 !important;
}

.sidebar-gradient-unified a:hover,
.sidebar-gradient-unified button:hover,
.sidebar-gradient-unified [data-active='true'] {
    background: linear-gradient(90deg, #ec4899, #c026d3) !important;
    color: #fff !important;
    border-color: rgba(255, 255, 255, 0.35) !important;
    box-shadow: 0 8px 20px -10px rgba(236, 72, 153, 0.9) !important;
}

.sidebar-gradient-unified [data-sidebar='menu-button'] span,
.sidebar-gradient-unified [data-sidebar='sidebar'] span {
    color: inherit;
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

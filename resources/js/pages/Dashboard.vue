<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { AlertTriangle, CalendarDays, CalendarRange, Eye, Package, PackageOpen, ShoppingCart, TrendingUp, Users } from 'lucide-vue-next';
import { dashboard } from '@/routes';

const props = defineProps<{
    stats: {
        totalProductos: number;
        stockBajo: number;
        ventasHoy: number;
        ventasMes: number;
        totalClientes: number;
        totalUsuarios: number;
    };
    stockAlertas: Array<{
        producto: string;
        cantidad: number;
    }>;
    ventasRecientes: Array<{
        cliente: string;
        fecha: string;
        productos: string;
        total: number;
    }>;
    isVendedor: boolean;
}>();

const page = usePage();

const user = computed(() => page.props.auth?.user ?? null);
const userRoles = computed(() => page.props.auth?.roles ?? []);

function capitalizeName(name: string): string {
    return name.replace(/\b\w/g, char => char.toUpperCase());
}

const roleInfo = computed(() => {
    const roles = userRoles.value;
    if (roles.includes('admin')) {
        return { greeting: 'Bienvenida', title: 'Panel administrativo del sistema', roleName: 'Administradora' };
    }
    if (roles.includes('vendedor')) {
        return { greeting: 'Bienvenida', title: 'Panel de ventas y atención al cliente', roleName: 'Vendedora' };
    }
    if (roles.includes('encargado_inventario')) {
        return { greeting: 'Bienvenida', title: 'Panel de gestión de inventario', roleName: 'Encargado de Inventario' };
    }
    return { greeting: 'Bienvenido', title: 'Panel de control', roleName: 'Usuario' };
});

const userName = computed(() => {
    const name = user.value?.name;
    return name ? capitalizeName(name) : 'Usuario';
});

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const cards = [
    {
        title: 'Productos',
        value: props.stats.totalProductos,
        icon: Package,
        gradient: 'from-pink-400 to-rose-500',
        bgLight: 'bg-pink-50',
        textColor: 'text-pink-600',
        iconBg: 'bg-pink-100',
    },
    {
        title: 'Stock Bajo',
        value: props.stats.stockBajo,
        icon: PackageOpen,
        gradient: 'from-amber-400 to-orange-500',
        bgLight: 'bg-amber-50',
        textColor: 'text-amber-600',
        iconBg: 'bg-amber-100',
    },
    {
        title: 'Ventas Hoy',
        value: `S/ ${props.stats.ventasHoy.toFixed(2)}`,
        icon: CalendarDays,
        gradient: 'from-purple-400 to-violet-500',
        bgLight: 'bg-purple-50',
        textColor: 'text-purple-600',
        iconBg: 'bg-purple-100',
    },
    {
        title: 'Ventas del Mes',
        value: `S/ ${props.stats.ventasMes.toFixed(2)}`,
        icon: CalendarRange,
        gradient: 'from-sky-400 to-blue-500',
        bgLight: 'bg-sky-50',
        textColor: 'text-sky-600',
        iconBg: 'bg-sky-100',
    },
];

const formatCurrency = (value: number): string => {
    return `S/ ${value.toFixed(2)}`;
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-rose-50/90 via-purple-50/90 to-sky-50/90 p-8 shadow-sm border border-white/60">
            <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-gradient-to-br from-rose-200/40 to-purple-300/30 blur-2xl" />
            <div class="absolute -bottom-6 -left-6 h-32 w-32 rounded-full bg-gradient-to-tr from-sky-200/40 to-cyan-200/30 blur-2xl" />

            <div class="relative flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-rose-400 to-pink-500 text-white shadow-md">
                            <span class="avatar-initial text-xl font-bold">{{ userName ? userName.charAt(0).toUpperCase() : '' }}</span>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight text-gray-800">
                                {{ roleInfo.greeting }} {{ userName }}
                            </h1>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-3 py-0.5 text-sm font-medium text-rose-600 shadow-sm border border-rose-200/50">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500" />
                                    {{ roleInfo.roleName }}
                                </span>
                                <p class="text-base text-gray-500">
                                    {{ roleInfo.title }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <Link
                    href="/ventas"
                    class="group hidden items-center gap-2 rounded-xl bg-gradient-to-r from-rose-500 to-pink-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg sm:inline-flex"
                >
                    <ShoppingCart class="h-4 w-4 transition-transform duration-200 group-hover:scale-110" />
                    Registrar Venta
                </Link>
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="card in cards"
                :key="card.title"
                class="group relative overflow-hidden rounded-xl border border-white/60 bg-white/80 p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md"
            >
                <div class="absolute -right-6 -top-6 h-20 w-20 rounded-full opacity-0 bg-gradient-to-br transition-all duration-500 group-hover:opacity-10"
                    :class="card.gradient"
                />
                <div class="flex items-start justify-between">
                    <div class="flex flex-col gap-1">
                        <span class="text-sm font-medium text-gray-500">
                            {{ card.title }}
                        </span>
                        <span class="text-3xl font-bold tracking-tight text-gray-800">
                            {{ card.value }}
                        </span>
                    </div>
                    <div :class="[card.iconBg, 'flex h-12 w-12 items-center justify-center rounded-xl shadow-sm transition-transform duration-300 group-hover:scale-110']">
                        <component :is="card.icon" :class="[card.textColor, 'h-6 w-6']" />
                    </div>
                </div>
                <div :class="['mt-4 h-1.5 w-full rounded-full', card.bgLight]">
                    <div :class="['h-1.5 rounded-full bg-gradient-to-r transition-all duration-500', card.gradient]"
                        :style="{ width: Math.min((typeof card.value === 'number' ? card.value : 0) / 100 * 100, 100) + '%' }"
                    />
                </div>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-xl border border-white/60 bg-white/80 p-6 shadow-sm transition-all duration-300 hover:shadow-md">
                <div class="mb-5 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100">
                            <AlertTriangle class="h-5 w-5 text-amber-600" />
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800">Alertas de Stock</h3>
                    </div>
                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                        {{ stockAlertas.length }} alertas
                    </span>
                </div>

                <div v-if="stockAlertas.length > 0" class="space-y-3">
                    <div
                        v-for="(alerta, index) in stockAlertas"
                        :key="index"
                        class="flex items-center justify-between rounded-lg bg-amber-50/80 px-4 py-3 border border-amber-200/50"
                    >
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-200/60">
                                <PackageOpen class="h-4 w-4 text-amber-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">{{ alerta.producto }}</p>
                                <p class="text-xs text-gray-500">Cantidad restante</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-rose-100 px-3 py-1 text-sm font-bold text-rose-600">
                            {{ alerta.cantidad }} uds
                        </span>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center justify-center py-10 text-center">
                    <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
                        <PackageOpen class="h-8 w-8 text-emerald-500" />
                    </div>
                    <p class="text-sm font-medium text-gray-600">No hay alertas de stock</p>
                    <p class="mt-1 text-xs text-gray-400">Todos los productos tienen inventario suficiente</p>
                </div>

                <div class="mt-5 border-t border-gray-100 pt-4">
                    <Link
                        href="/inventario"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <Eye class="h-4 w-4" />
                        Ver Inventario
                    </Link>
                </div>
            </div>

            <div class="rounded-xl border border-white/60 bg-white/80 p-6 shadow-sm transition-all duration-300 hover:shadow-md">
                <div class="mb-5 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100">
                            <TrendingUp class="h-5 w-5 text-purple-600" />
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800">Ventas Recientes</h3>
                    </div>
                    <span class="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-semibold text-purple-700">
                        {{ ventasRecientes.length }} ventas
                    </span>
                </div>

                <div v-if="ventasRecientes.length > 0" class="space-y-3">
                    <div
                        v-for="(venta, index) in ventasRecientes"
                        :key="index"
                        class="flex items-center justify-between rounded-lg bg-purple-50/80 px-4 py-3 border border-purple-200/50"
                    >
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-200/60">
                                <ShoppingCart class="h-4 w-4 text-purple-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">{{ venta.cliente }}</p>
                                <p class="text-xs text-gray-500">{{ venta.fecha }} &middot; {{ venta.productos }}</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-600">
                            {{ formatCurrency(venta.total) }}
                        </span>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center justify-center py-10 text-center">
                    <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-purple-100">
                        <TrendingUp class="h-8 w-8 text-purple-400" />
                    </div>
                    <p class="text-sm font-medium text-gray-600">No hay ventas recientes</p>
                    <p class="mt-1 text-xs text-gray-400">Las ventas aparecerán aquí cuando se registren</p>
                </div>

                <div class="mt-5 border-t border-gray-100 pt-4">
                    <Link
                        href="/ventas"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-purple-500 to-violet-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <Eye class="h-4 w-4" />
                        Ver Ventas
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

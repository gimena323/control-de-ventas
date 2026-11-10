<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Flower2,
    PackageOpen,
    Shirt,
    ShoppingBag,
    ShoppingCart,
    Sparkles,
    Sprout,
    TrendingUp,
    Truck,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { dashboard } from '@/routes';

const props = defineProps<{
    stats: {
        totalProductos: number;
        stockBajo: number;
        ventasHoy: number;
        ventasMes: number;
        totalClientes: number;
        totalUsuarios: number;
        totalVentas: number;
        totalCompras: number;
        totalProveedores: number;
    };
    estadisticas: Array<{
        fecha: string;
        ventas: number;
        compras: number;
    }>;
    stockAlertas: Array<{
        producto: string;
        cantidad: number;
        imagen: string | null;
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
    return name.replace(/\b\w/g, (char) => char.toUpperCase());
}

const roleInfo = computed(() => {
    const roles = userRoles.value;

    if (roles.includes('admin')) {
        return {
            greeting: 'Bienvenida',
            title: 'Panel administrativo del sistema',
            roleName: 'Administradora',
        };
    }

    if (roles.includes('vendedor')) {
        return {
            greeting: 'Bienvenida',
            title: 'Panel de ventas y atención al cliente',
            roleName: 'Vendedora',
        };
    }

    if (roles.includes('encargado_inventario')) {
        return {
            greeting: 'Bienvenida',
            title: 'Panel de gestión de inventario',
            roleName: 'Encargado de Inventario',
        };
    }

    return {
        greeting: 'Bienvenido',
        title: 'Panel de control',
        roleName: 'Usuario',
    };
});

const userName = computed(() => {
    const name = user.value?.name;

    return name ? capitalizeName(name) : 'Usuario';
});

const userInitial = computed(() => userName.value.charAt(0).toUpperCase());

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
        title: 'Usuarios',
        value: props.stats.totalUsuarios,
        icon: Users,
        gradient: 'from-[#FFB347] to-[#FF7A69]',
        accent: 'from-[#FFB347] to-[#FF7A69]',
        shadow: 'shadow-orange-200/40',
    },
    {
        title: 'Ventas',
        value: props.stats.totalVentas,
        icon: ShoppingCart,
        gradient: 'from-[#42C2D5] to-[#31AFC8]',
        accent: 'from-[#42C2D5] to-[#31AFC8]',
        shadow: 'shadow-cyan-200/40',
    },
    {
        title: 'Compras',
        value: props.stats.totalCompras,
        icon: ShoppingBag,
        gradient: 'from-[#F27387] to-[#EB3D9B]',
        accent: 'from-[#F27387] to-[#EB3D9B]',
        shadow: 'shadow-pink-200/40',
    },
    {
        title: 'Clientes',
        value: props.stats.totalClientes,
        icon: Users,
        gradient: 'from-[#69C7B3] to-[#528FD1]',
        accent: 'from-[#69C7B3] to-[#528FD1]',
        shadow: 'shadow-sky-200/40',
    },
    {
        title: 'Proveedores',
        value: props.stats.totalProveedores,
        icon: Truck,
        gradient: 'from-[#2872E7] to-[#59C8DD]',
        accent: 'from-[#2872E7] to-[#59C8DD]',
        shadow: 'shadow-blue-200/40',
    },
    {
        title: 'Productos',
        value: props.stats.totalProductos,
        icon: Shirt,
        gradient: 'from-[#D04D9D] to-[#75B6AB]',
        accent: 'from-[#D04D9D] to-[#75B6AB]',
        shadow: 'shadow-fuchsia-200/40',
    },
];

const formatCurrency = (value: number): string => {
    return `Bs. ${value.toFixed(2)}`;
};

const chartWidth = 560;
const chartHeight = 220;
const chartPadding = { top: 18, right: 18, bottom: 38, left: 62 };
const chartBarWidth = 42;

function chartMaximum(type: 'ventas' | 'compras'): number {
    const maximum = Math.max(
        ...props.estadisticas.map((item) => item[type]),
        1,
    );
    const magnitude = 10 ** Math.floor(Math.log10(maximum));
    const step = magnitude / 2;

    return Math.ceil(maximum / step) * step;
}

function formatAxisValue(value: number): string {
    return new Intl.NumberFormat('es-BO', {
        maximumFractionDigits: 0,
    }).format(value);
}

function chartPoints(type: 'ventas' | 'compras'): string {
    const values = props.estadisticas.map((item) => item[type]);
    const maximum = chartMaximum(type);
    const usableWidth = chartWidth - chartPadding.left - chartPadding.right;
    const usableHeight = chartHeight - chartPadding.top - chartPadding.bottom;

    return values
        .map((value, index) => {
            const x =
                chartPadding.left +
                (index * usableWidth) / Math.max(values.length - 1, 1);
            const y =
                chartPadding.top +
                usableHeight -
                (value / maximum) * usableHeight;

            return `${x},${y}`;
        })
        .join(' ');
}

function chartY(value: number, type: 'ventas' | 'compras'): number {
    const maximum = chartMaximum(type);
    const usableHeight = chartHeight - chartPadding.top - chartPadding.bottom;

    return chartPadding.top + usableHeight - (value / maximum) * usableHeight;
}

function chartX(index: number): number {
    const usableWidth = chartWidth - chartPadding.left - chartPadding.right;

    return (
        chartPadding.left +
        (index * usableWidth) / Math.max(props.estadisticas.length - 1, 1)
    );
}

// ---------- Decoración de fondo (solo visual) ----------
type DecorButterfly = {
    top: string;
    left: string;
    size: number;
    color: string;
    delay: number;
    duration: number;
    opacity: number;
};

const decorButterflies: DecorButterfly[] = [
    {
        top: '5%',
        left: '2%',
        size: 38,
        color: '#f9a8d4',
        delay: 0,
        duration: 20,
        opacity: 0.55,
    },
    {
        top: '11%',
        left: '90%',
        size: 32,
        color: '#d8b4fe',
        delay: 2,
        duration: 24,
        opacity: 0.5,
    },
    {
        top: '68%',
        left: '3%',
        size: 30,
        color: '#fbcfe8',
        delay: 4,
        duration: 18,
        opacity: 0.45,
    },
    {
        top: '78%',
        left: '92%',
        size: 36,
        color: '#c4b5fd',
        delay: 1,
        duration: 22,
        opacity: 0.55,
    },
    {
        top: '88%',
        left: '46%',
        size: 28,
        color: '#fda4af',
        delay: 5,
        duration: 26,
        opacity: 0.4,
    },
];

const decorSparkles: {
    top: string;
    left: string;
    size: number;
    delay: number;
}[] = [
    { top: '8%', left: '14%', size: 18, delay: 0 },
    { top: '16%', left: '82%', size: 14, delay: 1.4 },
    { top: '40%', left: '7%', size: 16, delay: 2.6 },
    { top: '65%', left: '88%', size: 18, delay: 0.8 },
    { top: '85%', left: '30%', size: 14, delay: 3.2 },
];
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="dashboard-bg relative flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6 sm:gap-7 sm:p-8 lg:p-10"
    >
        <!-- ===================== DECORACIÓN DE FONDO ===================== -->
        <div
            class="pointer-events-none absolute inset-0 z-0 overflow-hidden"
            aria-hidden="true"
        >
            <div
                v-for="(b, i) in decorButterflies"
                :key="'b' + i"
                class="decor-butterfly"
                :style="{
                    top: b.top,
                    left: b.left,
                    width: b.size + 'px',
                    height: b.size + 'px',
                    color: b.color,
                    opacity: b.opacity,
                    animationDelay: b.delay + 's',
                    animationDuration: b.duration + 's',
                }"
            >
                <svg
                    viewBox="0 0 100 100"
                    class="decor-butterfly-svg"
                    :style="{ animationDelay: '-' + i * 1.2 + 's' }"
                >
                    <path
                        d="M50 50 C30 22 6 30 10 48 C14 62 32 64 50 50Z"
                        fill="currentColor"
                    />
                    <path
                        d="M50 50 C70 22 94 30 90 48 C86 62 68 64 50 50Z"
                        fill="currentColor"
                    />
                    <path
                        d="M50 50 C34 64 20 80 30 88 C38 94 46 74 50 50Z"
                        fill="currentColor"
                        opacity="0.7"
                    />
                    <path
                        d="M50 50 C66 64 80 80 70 88 C62 94 54 74 50 50Z"
                        fill="currentColor"
                        opacity="0.7"
                    />
                    <ellipse
                        cx="50"
                        cy="52"
                        rx="3"
                        ry="13"
                        fill="#7e3a8a"
                        opacity="0.35"
                    />
                </svg>
            </div>

            <div
                v-for="(s, i) in decorSparkles"
                :key="'s' + i"
                class="decor-sparkle"
                :style="{
                    top: s.top,
                    left: s.left,
                    width: s.size + 'px',
                    height: s.size + 'px',
                    animationDelay: s.delay + 's',
                }"
            >
                <svg
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    class="h-full w-full"
                >
                    <path
                        d="M12 2c.4 4.9 2.7 7.2 7.6 7.6-4.9.4-7.2 2.7-7.6 7.6-.4-4.9-2.7-7.2-7.6-7.6C9.3 9.2 11.6 6.9 12 2Z"
                    />
                </svg>
            </div>
        </div>

        <!-- ===================== BANNER BIENVENIDA ===================== -->
        <div
            class="relative z-10 min-h-[300px] overflow-hidden rounded-[2rem] border border-white/80 shadow-[0_24px_60px_-20px_rgba(224,150,170,0.45)]"
        >
            <div class="absolute inset-0">
                <img
                    src="/images/dashboard-boutique-hero.png"
                    alt=""
                    class="h-full w-full object-cover object-center"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-r from-white/35 via-white/20 to-white/15"
                ></div>
                <div
                    class="absolute inset-0 bg-gradient-to-t from-rose-950/10 via-transparent to-white/5"
                ></div>
            </div>

            <div
                class="relative flex h-full flex-col justify-center px-8 py-12 sm:px-12 sm:py-16"
            >
                <div class="flex flex-wrap items-center justify-between gap-8">
                    <div class="flex items-center gap-5 sm:gap-6">
                        <div class="relative flex-shrink-0">
                            <div
                                class="flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-[#C2185B] via-[#E91E63] to-[#7B1FA2] p-[3px] shadow-[0_18px_40px_-10px_rgba(233,30,99,0.5)] ring-4 ring-white/80 sm:h-24 sm:w-24"
                            >
                                <div
                                    class="flex h-full w-full items-center justify-center rounded-full bg-white/15 backdrop-blur-xl"
                                >
                                    <span
                                        class="block text-3xl leading-none font-bold text-white drop-shadow-md sm:text-4xl"
                                        v-text="userInitial"
                                    ></span>
                                </div>
                            </div>
                            <span
                                class="absolute -right-1 -bottom-1 flex h-7 w-7 items-center justify-center rounded-full bg-white shadow-lg ring-1 ring-pink-100"
                            >
                                <Sparkles class="h-4 w-4 text-[#E91E63]" />
                            </span>
                        </div>

                        <div class="max-w-xl">
                            <h1
                                class="bg-gradient-to-r from-[#E91E63] via-[#C2185B] to-[#7B1FA2] bg-clip-text text-3xl font-bold tracking-tight text-transparent drop-shadow-[0_1px_10px_rgba(255,255,255,0.95)] sm:text-4xl"
                            >
                                {{ roleInfo.greeting }} {{ userName }}
                            </h1>
                            <div
                                class="mt-2 flex flex-wrap items-center gap-2.5"
                            >
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full border border-rose-200/60 bg-white/85 px-3.5 py-1 text-sm font-semibold text-[#C2185B] shadow-sm backdrop-blur-md"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-[#E91E63]"
                                    />
                                    {{ roleInfo.roleName }}
                                </span>
                                <p
                                    class="text-base font-semibold text-[#4A3F3A] drop-shadow-[0_1px_8px_rgba(255,255,255,0.9)]"
                                >
                                    {{ roleInfo.title }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== TARJETAS DE ESTADÍSTICAS ===================== -->
        <div
            class="relative z-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6 xl:grid-cols-4"
        >
            <div
                v-for="card in cards"
                :key="card.title"
                class="group relative min-h-[112px] overflow-hidden rounded-2xl border border-white/70 bg-gradient-to-r p-5 shadow-[0_12px_35px_-12px_rgba(80,70,90,0.38)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_18px_42px_-12px_rgba(80,70,90,0.48)]"
                :class="[card.gradient, card.shadow]"
            >
                <div
                    class="pointer-events-none absolute -top-10 -right-6 h-28 w-28 rounded-full bg-white/25 blur-xl"
                />
                <div
                    class="relative flex h-full flex-col items-center justify-center gap-1 text-center"
                >
                    <span class="text-sm font-semibold text-slate-700/90">
                        {{ card.title }}
                    </span>
                    <div
                        class="flex items-center justify-center gap-2 text-slate-800"
                    >
                        <component :is="card.icon" class="h-6 w-6" />
                        <span class="text-3xl font-bold tracking-tight">
                            {{ card.value }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== PANELES INFERIORES ===================== -->
        <div class="relative z-10 grid gap-5 lg:grid-cols-2 lg:gap-6">
            <section
                v-for="chart in [
                    {
                        key: 'ventas' as const,
                        title: 'Estadística de Ventas',
                        legend: 'Ganancias',
                        color: '#F7AFC1',
                        fill: '#FDE1E8',
                        icon: TrendingUp,
                        type: 'bars',
                    },
                    {
                        key: 'compras' as const,
                        title: 'Estadística de Compras',
                        legend: 'Ganancia Obtenida',
                        color: '#2DA8F5',
                        fill: '#DDF2FF',
                        icon: ShoppingCart,
                        type: 'line',
                    },
                ]"
                :key="chart.key"
                class="relative overflow-hidden rounded-[1.75rem] border border-white/80 bg-white/80 p-6 shadow-[0_12px_40px_-12px_rgba(214,150,170,0.28)] backdrop-blur-xl"
            >
                <div class="mb-5 flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl text-white shadow-md"
                        :style="{ backgroundColor: chart.color }"
                    >
                        <component :is="chart.icon" class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-[#4A3F3A]">
                            {{ chart.title }}
                        </h3>
                    </div>
                </div>

                <div
                    class="mb-2 flex items-center justify-center gap-2 text-xs text-[#6F656A]"
                >
                    <span
                        class="h-3 w-8 border-2"
                        :style="{
                            backgroundColor: chart.fill,
                            borderColor: chart.color,
                        }"
                    />
                    <span>{{ chart.legend }}</span>
                </div>

                <div class="overflow-x-auto">
                    <svg
                        :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                        class="h-[250px] w-full min-w-[500px]"
                        role="img"
                        :aria-label="chart.title"
                    >
                        <g
                            v-for="level in [0, 0.25, 0.5, 0.75, 1]"
                            :key="level"
                        >
                            <line
                                :x1="chartPadding.left"
                                :x2="chartWidth - chartPadding.right"
                                :y1="
                                    chartPadding.top +
                                    (1 - level) *
                                        (chartHeight -
                                            chartPadding.top -
                                            chartPadding.bottom)
                                "
                                :y2="
                                    chartPadding.top +
                                    (1 - level) *
                                        (chartHeight -
                                            chartPadding.top -
                                            chartPadding.bottom)
                                "
                                stroke="#E7DDE2"
                                stroke-width="1"
                            />
                            <text
                                :x="chartPadding.left - 9"
                                :y="
                                    chartPadding.top +
                                    (1 - level) *
                                        (chartHeight -
                                            chartPadding.top -
                                            chartPadding.bottom) +
                                    4
                                "
                                text-anchor="end"
                                class="fill-[#7D7277] text-[10px] font-medium"
                            >
                                {{
                                    formatAxisValue(
                                        chartMaximum(chart.key) * level,
                                    )
                                }}
                            </text>
                        </g>

                        <polygon
                            v-if="chart.type === 'line'"
                            :points="`${chartPadding.left},${chartHeight - chartPadding.bottom} ${chartPoints(chart.key)} ${chartWidth - chartPadding.right},${chartHeight - chartPadding.bottom}`"
                            :fill="chart.fill"
                            fill-opacity="0.75"
                        />
                        <polyline
                            v-if="chart.type === 'line'"
                            :points="chartPoints(chart.key)"
                            fill="none"
                            :stroke="chart.color"
                            stroke-width="4"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                        <g
                            v-for="(item, index) in estadisticas"
                            :key="`${chart.key}-${item.fecha}`"
                        >
                            <rect
                                v-if="chart.type === 'bars'"
                                :x="chartX(index) - chartBarWidth / 2"
                                :y="chartY(item[chart.key], chart.key)"
                                :width="chartBarWidth"
                                :height="
                                    chartHeight -
                                    chartPadding.bottom -
                                    chartY(item[chart.key], chart.key)
                                "
                                rx="3"
                                :fill="chart.fill"
                                :stroke="chart.color"
                                stroke-width="2"
                            >
                                <title>
                                    {{ item.fecha }}:
                                    {{ formatCurrency(item[chart.key]) }}
                                </title>
                            </rect>
                            <circle
                                v-else
                                :cx="chartX(index)"
                                :cy="chartY(item[chart.key], chart.key)"
                                r="5"
                                fill="white"
                                :stroke="chart.color"
                                stroke-width="3"
                            >
                                <title>
                                    {{ item.fecha }}:
                                    {{ formatCurrency(item[chart.key]) }}
                                </title>
                            </circle>
                            <text
                                v-if="item[chart.key] > 0"
                                :x="chartX(index)"
                                :y="
                                    Math.max(
                                        12,
                                        chartY(item[chart.key], chart.key) - 8,
                                    )
                                "
                                text-anchor="middle"
                                class="fill-[#4A3F3A] text-[10px] font-bold"
                            >
                                Bs. {{ item[chart.key].toFixed(2) }}
                            </text>
                            <text
                                :x="chartX(index)"
                                :y="chartHeight - 14"
                                text-anchor="middle"
                                class="fill-[#8A7B85] text-[11px]"
                            >
                                {{ item.fecha }}
                            </text>
                        </g>
                    </svg>
                </div>
            </section>
        </div>

        <div
            v-if="false"
            class="relative z-10 grid gap-5 lg:grid-cols-2 lg:gap-6"
        >
            <!-- Alertas de Stock -->
            <div
                class="relative overflow-hidden rounded-[1.75rem] border border-white/80 bg-white/75 p-6 shadow-[0_12px_40px_-12px_rgba(214,150,170,0.28)] backdrop-blur-xl transition-all duration-300 hover:shadow-[0_20px_50px_-12px_rgba(214,150,170,0.42)]"
            >
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-28 bg-gradient-to-r from-orange-50/80 via-pink-50/90 to-fuchsia-50/70"
                />
                <div
                    class="pointer-events-none absolute top-6 -right-4 rotate-12 opacity-[0.09]"
                >
                    <Flower2 class="h-24 w-24 text-[#C2185B]" />
                </div>
                <div
                    class="pointer-events-none absolute -right-2 bottom-10 -rotate-12 opacity-[0.08]"
                >
                    <Sprout class="h-20 w-20 text-[#7B1FA2]" />
                </div>

                <div class="relative mb-5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#E91E63] to-[#C2185B] shadow-md shadow-pink-200/50"
                        >
                            <AlertTriangle class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <h3
                                class="bg-gradient-to-r from-[#E91E63] to-[#C2185B] bg-clip-text text-lg font-semibold text-transparent"
                            >
                                Alertas de Stock
                            </h3>
                            <p class="text-xs text-[#8A7B85]">
                                Productos por reponer
                            </p>
                        </div>
                    </div>
                    <Link
                        href="/inventario"
                        class="rounded-full border border-pink-200/80 bg-white/75 px-4 py-1.5 text-xs font-semibold text-[#C2185B] transition-colors hover:bg-pink-50"
                    >
                        Ver todas
                    </Link>
                </div>

                <div v-if="stockAlertas.length > 0" class="relative space-y-3">
                    <div
                        v-for="(alerta, index) in stockAlertas"
                        :key="index"
                        class="flex items-center justify-between rounded-2xl border border-pink-100/80 bg-white/95 px-4 py-3 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-pink-50/60 hover:shadow-md"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-pink-100 to-purple-100 ring-2 ring-white"
                            >
                                <img
                                    v-if="alerta.imagen"
                                    :src="alerta.imagen ?? undefined"
                                    :alt="alerta.producto"
                                    class="h-full w-full object-cover"
                                />
                                <PackageOpen
                                    v-else
                                    class="h-5 w-5 text-[#C2185B]"
                                />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-700">
                                    {{ alerta.producto }}
                                </p>
                                <p class="text-xs text-slate-400">
                                    Cantidad restante
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span
                                :class="
                                    alerta.cantidad <= 5
                                        ? 'bg-rose-100 text-rose-600'
                                        : 'bg-pink-100 text-pink-700'
                                "
                                class="rounded-full px-3 py-1 text-xs font-bold"
                            >
                                {{
                                    alerta.cantidad <= 5
                                        ? 'Stock Crítico'
                                        : 'Stock Bajo'
                                }}
                            </span>
                            <span class="text-xs font-semibold text-slate-500">
                                {{ alerta.cantidad }}
                                {{
                                    alerta.cantidad === 1
                                        ? 'unidad'
                                        : 'unidades'
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="relative flex flex-col items-center justify-center rounded-2xl bg-white/75 px-6 py-8 text-center shadow-sm backdrop-blur-md"
                >
                    <div
                        class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100"
                    >
                        <PackageOpen class="h-8 w-8 text-emerald-500" />
                    </div>
                    <p class="text-sm font-medium text-gray-600">
                        No hay alertas de stock
                    </p>
                    <p class="mt-1 text-xs text-gray-400">
                        Todos los productos tienen inventario suficiente
                    </p>
                </div>
            </div>

            <!-- Ventas Recientes -->
            <div
                class="relative overflow-hidden rounded-[1.75rem] border border-white/80 bg-white/75 p-6 shadow-[0_12px_40px_-12px_rgba(214,150,170,0.28)] backdrop-blur-xl transition-all duration-300 hover:shadow-[0_20px_50px_-12px_rgba(214,150,170,0.42)]"
            >
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-28 bg-gradient-to-r from-purple-50/80 via-fuchsia-50/90 to-pink-50/70"
                />
                <div
                    class="pointer-events-none absolute top-6 -right-4 rotate-12 opacity-[0.09]"
                >
                    <Sprout class="h-24 w-24 text-[#9C27B0]" />
                </div>
                <div
                    class="pointer-events-none absolute -right-2 bottom-10 -rotate-12 opacity-[0.08]"
                >
                    <Flower2 class="h-20 w-20 text-[#C2185B]" />
                </div>

                <div class="relative mb-5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-[#C2185B] to-[#7B1FA2] shadow-md shadow-purple-200/50"
                        >
                            <TrendingUp class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <h3
                                class="bg-gradient-to-r from-[#C2185B] via-[#E91E63] to-[#7B1FA2] bg-clip-text text-lg font-semibold text-transparent"
                            >
                                Ventas Recientes
                            </h3>
                            <p class="text-xs text-[#8A7B85]">
                                Últimos pedidos registrados
                            </p>
                        </div>
                    </div>
                    <Link
                        href="/ventas"
                        class="rounded-full border border-purple-200/80 bg-white/75 px-4 py-1.5 text-xs font-semibold text-[#7B1FA2] transition-colors hover:bg-purple-50"
                    >
                        Ver todas
                    </Link>
                </div>

                <div
                    v-if="ventasRecientes.length > 0"
                    class="relative space-y-3"
                >
                    <div
                        v-for="(venta, index) in ventasRecientes"
                        :key="index"
                        class="flex items-center justify-between rounded-2xl border border-purple-100/80 bg-white/95 px-4 py-3 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-purple-50/60 hover:shadow-md"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-purple-100 to-pink-100"
                            >
                                <ShoppingCart class="h-4 w-4 text-[#7B1FA2]" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-700">
                                    {{ venta.cliente }}
                                </p>
                                <p class="text-xs text-slate-400">
                                    {{ venta.fecha }} &middot;
                                    {{ venta.productos }}
                                </p>
                            </div>
                        </div>
                        <span
                            class="rounded-full bg-gradient-to-r from-pink-100 to-purple-100 px-3 py-1 text-sm font-bold text-[#C2185B]"
                        >
                            {{ formatCurrency(venta.total) }}
                        </span>
                    </div>
                </div>

                <div
                    v-else
                    class="relative flex flex-col items-center justify-center rounded-2xl bg-white/75 px-6 py-8 text-center shadow-sm backdrop-blur-md"
                >
                    <div
                        class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-purple-100"
                    >
                        <TrendingUp class="h-8 w-8 text-purple-400" />
                    </div>
                    <p class="text-sm font-medium text-gray-600">
                        No hay ventas recientes
                    </p>
                    <p class="mt-1 text-xs text-gray-400">
                        Las ventas aparecerán aquí cuando se registren
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dashboard-bg {
    background:
        radial-gradient(
            1100px 700px at 90% -5%,
            rgba(233, 213, 255, 0.5),
            transparent 60%
        ),
        radial-gradient(
            900px 600px at -5% 15%,
            rgba(251, 207, 232, 0.45),
            transparent 60%
        ),
        radial-gradient(
            800px 600px at 105% 95%,
            rgba(252, 165, 165, 0.3),
            transparent 60%
        ),
        linear-gradient(
            160deg,
            #fdf2f8 0%,
            #fce7f3 35%,
            #fdf4ff 65%,
            #f3e8ff 100%
        );
}

.decor-butterfly {
    position: absolute;
    z-index: 0;
    will-change: transform;
    animation: decor-float ease-in-out infinite;
}

.decor-butterfly-svg {
    display: block;
    width: 100%;
    height: 100%;
    animation: decor-flap 0.4s ease-in-out infinite;
    transform-origin: 50% 50%;
    filter: drop-shadow(0 3px 6px rgba(190, 24, 93, 0.12));
}

.decor-sparkle {
    position: absolute;
    z-index: 0;
    color: #f9a8d4;
    animation: decor-twinkle ease-in-out infinite;
}

@keyframes decor-float {
    0%,
    100% {
        transform: translate(0, 0) rotate(-5deg);
    }
    25% {
        transform: translate(16px, -20px) rotate(3deg);
    }
    50% {
        transform: translate(30px, -6px) rotate(-2deg);
    }
    75% {
        transform: translate(8px, 12px) rotate(5deg);
    }
}

@keyframes decor-flap {
    0%,
    100% {
        transform: scaleX(1) scaleY(1);
    }
    25% {
        transform: scaleX(0.4) scaleY(0.95);
    }
    50% {
        transform: scaleX(0.15) scaleY(0.9);
    }
    75% {
        transform: scaleX(0.5) scaleY(0.95);
    }
}

@keyframes decor-twinkle {
    0%,
    100% {
        opacity: 0.35;
        transform: scale(0.85) rotate(0deg);
    }
    50% {
        opacity: 1;
        transform: scale(1.1) rotate(45deg);
    }
}
</style>

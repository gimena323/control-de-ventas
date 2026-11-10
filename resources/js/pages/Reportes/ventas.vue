<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FileDown, ShoppingCart, TrendingUp } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import ManagementHero from '@/components/ManagementHero.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import { compras, ventas } from '@/routes/reportes';
import { vistaPdf as ventasVistaPdf } from '@/routes/reportes/ventas';
import type { Categoria, User } from '@/types';

type DetalleReporte = {
    venta_id: number;
    numero_venta: string;
    fecha: string;
    cliente: string;
    categoria: string;
    marca: string;
    genero: string;
    descripcion: string;
    cantidad: number;
    precio_unitario: number;
    subtotal: number;
};

type ResumenProducto = {
    descripcion: string;
    categoria: string;
    marca: string;
    genero: string;
    cantidad: number;
    stock: number;
    precio_unitario: number;
    subtotal: number;
};

type ResumenCliente = {
    cliente: string;
    total_productos: number;
    total_venta: number;
};

type ReporteVentas = {
    detalles: DetalleReporte[];
    resumen_producto: ResumenProducto[];
    resumen_cliente: ResumenCliente[];
    total_ventas: number;
    total_productos: number;
    monto_total: number;
};

type Props = {
    reporte: ReporteVentas;
    usuarios: User[];
    categorias: Categoria[];
    filtros: {
        fecha_inicio?: string;
        fecha_fin?: string;
        user_id?: string;
        categoria_id?: string;
    };
};

const props = defineProps<Props>();
const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Reporte de Ventas', href: ventas() }],
    },
});

const fechaInicio = ref(props.filtros.fecha_inicio ?? '');
const fechaFin = ref(props.filtros.fecha_fin ?? '');
const userId = ref(props.filtros.user_id ?? '');
const categoriaId = ref(props.filtros.categoria_id ?? '');

function generateReport() {
    const params: Record<string, string> = {};

    if (fechaInicio.value) {
        params.fecha_inicio = fechaInicio.value;
    }

    if (fechaFin.value) {
        params.fecha_fin = fechaFin.value;
    }

    if (userId.value && userId.value !== 'all') {
        params.user_id = userId.value;
    }

    if (categoriaId.value && categoriaId.value !== 'all') {
        params.categoria_id = categoriaId.value;
    }

    router.get(ventas(), params, { preserveState: true, replace: true });
}

function downloadPdf() {
    const params = new URLSearchParams();

    if (fechaInicio.value) {
        params.set('fecha_inicio', fechaInicio.value);
    }

    if (fechaFin.value) {
        params.set('fecha_fin', fechaFin.value);
    }

    if (userId.value && userId.value !== 'all') {
        params.set('user_id', userId.value);
    }

    if (categoriaId.value && categoriaId.value !== 'all') {
        params.set('categoria_id', categoriaId.value);
    }

    router.visit(ventasVistaPdf({ query: Object.fromEntries(params) }));
}

watch([fechaInicio, fechaFin, userId, categoriaId], generateReport);

function clearFilters() {
    fechaInicio.value = '';
    fechaFin.value = '';
    userId.value = '';
    categoriaId.value = '';
}

function formatCurrency(value: string | number): string {
    const num = typeof value === 'string' ? parseFloat(value) : value;

    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
        minimumFractionDigits: 2,
    }).format(num);
}

function getUserName(user: User): string {
    if (user.name) {
        return user.name;
    }

    const parts = [
        user.nombres,
        user.apellido_paterno,
        user.apellido_materno,
    ].filter(Boolean);

    return parts.join(' ') || '---';
}
</script>

<template>
    <Head title="Reporte de Ventas" />

    <div
        class="flex h-full flex-1 flex-col gap-6 rounded-xl bg-gradient-to-br from-violet-100 via-fuchsia-50 to-pink-100 p-4"
    >
        <div
            class="flex flex-wrap items-center gap-2 rounded-2xl border border-pink-100 bg-white/90 p-2 shadow-sm"
        >
            <Button
                v-if="can('reportes.compras.ver')"
                as-child
                variant="ghost"
                class="rounded-xl text-pink-700 hover:bg-pink-50"
            >
                <Link :href="compras()"
                    ><ShoppingCart class="mr-2 h-4 w-4" />Compras</Link
                >
            </Button>
            <Button
                as-child
                class="rounded-xl bg-gradient-to-r from-fuchsia-600 to-purple-600 text-white shadow-sm"
            >
                <Link :href="ventas()"
                    ><TrendingUp class="mr-2 h-4 w-4" />Ventas</Link
                >
            </Button>
        </div>

        <ManagementHero
            title="Reporte de Ventas"
            description="Análisis detallado de las ventas realizadas."
        >
            <template #icon><TrendingUp class="h-7 w-7" /></template>
            <Button
                class="bg-gradient-to-r from-fuchsia-600 to-purple-600 text-white shadow-md hover:opacity-90"
                @click="downloadPdf"
            >
                <FileDown class="mr-2 h-4 w-4" />
                Generar PDF
            </Button>
        </ManagementHero>

        <!-- Filters -->
        <div
            class="overflow-hidden rounded-2xl border border-fuchsia-100 bg-white/90 shadow-sm"
        >
            <div class="border-b px-6 py-4">
                <h3 class="text-base font-semibold">Filtros de Búsqueda</h3>
            </div>
            <div
                class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-4"
            >
                <div class="grid gap-2">
                    <Label>Fecha Inicial</Label>
                    <Input type="date" v-model="fechaInicio" />
                </div>
                <div class="grid gap-2">
                    <Label>Fecha Final</Label>
                    <Input type="date" v-model="fechaFin" :min="fechaInicio" />
                </div>
                <div v-if="usuarios.length > 0" class="grid gap-2">
                    <Label>Usuario</Label>
                    <Select v-model="userId">
                        <SelectTrigger>
                            <SelectValue placeholder="Todos los usuarios" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all"
                                >Todos los usuarios</SelectItem
                            >
                            <SelectItem
                                v-for="u in usuarios"
                                :key="u.id"
                                :value="String(u.id)"
                            >
                                {{ getUserName(u) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div v-else class="grid gap-2">
                    <Label>Vendedora</Label>
                    <div
                        class="flex h-9 items-center rounded-md border border-fuchsia-100 bg-fuchsia-50 px-3 text-sm font-medium text-fuchsia-700"
                    >
                        Mis ventas
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label>Categorías</Label>
                    <Select v-model="categoriaId">
                        <SelectTrigger>
                            <SelectValue placeholder="Todas las categorías" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all"
                                >Todas las categorías</SelectItem
                            >
                            <SelectItem
                                v-for="c in categorias"
                                :key="c.id"
                                :value="String(c.id)"
                            >
                                {{ c.nombre }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <div class="flex gap-2 border-t px-6 py-3">
                <Button variant="outline" @click="clearFilters">
                    Limpiar Filtros
                </Button>
            </div>
        </div>

        <!-- Detail Table -->
        <div
            class="overflow-hidden rounded-2xl border border-fuchsia-100 bg-white/90 shadow-sm"
        >
            <div class="border-b px-4 py-3">
                <h3 class="text-sm font-semibold">
                    Detalle de Productos Vendidos
                </h3>
            </div>

            <div v-if="reporte.resumen_producto.length > 0">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b bg-muted/50 text-left">
                                <th class="px-2 py-2 font-medium">Nro</th>
                                <th class="px-2 py-2 font-medium">
                                    Descripción
                                </th>
                                <th class="px-2 py-2 font-medium">Categoría</th>
                                <th class="px-2 py-2 font-medium">Marca</th>
                                <th class="px-2 py-2 font-medium">Género</th>
                                <th class="px-2 py-2 text-center font-medium">
                                    Cant.
                                </th>
                                <th class="px-2 py-2 text-center font-medium">
                                    Stock
                                </th>
                                <th class="px-2 py-2 text-right font-medium">
                                    P. Unit.
                                </th>
                                <th class="px-2 py-2 text-right font-medium">
                                    Subtotal
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(
                                    producto, index
                                ) in reporte.resumen_producto"
                                :key="producto.descripcion"
                                class="border-b transition-colors last:border-0 hover:bg-muted/30"
                            >
                                <td class="px-2 py-2 text-center">
                                    {{ index + 1 }}
                                </td>
                                <td
                                    class="max-w-[240px] truncate px-2 py-2 font-medium"
                                    :title="producto.descripcion"
                                >
                                    {{ producto.descripcion }}
                                </td>
                                <td class="px-2 py-2">
                                    <Badge variant="outline" class="text-[10px]">{{
                                        producto.categoria
                                    }}</Badge>
                                </td>
                                <td class="px-2 py-2">{{ producto.marca }}</td>
                                <td class="px-2 py-2">
                                    <Badge variant="outline" class="text-[10px]">{{
                                        producto.genero === 'femenino'
                                            ? 'Femenino'
                                            : producto.genero === 'masculino'
                                              ? 'Masculino'
                                              : producto.genero
                                    }}</Badge>
                                </td>
                                <td class="px-2 py-2 text-center font-semibold">
                                    {{ producto.cantidad }}
                                </td>
                                <td class="px-2 py-2 text-center">
                                    {{ producto.stock }}
                                </td>
                                <td class="px-2 py-2 text-right">
                                    {{
                                        formatCurrency(producto.precio_unitario)
                                    }}
                                </td>
                                <td class="px-2 py-2 text-right font-medium">
                                    {{ formatCurrency(producto.subtotal) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else class="px-6 py-14 text-center">
                <div
                    class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-fuchsia-50"
                >
                    <TrendingUp class="h-7 w-7 text-fuchsia-300" />
                </div>
                <p class="text-sm font-medium text-slate-600">
                    No hay ventas registradas para mostrar.
                </p>
                <p class="mt-1 text-xs text-slate-400">
                    Ajusta los filtros de búsqueda o registra nuevas ventas.
                </p>
            </div>
        </div>

        <!-- Summary by Client -->
        <div
            class="overflow-hidden rounded-2xl border border-fuchsia-100 bg-white/90 shadow-sm"
        >
            <div class="border-b px-6 py-4">
                <h3 class="text-base font-semibold">
                    Resumen de Ventas por Cliente
                </h3>
            </div>

            <div v-if="reporte.resumen_cliente.length > 0">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b bg-muted/50 text-left">
                            <th class="px-2 py-2 font-medium">Nro</th>
                            <th class="px-2 py-2 font-medium">Cliente</th>
                            <th class="px-2 py-2 text-center font-medium">
                                Total Productos
                            </th>
                            <th class="px-2 py-2 text-right font-medium">
                                Total Venta
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(resumen, index) in reporte.resumen_cliente"
                            :key="resumen.cliente"
                            class="border-b transition-colors last:border-0 hover:bg-muted/30"
                        >
                            <td class="px-2 py-2 text-center">
                                {{ index + 1 }}
                            </td>
                            <td class="px-2 py-2 font-medium">
                                {{ resumen.cliente }}
                            </td>
                            <td class="px-2 py-2 text-center">
                                <Badge variant="secondary">{{
                                    resumen.total_productos
                                }}</Badge>
                            </td>
                            <td class="px-2 py-2 text-right font-bold">
                                {{ formatCurrency(resumen.total_venta) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="px-6 py-10 text-center">
                <p class="text-sm text-slate-400">
                    Sin datos de clientes para los filtros seleccionados.
                </p>
            </div>
        </div>

        <!-- Totals -->
        <div
            class="overflow-hidden rounded-2xl border border-fuchsia-100 bg-white/90 shadow-sm"
        >
            <div class="border-b px-6 py-4">
                <h3 class="text-base font-semibold">Totales Generales</h3>
            </div>
            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-3">
                <div
                    class="rounded-xl border border-fuchsia-100 bg-gradient-to-br from-fuchsia-50/50 to-white p-5 text-center"
                >
                    <p class="text-xs font-medium text-slate-500">
                        Total de Ventas
                    </p>
                    <p class="mt-1 text-2xl font-bold text-slate-800">
                        {{ reporte.total_ventas }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-fuchsia-100 bg-gradient-to-br from-purple-50/50 to-white p-5 text-center"
                >
                    <p class="text-xs font-medium text-slate-500">
                        Total de Productos Vendidos
                    </p>
                    <p class="mt-1 text-2xl font-bold text-slate-800">
                        {{ reporte.total_productos }}
                        <span class="text-sm font-medium text-slate-500"
                            >productos</span
                        >
                    </p>
                </div>
                <div
                    class="rounded-xl border border-fuchsia-100 bg-gradient-to-br from-rose-50/50 to-white p-5 text-center"
                >
                    <p class="text-xs font-medium text-slate-500">
                        Monto Total Vendido
                    </p>
                    <p class="mt-1 text-2xl font-bold text-slate-800">
                        {{ formatCurrency(reporte.monto_total) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

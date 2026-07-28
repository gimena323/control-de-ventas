<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { BarChart3, FileDown, Search } from 'lucide-vue-next';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
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
import { index } from '@/routes/reportes';
import type { Categoria, User } from '@/types';

type DetalleReporte = {
    venta_id: number;
    numero_venta: string;
    fecha: string;
    cliente: string;
    categoria: string;
    marca: string;
    modelo: string;
    descripcion: string;
    cantidad: number;
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

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Reportes', href: index() }],
    },
});

const fechaInicio = ref(props.filtros.fecha_inicio ?? '');
const fechaFin = ref(props.filtros.fecha_fin ?? '');
const userId = ref(props.filtros.user_id ?? '');
const categoriaId = ref(props.filtros.categoria_id ?? '');

function generateReport() {
    const params: Record<string, string> = {};
    if (fechaInicio.value) params.fecha_inicio = fechaInicio.value;
    if (fechaFin.value) params.fecha_fin = fechaFin.value;
    if (userId.value && userId.value !== 'all') params.user_id = userId.value;
    if (categoriaId.value && categoriaId.value !== 'all') params.categoria_id = categoriaId.value;

    router.get(index(), params, { preserveState: true, replace: true });
}

function downloadPdf() {
    const params = new URLSearchParams();
    if (fechaInicio.value) params.set('fecha_inicio', fechaInicio.value);
    if (fechaFin.value) params.set('fecha_fin', fechaFin.value);
    if (userId.value && userId.value !== 'all') params.set('user_id', userId.value);
    if (categoriaId.value && categoriaId.value !== 'all') params.set('categoria_id', categoriaId.value);

    window.location.href = `/reportes/ventas/pdf?${params.toString()}`;
}

function clearFilters() {
    fechaInicio.value = '';
    fechaFin.value = '';
    userId.value = '';
    categoriaId.value = '';
    generateReport();
}

function formatCurrency(value: string | number): string {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB', minimumFractionDigits: 2 }).format(num);
}

function formatDate(date: string | Date | null): string {
    if (!date) return '---';
    const d = new Date(date);
    const day = String(d.getUTCDate()).padStart(2, '0');
    const month = String(d.getUTCMonth() + 1).padStart(2, '0');
    const year = d.getUTCFullYear();
    return `${day}-${month}-${year}`;
}

function getUserName(user: User): string {
    if (user.name) return user.name;
    const parts = [user.nombres, user.apellido_paterno, user.apellido_materno].filter(Boolean);
    return parts.join(' ') || '---';
}
</script>

<template>
    <Head title="Reporte de Ventas" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <Heading title="Reporte de Ventas" description="Análisis detallado de las ventas realizadas." />
            <Button variant="outline" @click="downloadPdf" :disabled="reporte.detalles.length === 0">
                <FileDown class="mr-2 h-4 w-4" />
                Generar PDF
            </Button>
        </div>

        <!-- Filters -->
        <div class="overflow-hidden rounded-xl border bg-glass">
            <div class="border-b px-6 py-4">
                <h3 class="text-base font-semibold">Filtros de Búsqueda</h3>
            </div>
            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-4">
                <div class="grid gap-2">
                    <Label>Fecha Inicial</Label>
                    <Input type="date" v-model="fechaInicio" />
                </div>
                <div class="grid gap-2">
                    <Label>Fecha Final</Label>
                    <Input type="date" v-model="fechaFin" />
                </div>
                <div class="grid gap-2">
                    <Label>Usuario</Label>
                    <Select v-model="userId">
                        <SelectTrigger>
                            <SelectValue placeholder="Todos los usuarios" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los usuarios</SelectItem>
                            <SelectItem v-for="u in usuarios" :key="u.id" :value="String(u.id)">
                                {{ getUserName(u) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label>Categorías</Label>
                    <Select v-model="categoriaId">
                        <SelectTrigger>
                            <SelectValue placeholder="Todas las categorías" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas las categorías</SelectItem>
                            <SelectItem v-for="c in categorias" :key="c.id" :value="String(c.id)">
                                {{ c.nombre }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <div class="flex gap-2 border-t px-6 py-3">
                <Button @click="generateReport">
                    <Search class="mr-2 h-4 w-4" />
                    Generar Reporte
                </Button>
                <Button variant="outline" @click="clearFilters">
                    Limpiar Filtros
                </Button>
            </div>
        </div>

        <!-- Detail Table -->
        <div class="overflow-hidden rounded-xl border bg-glass">
            <div class="border-b px-6 py-4">
                <h3 class="text-base font-semibold">Detalle Completo de Productos Vendidos</h3>
            </div>

            <div v-if="reporte.detalles.length > 0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-muted/50 border-b text-left">
                                <th class="px-4 py-3 font-medium">Nro</th>
                                <th class="px-4 py-3 font-medium">Fecha</th>
                                <th class="px-4 py-3 font-medium">Cliente</th>
                                <th class="px-4 py-3 font-medium">Categoría</th>
                                <th class="px-4 py-3 font-medium">Marca</th>
                                <th class="px-4 py-3 font-medium">Modelo</th>
                                <th class="px-4 py-3 font-medium">Descripción</th>
                                <th class="px-4 py-3 font-medium text-center">Cantidad</th>
                                <th class="px-4 py-3 font-medium text-right">P. Unitario</th>
                                <th class="px-4 py-3 font-medium text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(detalle, index) in reporte.detalles"
                                :key="`${detalle.venta_id}-${index}`"
                                class="hover:bg-muted/30 border-b transition-colors last:border-0"
                            >
                                <td class="px-4 py-3 text-center">{{ index + 1 }}</td>
                                <td class="px-4 py-3">{{ formatDate(detalle.fecha) }}</td>
                                <td class="px-4 py-3 font-medium">{{ detalle.cliente }}</td>
                                <td class="px-4 py-3">
                                    <Badge variant="outline" class="text-xs">{{ detalle.categoria }}</Badge>
                                </td>
                                <td class="px-4 py-3">{{ detalle.marca }}</td>
                                <td class="px-4 py-3">{{ detalle.modelo }}</td>
                                <td class="px-4 py-3 text-xs">{{ detalle.descripcion }}</td>
                                <td class="px-4 py-3 text-center">{{ detalle.cantidad }}</td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(detalle.precio_unitario) }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ formatCurrency(detalle.subtotal) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else class="px-6 py-12 text-center">
                <BarChart3 class="mx-auto mb-3 h-10 w-10 text-muted-foreground opacity-40" />
                <p class="font-medium text-muted-foreground">No se encontraron ventas.</p>
                <p class="text-muted-foreground text-xs">Intenta ajustar los filtros de búsqueda.</p>
            </div>
        </div>

        <!-- Summary by Client -->
        <div v-if="reporte.resumen_cliente.length > 0" class="overflow-hidden rounded-xl border bg-glass">
            <div class="border-b px-6 py-4">
                <h3 class="text-base font-semibold">Resumen de Ventas por Cliente</h3>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-muted/50 border-b text-left">
                        <th class="px-4 py-3 font-medium">Nro</th>
                        <th class="px-4 py-3 font-medium">Cliente</th>
                        <th class="px-4 py-3 font-medium text-center">Total Productos</th>
                        <th class="px-4 py-3 font-medium text-right">Total Venta</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(resumen, index) in reporte.resumen_cliente"
                        :key="resumen.cliente"
                        class="hover:bg-muted/30 border-b transition-colors last:border-0"
                    >
                        <td class="px-4 py-3 text-center">{{ index + 1 }}</td>
                        <td class="px-4 py-3 font-medium">{{ resumen.cliente }}</td>
                        <td class="px-4 py-3 text-center">
                            <Badge variant="secondary">{{ resumen.total_productos }}</Badge>
                        </td>
                        <td class="px-4 py-3 text-right font-bold">{{ formatCurrency(resumen.total_venta) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div v-if="reporte.detalles.length > 0" class="overflow-hidden rounded-xl border bg-glass">
            <div class="border-b px-6 py-4">
                <h3 class="text-base font-semibold">Totales Generales</h3>
            </div>
            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-3">
                <div class="rounded-lg border p-4 text-center">
                    <p class="text-muted-foreground text-xs">Total de Ventas</p>
                    <p class="text-2xl font-bold">{{ reporte.total_ventas }}</p>
                </div>
                <div class="rounded-lg border p-4 text-center">
                    <p class="text-muted-foreground text-xs">Total de Productos Vendidos</p>
                    <p class="text-2xl font-bold">{{ reporte.total_productos }} productos</p>
                </div>
                <div class="rounded-lg border p-4 text-center">
                    <p class="text-muted-foreground text-xs">Monto Total Vendido</p>
                    <p class="text-2xl font-bold">{{ formatCurrency(reporte.monto_total) }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

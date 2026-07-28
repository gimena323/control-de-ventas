<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Check, Eye, History, Minus, Package, Plus, Printer, Search, ShoppingCart, Trash2, User, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/composables/usePermissions';
import { index, store, anular } from '@/routes/ventas';
import type { Cliente, PaginatedData, Venta } from '@/types';

const { can } = usePermissions();

type ProductoVenta = {
    id: number;
    nombre: string;
    precio_venta: string;
    imagen: string | null;
    stock: number;
    categoria?: { nombre: string };
    marca?: { nombre: string };
    modelo?: { nombre: string };
};

type LineItem = {
    producto_id: number;
    producto: string;
    cantidad: number;
    precio_unitario: number;
    descuento: number;
    subtotal: number;
    stock_disponible: number;
};

type Props = {
    ventas: PaginatedData<Venta>;
    clientes: Cliente[];
    productos: ProductoVenta[];
    filters: { search?: string };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Ventas', href: index() }],
    },
});

const search = ref(props.filters.search ?? '');

watch(search, (value) => {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set('search', value);
    } else {
        url.searchParams.delete('search');
    }
    url.searchParams.delete('page');
    setTimeout(() => {
        window.location.href = url.toString();
    }, 400);
});

function clearSearch() {
    search.value = '';
}

const wizardOpen = ref(false);
const wizardStep = ref(1);
const deleteDialogOpen = ref(false);
const ventaToDelete = ref<Venta | null>(null);
const printDialogOpen = ref(false);
const ventaToPrint = ref<Venta | null>(null);
const productSearch = ref('');

const selectedClienteId = ref('');
const selectedFecha = ref(new Date().toISOString().split('T')[0]);
const selectedDetalles = ref<LineItem[]>([]);

const filteredProductos = computed(() => {
    if (!productSearch.value) return props.productos;
    const s = productSearch.value.toLowerCase();
    return props.productos.filter((p) =>
        p.nombre.toLowerCase().includes(s) ||
        p.categoria?.nombre?.toLowerCase().includes(s) ||
        p.marca?.nombre?.toLowerCase().includes(s) ||
        p.modelo?.nombre?.toLowerCase().includes(s)
    );
});

const selectedCliente = computed(() =>
    props.clientes.find((c) => c.id === Number(selectedClienteId.value))
);

const wizardTotal = computed(() =>
    selectedDetalles.value.reduce((sum, d) => sum + d.subtotal, 0)
);

const wizardTotalDescuento = computed(() =>
    selectedDetalles.value.reduce((sum, d) => sum + d.descuento, 0)
);

const wizardSubtotal = computed(() =>
    selectedDetalles.value.reduce((sum, d) => sum + (d.cantidad * d.precio_unitario), 0)
);

const wizardCantidadTotal = computed(() =>
    selectedDetalles.value.reduce((sum, d) => sum + d.cantidad, 0)
);

function openWizard() {
    wizardStep.value = 1;
    selectedClienteId.value = '';
    selectedFecha.value = new Date().toISOString().split('T')[0];
    selectedDetalles.value = [];
    productSearch.value = '';
    wizardOpen.value = true;
}

function addProduct(product: ProductoVenta) {
    const existing = selectedDetalles.value.find((d) => d.producto_id === product.id);
    if (existing) {
        if (existing.cantidad < product.stock) {
            existing.cantidad++;
            computeLineSubtotal(existing);
        }
        return;
    }
    selectedDetalles.value.push({
        producto_id: product.id,
        producto: product.nombre,
        cantidad: 1,
        precio_unitario: parseFloat(product.precio_venta),
        descuento: 0,
        subtotal: parseFloat(product.precio_venta),
        stock_disponible: product.stock,
    });
}

function removeProduct(index: number) {
    selectedDetalles.value.splice(index, 1);
}

function decrementCantidad(item: LineItem) {
    if (item.cantidad > 1) {
        item.cantidad--;
        computeLineSubtotal(item);
    }
}

function incrementCantidad(item: LineItem) {
    if (item.cantidad < item.stock_disponible) {
        item.cantidad++;
        computeLineSubtotal(item);
    }
}

function computeLineSubtotal(item: LineItem) {
    item.subtotal = Math.max(0, (item.cantidad * item.precio_unitario) - item.descuento);
}

const saleForm = useForm({
    cliente_id: '',
    fecha: '',
    detalles: [] as Array<{
        producto_id: number;
        cantidad: number;
        precio_unitario: number;
        descuento: number;
    }>,
});

function submitSale() {
    saleForm.cliente_id = selectedClienteId.value;
    saleForm.fecha = selectedFecha.value;
    saleForm.detalles = selectedDetalles.value.map((d) => ({
        producto_id: d.producto_id,
        cantidad: d.cantidad,
        precio_unitario: d.precio_unitario,
        descuento: d.descuento,
    }));

    saleForm.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            wizardOpen.value = false;
            saleForm.reset();
        },
    });
}

function goToStep(step: number) {
    if (step === 2 && !selectedClienteId.value) return;
    if (step === 3 && selectedDetalles.value.length === 0) return;
    wizardStep.value = step;
}

function confirmDelete(venta: Venta) {
    ventaToDelete.value = venta;
    deleteDialogOpen.value = true;
}

function submitAnular() {
    if (!ventaToDelete.value) return;
    const id = ventaToDelete.value.id;
    deleteDialogOpen.value = false;
    router.post(anular(id).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            ventaToDelete.value = null;
        },
    });
}

function openPrintDialog(venta: Venta) {
    ventaToPrint.value = venta;
    printDialogOpen.value = true;
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

function getFullName(user: Venta['user']): string {
    if (!user) return '---';
    if (user.name) return user.name;
    const parts = [user.nombres, user.apellido_paterno, user.apellido_materno].filter(Boolean);
    return parts.join(' ') || '---';
}

function getClientFullName(cliente: Venta['cliente']): string {
    if (!cliente) return '---';
    return [cliente.nombre, cliente.apellido].filter(Boolean).join(' ');
}

function getEstadoBadgeClass(estado: string): string {
    if (estado === 'anulada') return 'border-red-200 text-red-600';
    return 'border-green-200 text-green-600';
}

function printComprobante() {
    const printContent = document.getElementById('comprobante-content');
    if (!printContent) return;
    const printWindow = window.open('', '_blank', 'width=400,height=600');
    if (!printWindow) return;
    printWindow.document.write(`
        <html><head><title>Comprobante</title>
        <style>
            body { font-family: monospace; font-size: 12px; padding: 10px; max-width: 350px; margin: 0 auto; }
            .header { text-align: center; margin-bottom: 10px; }
            .header h2 { margin: 0; font-size: 16px; }
            .header p { margin: 2px 0; font-size: 10px; color: #666; }
            .row { display: flex; justify-content: space-between; margin: 3px 0; }
            .border-top { border-top: 1px dashed #000; margin-top: 8px; padding-top: 8px; }
            .border-bottom { border-bottom: 1px dashed #000; margin-bottom: 8px; padding-bottom: 8px; }
            .total { font-size: 14px; font-weight: bold; }
            table { width: 100%; border-collapse: collapse; }
            td, th { padding: 3px 0; text-align: left; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
        </style></head><body>
        ${printContent.innerHTML}
        </body></html>
    `);
    printWindow.document.close();
    printWindow.print();
    printWindow.close();
}
</script>

<template>
    <Head title="Ventas" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <Heading title="Gestión de Ventas" description="Consulta todas las ventas y utiliza el buscador para filtrar." />
            <div class="flex items-center gap-2">
                <Link :href="'/ventas/anuladas'">
                    <Button variant="outline">
                        <History class="mr-2 h-4 w-4" />
                        Movimientos
                    </Button>
                </Link>
                <Button v-if="can('ventas.crear')" @click="openWizard">
                    <Plus class="mr-2 h-4 w-4" />
                    Nueva Venta
                </Button>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative max-w-sm flex-1">
                <Search class="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                <Input v-model="search" placeholder="Buscar por cliente, producto, número de venta o descripción..." class="pl-10" />
            </div>
            <Button variant="outline" @click="clearSearch">
                Limpiar
            </Button>
        </div>

        <div class="overflow-hidden rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-muted/50 border-b text-left">
                        <th class="px-4 py-3 font-medium">N° Venta</th>
                        <th class="px-4 py-3 font-medium">Cliente</th>
                        <th class="px-4 py-3 font-medium">Productos</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Fecha</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="venta in ventas.data"
                        :key="venta.id"
                        class="hover:bg-muted/30 border-b transition-colors last:border-0"
                    >
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs">{{ venta.numero_venta }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
                                    <User class="text-primary h-4 w-4" />
                                </div>
                                <span class="font-medium">{{ getClientFullName(venta.cliente) }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <Badge variant="secondary">{{ venta.detalles?.length ?? 0 }} producto(s)</Badge>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-medium">{{ formatCurrency(venta.total) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs">{{ formatDate(venta.fecha) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <Badge :class="['text-xs', getEstadoBadgeClass(venta.estado)]" variant="outline">
                                {{ venta.estado === 'anulada' ? 'Anulada' : 'Completada' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="icon-sm" class="border-primary/20 text-primary hover:bg-primary/10" @click="router.visit(`/ventas/${venta.id}`)">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p>Ver venta</p></TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="icon-sm" class="border-amber-200 text-amber-600 hover:bg-amber-50" @click="openPrintDialog(venta)">
                                                <Printer class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p>Imprimir</p></TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider v-if="can('ventas.crear') && venta.estado !== 'anulada'" :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="icon-sm" class="border-red-200 text-red-500 hover:bg-red-50" @click="confirmDelete(venta)">
                                                <X class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p>Anular venta</p></TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="ventas.data.length === 0">
                        <td colspan="7" class="py-12 text-center text-sm text-muted-foreground">
                            <ShoppingCart class="mx-auto mb-2 h-8 w-8 opacity-40" />
                            <p class="font-medium">No hay ventas registradas.</p>
                            <p>Crea una nueva venta para comenzar.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="ventas.last_page > 1" class="flex items-center justify-between">
            <p class="text-muted-foreground text-sm">
                Mostrando {{ ventas.from }}–{{ ventas.to }} de {{ ventas.total }} ventas
            </p>
            <div class="flex items-center gap-2">
                <Link
                    :href="ventas.prev_page_url ?? '#'"
                    :class="['rounded-lg border px-3 py-1.5 text-sm transition-colors', ventas.prev_page_url ? 'hover:bg-muted' : 'pointer-events-none opacity-40']"
                >
                    Anterior
                </Link>
                <Link
                    :href="ventas.next_page_url ?? '#'"
                    :class="['rounded-lg border px-3 py-1.5 text-sm transition-colors', ventas.next_page_url ? 'hover:bg-muted' : 'pointer-events-none opacity-40']"
                >
                    Siguiente
                </Link>
            </div>
        </div>
    </div>

    <!-- ═══════ WIZARD NUEVA VENTA ═══════ -->
    <Dialog v-model:open="wizardOpen">
        <DialogContent class="sm:max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Nueva Venta</DialogTitle>
                <DialogDescription>
                    <span v-if="wizardStep === 1">Selecciona el cliente para la venta.</span>
                    <span v-else-if="wizardStep === 2">Selecciona los productos que deseas vender.</span>
                    <span v-else>Revisa el resumen antes de registrar la venta.</span>
                </DialogDescription>
            </DialogHeader>

            <!-- Steps indicator -->
            <div class="flex items-center justify-center gap-4 py-2">
                <div class="flex items-center gap-2">
                    <div :class="['flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold', wizardStep >= 1 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground']">1</div>
                    <span class="text-xs font-medium">Cliente</span>
                </div>
                <div class="h-px w-8 bg-border" />
                <div class="flex items-center gap-2">
                    <div :class="['flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold', wizardStep >= 2 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground']">2</div>
                    <span class="text-xs font-medium">Productos</span>
                </div>
                <div class="h-px w-8 bg-border" />
                <div class="flex items-center gap-2">
                    <div :class="['flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold', wizardStep >= 3 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground']">3</div>
                    <span class="text-xs font-medium">Resumen</span>
                </div>
            </div>

            <!-- STEP 1: Select Client -->
            <div v-if="wizardStep === 1" class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="cliente_id">Cliente *</Label>
                    <Select v-model="selectedClienteId">
                        <SelectTrigger>
                            <SelectValue placeholder="Seleccionar cliente" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="0">Cliente General</SelectItem>
                            <SelectItem v-for="cliente in clientes" :key="cliente.id" :value="String(cliente.id)">
                                {{ cliente.nombre }}{{ cliente.apellido ? ' ' + cliente.apellido : '' }} — CI/NIT: {{ cliente.ci_nit }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label for="fecha">Fecha *</Label>
                    <Input id="fecha" type="date" v-model="selectedFecha" />
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button :disabled="!selectedClienteId" @click="goToStep(2)">
                        Siguiente
                        <ArrowRight class="ml-2 h-4 w-4" />
                    </Button>
                </DialogFooter>
            </div>

            <!-- STEP 2: Select Products -->
            <div v-if="wizardStep === 2" class="grid gap-4 py-4">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <Search class="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                        <Input v-model="productSearch" placeholder="Buscar por nombre de la prenda, modelo, marca, categoría o descripción..." class="pl-10" />
                    </div>
                </div>

                <div v-if="props.productos.length === 0" class="py-8 text-center">
                    <Package class="mx-auto mb-3 h-10 w-10 text-muted-foreground opacity-40" />
                    <p class="font-medium text-muted-foreground">No se encontraron productos.</p>
                    <p class="text-muted-foreground text-xs">Primero registre productos en el módulo Gestión de Productos para poder realizar ventas.</p>
                </div>

                <div v-else-if="filteredProductos.length === 0 && productSearch" class="py-8 text-center">
                    <Package class="mx-auto mb-3 h-10 w-10 text-muted-foreground opacity-40" />
                    <p class="text-muted-foreground text-sm">No se encontraron productos para "{{ productSearch }}".</p>
                </div>

                <div v-else class="max-h-[300px] overflow-y-auto rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-background">
                            <tr class="border-b text-left">
                                <th class="px-3 py-2 font-medium">Producto</th>
                                <th class="px-3 py-2 font-medium">Categoría</th>
                                <th class="px-3 py-2 font-medium">Marca</th>
                                <th class="px-3 py-2 font-medium text-right">Precio</th>
                                <th class="px-3 py-2 font-medium text-center">Stock</th>
                                <th class="px-3 py-2 font-medium text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="producto in filteredProductos"
                                :key="producto.id"
                                class="hover:bg-muted/30 border-b text-left transition-colors last:border-0"
                            >
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <div v-if="producto.imagen" class="h-8 w-8 overflow-hidden rounded bg-muted">
                                            <img :src="`/storage/${producto.imagen}`" :alt="producto.nombre" class="h-full w-full object-cover" />
                                        </div>
                                        <div v-else class="flex h-8 w-8 items-center justify-center rounded bg-muted">
                                            <Package class="text-muted-foreground h-4 w-4" />
                                        </div>
                                        <div>
                                            <p class="font-medium">{{ producto.nombre }}</p>
                                            <p v-if="producto.modelo" class="text-muted-foreground text-xs">{{ producto.modelo.nombre }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-xs">{{ producto.categoria?.nombre ?? '---' }}</td>
                                <td class="px-3 py-2 text-xs">{{ producto.marca?.nombre ?? '---' }}</td>
                                <td class="px-3 py-2 text-right text-xs font-medium">{{ formatCurrency(producto.precio_venta) }}</td>
                                <td class="px-3 py-2 text-center">
                                    <Badge :class="['text-xs', producto.stock > 0 ? 'border-green-200 text-green-600' : 'border-red-200 text-red-600']" variant="outline">
                                        {{ producto.stock }}
                                    </Badge>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :disabled="producto.stock <= 0"
                                        @click="addProduct(producto)"
                                    >
                                        <Plus class="mr-1 h-3 w-3" />
                                        Agregar
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Selected items -->
                <div v-if="selectedDetalles.length > 0" class="rounded-lg border p-3">
                    <p class="mb-2 text-xs font-medium">Productos seleccionados ({{ selectedDetalles.length }})</p>
                    <div v-for="(item, index) in selectedDetalles" :key="item.producto_id" class="flex items-center gap-3 border-b py-2 last:border-0">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ item.producto }}</p>
                            <p class="text-muted-foreground text-xs">P.U.: {{ formatCurrency(item.precio_unitario) }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <Button variant="outline" size="icon" class="h-7 w-7" @click="decrementCantidad(item)">
                                <Minus class="h-3 w-3" />
                            </Button>
                            <Input
                                v-model.number="item.cantidad"
                                type="number"
                                min="1"
                                :max="item.stock_disponible"
                                class="h-7 w-14 text-center text-xs"
                                @update:model-value="computeLineSubtotal(item)"
                            />
                            <Button variant="outline" size="icon" class="h-7 w-7" @click="incrementCantidad(item)" :disabled="item.cantidad >= item.stock_disponible">
                                <Plus class="h-3 w-3" />
                            </Button>
                        </div>
                        <div class="w-24">
                            <Input
                                v-model.number="item.descuento"
                                type="number"
                                min="0"
                                class="h-7 text-right text-xs"
                                placeholder="Desc."
                                @update:model-value="computeLineSubtotal(item)"
                            />
                        </div>
                        <span class="w-24 text-right text-sm font-medium">{{ formatCurrency(item.subtotal) }}</span>
                        <Button variant="ghost" size="icon" class="h-7 w-7 text-red-500" @click="removeProduct(index)">
                            <Trash2 class="h-3 w-3" />
                        </Button>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="goToStep(1)">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Atrás
                    </Button>
                    <Button :disabled="selectedDetalles.length === 0" @click="goToStep(3)">
                        Siguiente
                        <ArrowRight class="ml-2 h-4 w-4" />
                    </Button>
                </DialogFooter>
            </div>

            <!-- STEP 3: Summary -->
            <div v-if="wizardStep === 3" class="grid gap-4 py-4">
                <div class="rounded-lg border p-4">
                    <h4 class="mb-3 text-sm font-semibold">Resumen de la Venta</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Cliente:</span>
                            <span class="font-medium">
                                {{ selectedCliente ? `${selectedCliente.nombre}${selectedCliente.apellido ? ' ' + selectedCliente.apellido : ''}` : 'Cliente General' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Fecha:</span>
                            <span class="font-medium">{{ formatDate(selectedFecha) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Productos:</span>
                            <span class="font-medium">{{ selectedDetalles.length }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Cantidad total:</span>
                            <span class="font-medium">{{ wizardCantidadTotal }}</span>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-muted/50 border-b text-left">
                                <th class="px-3 py-2 font-medium">Producto</th>
                                <th class="px-3 py-2 font-medium text-center">Cant.</th>
                                <th class="px-3 py-2 font-medium text-right">P. Unit.</th>
                                <th class="px-3 py-2 font-medium text-right">Descuento</th>
                                <th class="px-3 py-2 font-medium text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in selectedDetalles" :key="item.producto_id" class="border-b last:border-0">
                                <td class="px-3 py-2 font-medium">{{ item.producto }}</td>
                                <td class="px-3 py-2 text-center">{{ item.cantidad }}</td>
                                <td class="px-3 py-2 text-right">{{ formatCurrency(item.precio_unitario) }}</td>
                                <td class="px-3 py-2 text-right">{{ formatCurrency(item.descuento) }}</td>
                                <td class="px-3 py-2 text-right font-medium">{{ formatCurrency(item.subtotal) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="space-y-2 border-t pt-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Subtotal:</span>
                        <span>{{ formatCurrency(wizardSubtotal) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Descuento total:</span>
                        <span>-{{ formatCurrency(wizardTotalDescuento) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold">
                        <span>Total General:</span>
                        <span>{{ formatCurrency(wizardTotal) }}</span>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="goToStep(2)">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Atrás
                    </Button>
                    <Button :disabled="saleForm.processing || selectedDetalles.length === 0" @click="submitSale">
                        <Check class="mr-2 h-4 w-4" />
                        {{ saleForm.processing ? 'Registrando...' : 'Registrar Venta' }}
                    </Button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>

    <!-- ═══════ CONFIRM ANULAR ═══════ -->
    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Anular Venta</DialogTitle>
                <DialogDescription>
                    ¿Está seguro de anular la venta <strong>{{ ventaToDelete?.numero_venta }}</strong>? Se devolverá automáticamente el stock de todos los productos vendidos.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cancelar</Button>
                </DialogClose>
                <Button variant="destructive" @click="submitAnular">Anular Venta</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- ═══════ PRINT COMPROBANTE ═══════ -->
    <Dialog v-model:open="printDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Comprobante de Venta</DialogTitle>
                <DialogDescription v-if="ventaToPrint">
                    Comprobante de la venta <strong>{{ ventaToPrint.numero_venta }}</strong>.
                </DialogDescription>
            </DialogHeader>
            <div v-if="ventaToPrint" id="comprobante-content" class="rounded-lg border p-4">
                <div class="mb-4 text-center">
                    <h3 class="text-lg font-bold">EMMA FASHION</h3>
                    <p class="text-xs text-muted-foreground">COMPROBANTE DE VENTA</p>
                </div>
                <div class="mb-4 space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">N°:</span>
                        <span class="font-mono font-medium">{{ ventaToPrint.numero_venta }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Fecha:</span>
                        <span>{{ formatDate(ventaToPrint.fecha) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Cliente:</span>
                        <span>{{ getClientFullName(ventaToPrint.cliente) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Atendido por:</span>
                        <span>{{ getFullName(ventaToPrint.user) }}</span>
                    </div>
                </div>
                <div v-if="ventaToPrint.detalles?.length" class="mb-2 border-t pt-2">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b">
                                <th class="py-1 text-left font-medium">Producto</th>
                                <th class="py-1 text-center font-medium">Cant</th>
                                <th class="py-1 text-right font-medium">P.U.</th>
                                <th class="py-1 text-right font-medium">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="detalle in ventaToPrint.detalles" :key="detalle.id" class="border-b">
                                <td class="py-1">{{ detalle.producto }}</td>
                                <td class="py-1 text-center">{{ detalle.cantidad }}</td>
                                <td class="py-1 text-right">{{ formatCurrency(detalle.precio_unitario) }}</td>
                                <td class="py-1 text-right font-medium">{{ formatCurrency(detalle.subtotal) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t pt-2">
                    <div class="flex justify-between text-base font-bold">
                        <span>Total:</span>
                        <span>{{ formatCurrency(ventaToPrint.total) }}</span>
                    </div>
                </div>
            </div>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cerrar</Button>
                </DialogClose>
                <Button @click="printComprobante">
                    <Printer class="mr-2 h-4 w-4" /> Imprimir
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

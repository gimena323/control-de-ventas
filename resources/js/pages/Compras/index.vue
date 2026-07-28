<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Printer, Search, ShoppingCart, Trash2, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { index, store, update, destroy } from '@/routes/compras';
import type { Compra, PaginatedData, Proveedor } from '@/types';

const { can } = usePermissions();

type Props = {
    compras: PaginatedData<Compra>;
    proveedores: Proveedor[];
    filters: { search?: string };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Compras',
                href: index(),
            },
        ],
    },
});

const search = ref(props.filters.search ?? '');

watch(
    search,
    (value) => {
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
    },
);

function clearSearch() {
    search.value = '';
}

type LineItem = {
    producto: string;
    cantidad: number;
    precio_unitario: number;
    subtotal: number;
};

const emptyLineItem = (): LineItem => ({
    producto: '',
    cantidad: 1,
    precio_unitario: 0,
    subtotal: 0,
});

function computeSubtotal(item: LineItem): void {
    item.subtotal = (item.cantidad || 0) * (item.precio_unitario || 0);
}

const createForm = useForm({
    proveedor_id: '',
    fecha: '',
    detalles: [] as LineItem[],
});

const editForm = useForm({
    proveedor_id: '',
    fecha: '',
    detalles: [] as LineItem[],
});

const editingCompra = ref<Compra | null>(null);
const viewingCompra = ref<Compra | null>(null);
const formDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const viewDialogOpen = ref(false);
const printDialogOpen = ref(false);
const compraToDelete = ref<Compra | null>(null);
const compraToPrint = ref<Compra | null>(null);

const isEditing = computed(() => !!editingCompra.value);
const dialogTitle = computed(() => (isEditing.value ? 'Editar Compra' : 'Nueva Compra'));

const form = computed(() => (isEditing.value ? editForm : createForm));

const formTotal = computed(() =>
    form.value.detalles.reduce((sum, item) => sum + (item.subtotal || 0), 0),
);

function addLineItem(): void {
    form.value.detalles.push(emptyLineItem());
}

function removeLineItem(index: number): void {
    form.value.detalles.splice(index, 1);
}

function openCreateDialog() {
    editingCompra.value = null;
    createForm.reset();
    createForm.detalles = [emptyLineItem()];
    formDialogOpen.value = true;
}

function openViewDialog(compra: Compra) {
    viewingCompra.value = compra;
    viewDialogOpen.value = true;
}

function openEditDialog(compra: Compra) {
    editingCompra.value = compra;
    editForm.proveedor_id = String(compra.proveedor_id);
    editForm.fecha = compra.fecha;
    editForm.detalles = (compra.detalles ?? []).map((d) => ({
        producto: d.producto,
        cantidad: Number(d.cantidad),
        precio_unitario: Number(d.precio_unitario),
        subtotal: Number(d.subtotal),
    }));
    if (editForm.detalles.length === 0) {
        editForm.detalles = [emptyLineItem()];
    }
    formDialogOpen.value = true;
}

function openPrintDialog(compra: Compra) {
    compraToPrint.value = compra;
    printDialogOpen.value = true;
}

function submitCreate() {
    createForm.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            formDialogOpen.value = false;
            createForm.reset();
        },
    });
}

function submitUpdate() {
    if (!editingCompra.value) return;
    editForm.patch(update(editingCompra.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            formDialogOpen.value = false;
            editingCompra.value = null;
            editForm.reset();
        },
    });
}

function confirmDelete(compra: Compra) {
    compraToDelete.value = compra;
    deleteDialogOpen.value = true;
}

function submitDelete() {
    if (!compraToDelete.value) return;
    const id = compraToDelete.value.id;
    deleteDialogOpen.value = false;
    router.delete(destroy(id).url, {
        preserveScroll: true,
        onSuccess: () => {
            compraToDelete.value = null;
        },
    });
}

function formatCurrency(value: string | number): string {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB', minimumFractionDigits: 2 }).format(num);
}

function formatDate(date: string | Date | null): string {
    if (!date) return '—';
    const d = new Date(date);
    const day = String(d.getUTCDate()).padStart(2, '0');
    const month = String(d.getUTCMonth() + 1).padStart(2, '0');
    const year = d.getUTCFullYear();
    return `${day}-${month}-${year}`;
}

function getFullName(user: Compra['user']): string {
    if (!user) return '—';
    if (user.name) return user.name;
    const parts = [user.nombres, user.apellido_paterno, user.apellido_materno].filter(Boolean);
    return parts.join(' ') || '—';
}
</script>

<template>
    <Head title="Compras" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <Heading title="Gestión de Compras" description="Administra las compras del sistema." />
            <Button v-if="can('compras.crear')" @click="openCreateDialog">
                <Plus class="mr-2 h-4 w-4" />
                Nueva Compra
            </Button>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative max-w-sm flex-1">
                <Search class="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                <Input v-model="search" placeholder="Buscar por nombre de proveedor o ID de compra..." class="pl-10" />
            </div>
            <Button variant="outline" @click="clearSearch">
                Limpiar
            </Button>
        </div>

        <div class="overflow-hidden rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-muted/50 border-b text-left">
                        <th class="px-4 py-3 font-medium">ID</th>
                        <th class="px-4 py-3 font-medium">Proveedor</th>
                        <th class="px-4 py-3 font-medium">Fecha</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Usuario</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="compra in compras.data"
                        :key="compra.id"
                        class="hover:bg-muted/30 border-b transition-colors last:border-0"
                    >
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs">{{ compra.numero_compra }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
                                    <ShoppingCart class="text-primary h-4 w-4" />
                                </div>
                                <div>
                                    <p class="font-medium">{{ compra.proveedor?.nombre ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
<td class="px-4 py-3">
    <span class="text-xs">{{ formatDate(compra.fecha) }}</span>
</td>
                        <td class="px-4 py-3">
                            <span class="font-medium">{{ formatCurrency(compra.total) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs">{{ getFullName(compra.user) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="icon-sm" class="border-primary/20 text-primary hover:bg-primary/10" @click="openViewDialog(compra)">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Ver compra</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider v-if="can('compras.editar')" :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="icon-sm" class="border-primary/20 text-primary hover:bg-primary/10" @click="openEditDialog(compra)">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Editar compra</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="icon-sm" class="border-amber-200 text-amber-600 hover:bg-amber-50" @click="openPrintDialog(compra)">
                                                <Printer class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Imprimir comprobante</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider v-if="can('compras.eliminar')" :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="icon-sm" class="border-red-200 text-red-500 hover:bg-red-50" @click="confirmDelete(compra)">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Eliminar compra</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="compras.data.length === 0">
                        <td colspan="6" class="py-12 text-center text-sm text-muted-foreground">
                            <ShoppingCart class="mx-auto mb-2 h-8 w-8 opacity-40" />
                            No se encontraron compras.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="compras.last_page > 1" class="flex items-center justify-between">
            <p class="text-muted-foreground text-sm">
                Mostrando {{ compras.from }}–{{ compras.to }} de {{ compras.total }} compras
            </p>
            <div class="flex items-center gap-2">
                <Link
                    :href="compras.prev_page_url ?? '#'"
                    :class="['rounded-lg border px-3 py-1.5 text-sm transition-colors', compras.prev_page_url ? 'hover:bg-muted' : 'pointer-events-none opacity-40']"
                >
                    Anterior
                </Link>
                <Link
                    :href="compras.next_page_url ?? '#'"
                    :class="['rounded-lg border px-3 py-1.5 text-sm transition-colors', compras.next_page_url ? 'hover:bg-muted' : 'pointer-events-none opacity-40']"
                >
                    Siguiente
                </Link>
            </div>
        </div>
    </div>

    <Dialog v-model:open="formDialogOpen">
        <DialogContent class="sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>{{ dialogTitle }}</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="isEditing ? submitUpdate() : submitCreate()">
                <div class="grid gap-6 py-4">
                    <div>
                        <h3 class="mb-3 text-base font-semibold">Información de la Compra</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="proveedor_id">Proveedor *</Label>
                                <Select v-model="form.proveedor_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar proveedor" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="proveedor in proveedores"
                                            :key="proveedor.id"
                                            :value="String(proveedor.id)"
                                        >
                                            {{ proveedor.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.proveedor_id" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="fecha">Fecha *</Label>
                                <Input
                                    id="fecha"
                                    type="date"
                                    v-model="form.fecha"
                                />
                                <InputError :message="form.errors.fecha" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 text-base font-semibold">Productos</h3>
                        <div class="overflow-hidden rounded-lg border">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-muted/50 border-b text-left">
                                        <th class="w-8 px-3 py-2"></th>
                                        <th class="px-3 py-2 font-medium">Producto</th>
                                        <th class="w-24 px-3 py-2 font-medium text-center">Cantidad</th>
                                        <th class="w-32 px-3 py-2 font-medium text-right">Precio Unitario</th>
                                        <th class="w-28 px-3 py-2 font-medium text-right">Total Parcial</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, index) in form.detalles"
                                        :key="index"
                                        class="hover:bg-muted/30 border-b text-left transition-colors last:border-0"
                                    >
                                        <td class="px-3 py-2">
                                            <button
                                                type="button"
                                                class="text-muted-foreground hover:text-red-500 transition-colors"
                                                @click="removeLineItem(index)"
                                            >
                                                <X class="h-4 w-4" />
                                            </button>
                                        </td>
                                        <td class="px-3 py-2">
                                            <Input
                                                v-model="item.producto"
                                                placeholder="Nombre del producto"
                                                class="h-9"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <Input
                                                v-model.number="item.cantidad"
                                                type="number"
                                                min="1"
                                                class="h-9 text-center"
                                                @update:model-value="computeSubtotal(item)"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <Input
                                                v-model.number="item.precio_unitario"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                class="h-9 text-right"
                                                @update:model-value="computeSubtotal(item)"
                                            />
                                        </td>
                                        <td class="px-3 py-2 text-right font-medium">
                                            {{ formatCurrency(item.subtotal) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Button type="button" variant="outline" size="sm" class="mt-2" @click="addLineItem">
                            <Plus class="mr-1 h-4 w-4" />
                            Agregar Producto
                        </Button>
                        <InputError :message="form.errors.detalles" />
                    </div>

                    <div class="flex items-center justify-end border-t pt-4">
                        <span class="text-lg font-bold">Total: {{ formatCurrency(formTotal) }}</span>
                    </div>
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEditing ? 'Guardar Cambios' : 'Crear Compra' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Eliminar Compra</DialogTitle>
                <DialogDescription>
                    ¿Estás seguro de eliminar la compra <strong>{{ compraToDelete?.numero_compra }}</strong>? Esta acción no se puede deshacer.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cancelar</Button>
                </DialogClose>
                <Button variant="destructive" @click="submitDelete">
                    Eliminar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="viewDialogOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Detalle de Compra</DialogTitle>
                <DialogDescription v-if="viewingCompra">
                    Información completa de la compra <strong>{{ viewingCompra.numero_compra }}</strong>.
                </DialogDescription>
            </DialogHeader>
            <div v-if="viewingCompra" class="grid gap-4 py-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label class="text-muted-foreground text-xs">N° Compra</Label>
                        <p class="text-sm font-medium font-mono">{{ viewingCompra.numero_compra }}</p>
                    </div>
<div>
    <Label class="text-muted-foreground text-xs">Fecha</Label>
    <p class="text-sm font-medium">{{ formatDate(viewingCompra.fecha) }}</p>
</div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label class="text-muted-foreground text-xs">Proveedor</Label>
                        <p class="text-sm font-medium">{{ viewingCompra.proveedor?.nombre ?? '—' }}</p>
                    </div>
                    <div>
                        <Label class="text-muted-foreground text-xs">Total</Label>
                        <p class="text-sm font-medium">{{ formatCurrency(viewingCompra.total) }}</p>
                    </div>
                </div>
                <div>
                    <Label class="text-muted-foreground text-xs">Registrado por</Label>
                    <p class="text-sm font-medium">{{ getFullName(viewingCompra.user) }}</p>
                </div>
                <div v-if="viewingCompra.detalles && viewingCompra.detalles.length > 0">
                    <Label class="text-muted-foreground text-xs mb-2 block">Productos</Label>
                    <div class="overflow-hidden rounded-lg border">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-muted/50 border-b text-left">
                                    <th class="px-3 py-2 font-medium">Producto</th>
                                    <th class="px-3 py-2 font-medium text-center">Cantidad</th>
                                    <th class="px-3 py-2 font-medium text-right">P. Unitario</th>
                                    <th class="px-3 py-2 font-medium text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="detalle in viewingCompra.detalles" :key="detalle.id" class="border-b text-left last:border-0">
                                    <td class="px-3 py-2 text-sm font-medium">{{ detalle.producto }}</td>
                                    <td class="px-3 py-2 text-center text-sm">{{ detalle.cantidad }}</td>
                                    <td class="px-3 py-2 text-right text-sm">{{ formatCurrency(detalle.precio_unitario) }}</td>
                                    <td class="px-3 py-2 text-right text-sm font-medium">{{ formatCurrency(detalle.subtotal) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-if="viewingCompra.observaciones">
                    <Label class="text-muted-foreground text-xs">Observaciones</Label>
                    <p class="text-sm font-medium">{{ viewingCompra.observaciones }}</p>
                </div>
            </div>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cerrar</Button>
                </DialogClose>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="printDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Comprobante de Compra</DialogTitle>
                <DialogDescription v-if="compraToPrint">
                    Comprobante de la compra <strong>{{ compraToPrint.numero_compra }}</strong>.
                </DialogDescription>
            </DialogHeader>
            <div v-if="compraToPrint" class="grid gap-4 py-4">
                <div class="rounded-lg border p-4">
                    <div class="mb-4 text-center">
                        <h3 class="text-lg font-bold">EMMA FASHION</h3>
                        <p class="text-muted-foreground text-xs">COMPROBANTE DE COMPRA</p>
                    </div>
                    <div class="mb-4 space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">N°:</span>
                            <span class="font-mono font-medium">{{ compraToPrint.numero_compra }}</span>
                        </div>
<div class="flex justify-between">
    <span class="text-muted-foreground">Fecha:</span>
    <span>{{ formatDate(compraToPrint.fecha) }}</span>
</div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Proveedor:</span>
                            <span>{{ compraToPrint.proveedor?.nombre ?? '—' }}</span>
                        </div>
                    </div>
                    <div v-if="compraToPrint.detalles && compraToPrint.detalles.length > 0" class="border-t pt-2 mb-2">
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
                                <tr v-for="detalle in compraToPrint.detalles" :key="detalle.id" class="border-b">
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
                            <span>{{ formatCurrency(compraToPrint.total) }}</span>
                        </div>
                    </div>
                    <div v-if="compraToPrint.observaciones" class="mt-2 border-t pt-2 text-xs text-muted-foreground">
                        {{ compraToPrint.observaciones }}
                    </div>
                </div>
            </div>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cerrar</Button>
                </DialogClose>
                <Button @click="window.print()">
                    <Printer class="mr-2 h-4 w-4" />
                    Imprimir
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

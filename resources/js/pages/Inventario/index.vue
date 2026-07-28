<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowUpDown,
    BarChart3,
    Eye,
    FileText,
    Minus,
    Package2,
    Plus,
    Search,
    Trash2,
} from 'lucide-vue-next';
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
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/composables/usePermissions';
import { index, store, update, destroy, toggleDisponible, entrada, salida, movimientos } from '@/routes/inventario';
import type { Inventario, InventarioMovimiento, PaginatedData } from '@/types';

const { can } = usePermissions();

type Props = {
    inventario: PaginatedData<Inventario>;
    filters: { search?: string };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Inventario de Ropa',
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

const entradaForm = useForm({ cantidad: 0, motivo: '' });
const salidaForm = useForm({ cantidad: 0, motivo: '' });

const inventarioToDelete = ref<Inventario | null>(null);
const deleteDialogOpen = ref(false);

const selectedInventario = ref<Inventario | null>(null);
const entradaDialogOpen = ref(false);
const salidaDialogOpen = ref(false);

const movimientosDialogOpen = ref(false);
const movimientosList = ref<InventarioMovimiento[]>([]);
const loadingMovimientos = ref(false);

function openEntradaDialog(item: Inventario) {
    selectedInventario.value = item;
    entradaForm.reset();
    entradaForm.cantidad = 0;
    entradaForm.motivo = '';
    entradaDialogOpen.value = true;
}

function openSalidaDialog(item: Inventario) {
    selectedInventario.value = item;
    salidaForm.reset();
    salidaForm.cantidad = 0;
    salidaForm.motivo = '';
    salidaDialogOpen.value = true;
}

function submitEntrada() {
    if (!selectedInventario.value) return;
    entradaForm.post(entrada(selectedInventario.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            entradaDialogOpen.value = false;
            selectedInventario.value = null;
        },
    });
}

function submitSalida() {
    if (!selectedInventario.value) return;
    salidaForm.post(salida(selectedInventario.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            salidaDialogOpen.value = false;
            selectedInventario.value = null;
        },
    });
}

function toggleDisponibleItem(item: Inventario) {
    router.patch(toggleDisponible(item.id).url, {
        preserveScroll: true,
    });
}

function openMovimientos(item: Inventario) {
    selectedInventario.value = item;
    movimientosDialogOpen.value = true;
    loadingMovimientos.value = true;
    movimientosList.value = [];

    fetch(movimientos(item.id).url)
        .then((res) => res.json())
        .then((data) => {
            movimientosList.value = data.movimientos ?? [];
            loadingMovimientos.value = false;
        })
        .catch(() => {
            loadingMovimientos.value = false;
        });
}

function confirmDelete(item: Inventario) {
    inventarioToDelete.value = item;
    deleteDialogOpen.value = true;
}

function submitDelete() {
    if (!inventarioToDelete.value) return;
    const id = inventarioToDelete.value.id;
    deleteDialogOpen.value = false;
    router.delete(destroy(id).url, {
        preserveScroll: true,
        onSuccess: () => {
            inventarioToDelete.value = null;
        },
    });
}

function estadoLabel(item: Inventario): string {
    if (!item.disponible) return 'No disponible';
    if (item.stock_actual > 0) return 'En stock';
    return 'Agotado';
}

function estadoVariant(item: Inventario): string {
    if (!item.disponible) return 'outline';
    if (item.stock_actual > 0) return 'success';
    return 'destructive';
}

const imageUrl = (imagen: string | null | undefined): string | null => {
    if (!imagen) return null;
    if (imagen.startsWith('http')) return imagen;
    return `/storage/${imagen}`;
};
</script>

<template>
    <Head title="Inventario de Ropa" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <Heading title="Inventario de Ropa" description="Control de stock, entradas y salidas de prendas de vestir." />
            <div class="flex items-center gap-2">
                <TooltipProvider :delay-duration="0">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button variant="outline" class="border-amber-200 text-amber-600 hover:bg-amber-50">
                                <Eye class="mr-2 h-4 w-4" />
                                Ver Movimientos
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Ver historial de movimientos del inventario</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
                <TooltipProvider :delay-duration="0">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button variant="outline" class="border-blue-200 text-blue-600 hover:bg-blue-50">
                                <BarChart3 class="mr-2 h-4 w-4" />
                                Reportes
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Ver reportes del inventario</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative max-w-md flex-1">
                <Search class="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                <Input
                    v-model="search"
                    placeholder="Buscar por producto, categoría, marca, modelo, talla o color..."
                    class="pl-10"
                />
            </div>
            <Button variant="outline" @click="clearSearch">
                Limpiar
            </Button>
        </div>

        <div class="overflow-hidden rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-muted/50 border-b text-left">
                        <th class="px-4 py-3 font-medium">Imagen</th>
                        <th class="px-4 py-3 font-medium">Producto</th>
                        <th class="px-4 py-3 font-medium">Categoría</th>
                        <th class="px-4 py-3 font-medium">Marca</th>
                        <th class="px-4 py-3 font-medium">Modelo</th>
                        <th class="px-4 py-3 font-medium">Talla</th>
                        <th class="px-4 py-3 font-medium">Color</th>
                        <th class="px-4 py-3 text-center font-medium">Entradas</th>
                        <th class="px-4 py-3 text-center font-medium">Salidas</th>
                        <th class="px-4 py-3 text-center font-medium">Stock</th>
                        <th class="px-4 py-3 text-center font-medium">Estado</th>
                        <th class="px-4 py-3 text-center font-medium">Disponibilidad</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in inventario.data"
                        :key="item.id"
                        class="hover:bg-muted/30 border-b transition-colors last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-lg bg-muted">
                                <img
                                    v-if="imageUrl(item.producto?.imagen)"
                                    :src="imageUrl(item.producto!.imagen)"
                                    :alt="item.producto?.nombre"
                                    class="h-full w-full object-cover"
                                />
                                <Package2 v-else class="text-muted-foreground h-5 w-5" />
                            </div>
                        </td>
                        <td class="max-w-[180px] truncate px-4 py-3 font-medium">
                            {{ item.producto?.nombre ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge variant="secondary">{{ item.producto?.categoria?.nombre ?? '—' }}</Badge>
                        </td>
                        <td class="px-4 py-3 text-xs">{{ item.producto?.marca?.nombre ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ item.producto?.modelo?.nombre ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <Badge variant="outline">{{ item.producto?.talla?.nombre ?? '—' }}</Badge>
                        </td>
                        <td class="px-4 py-3">
                            <div v-if="item.producto?.color" class="flex items-center gap-2">
                                <span
                                    class="inline-block h-4 w-4 rounded-full border"
                                    :style="{ backgroundColor: item.producto.color.codigo_hex }"
                                />
                                <span class="text-xs">{{ item.producto.color.nombre }}</span>
                            </div>
                            <span v-else class="text-muted-foreground text-xs">—</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-mono text-xs text-green-600">{{ item.total_entradas }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-mono text-xs text-red-600">{{ item.total_salidas }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span
                                :class="[
                                    'font-mono text-sm font-semibold',
                                    item.stock_actual > 10
                                        ? 'text-green-600'
                                        : item.stock_actual > 0
                                            ? 'text-amber-600'
                                            : 'text-red-600',
                                ]"
                            >
                                {{ item.stock_actual }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Badge :variant="estadoVariant(item) as any">
                                {{ estadoLabel(item) }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button
                                v-if="can('inventario.gestion')"
                                type="button"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition-colors"
                                :class="item.disponible
                                    ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100'
                                    : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'"
                                @click="toggleDisponibleItem(item)"
                            >
                                {{ item.disponible ? '✅ Disponible' : '❌ No disponible' }}
                            </button>
                            <span v-else class="text-xs">
                                {{ item.disponible ? '✅' : '❌' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="outline"
                                                size="icon-sm"
                                                class="border-green-200 text-green-600 hover:bg-green-50"
                                                :disabled="!can('inventario.gestion')"
                                                @click="openEntradaDialog(item)"
                                            >
                                                <Plus class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Registrar entrada</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="outline"
                                                size="icon-sm"
                                                class="border-red-200 text-red-500 hover:bg-red-50"
                                                :disabled="!can('inventario.gestion')"
                                                @click="openSalidaDialog(item)"
                                            >
                                                <Minus class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Registrar salida</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="outline"
                                                size="icon-sm"
                                                class="border-blue-200 text-blue-600 hover:bg-blue-50"
                                                @click="openMovimientos(item)"
                                            >
                                                <ArrowUpDown class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Ver movimientos</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider v-if="can('inventario.gestion')" :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="outline"
                                                size="icon-sm"
                                                class="border-red-200 text-red-500 hover:bg-red-50"
                                                @click="confirmDelete(item)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Eliminar del inventario</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="inventario.data.length === 0">
                        <td colspan="13" class="py-12 text-center text-sm text-muted-foreground">
                            <Package2 class="mx-auto mb-2 h-8 w-8 opacity-40" />
                            No se encontraron productos en el inventario.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="inventario.last_page > 1" class="flex items-center justify-between">
            <p class="text-muted-foreground text-sm">
                Mostrando {{ inventario.from }}–{{ inventario.to }} de {{ inventario.total }} productos
            </p>
            <div class="flex items-center gap-2">
                <Link
                    :href="inventario.prev_page_url ?? '#'"
                    :class="['rounded-lg border px-3 py-1.5 text-sm transition-colors', inventario.prev_page_url ? 'hover:bg-muted' : 'pointer-events-none opacity-40']"
                >
                    Anterior
                </Link>
                <Link
                    :href="inventario.next_page_url ?? '#'"
                    :class="['rounded-lg border px-3 py-1.5 text-sm transition-colors', inventario.next_page_url ? 'hover:bg-muted' : 'pointer-events-none opacity-40']"
                >
                    Siguiente
                </Link>
            </div>
        </div>
    </div>

    <Dialog v-model:open="entradaDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Registrar Entrada</DialogTitle>
                <DialogDescription>
                    Agregar stock a <strong>{{ selectedInventario?.producto?.nombre }}</strong>
                    ({{ selectedInventario?.producto?.talla?.nombre }} /
                    {{ selectedInventario?.producto?.color?.nombre }}).
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitEntrada">
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="entrada-cantidad">Cantidad *</Label>
                        <Input
                            id="entrada-cantidad"
                            v-model="entradaForm.cantidad"
                            type="number"
                            min="1"
                            placeholder="0"
                        />
                        <InputError :message="entradaForm.errors.cantidad" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="entrada-motivo">Motivo</Label>
                        <Input
                            id="entrada-motivo"
                            v-model="entradaForm.motivo"
                            placeholder="ej: Reposición de stock, nueva compra"
                        />
                        <InputError :message="entradaForm.errors.motivo" />
                    </div>
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="entradaForm.processing" class="bg-green-600 hover:bg-green-700">
                        <Plus class="mr-2 h-4 w-4" />
                        Registrar Entrada
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="salidaDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Registrar Salida</DialogTitle>
                <DialogDescription>
                    Retirar stock de <strong>{{ selectedInventario?.producto?.nombre }}</strong>
                    ({{ selectedInventario?.producto?.talla?.nombre }} /
                    {{ selectedInventario?.producto?.color?.nombre }}).
                    Stock actual: <strong>{{ selectedInventario?.stock_actual }}</strong>
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitSalida">
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="salida-cantidad">Cantidad *</Label>
                        <Input
                            id="salida-cantidad"
                            v-model="salidaForm.cantidad"
                            type="number"
                            min="1"
                            placeholder="0"
                        />
                        <InputError :message="salidaForm.errors.cantidad" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="salida-motivo">Motivo</Label>
                        <Input
                            id="salida-motivo"
                            v-model="salidaForm.motivo"
                            placeholder="ej: Venta, ajuste, producto dañado"
                        />
                        <InputError :message="salidaForm.errors.motivo" />
                    </div>
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="salidaForm.processing" class="bg-red-600 hover:bg-red-700">
                        <Minus class="mr-2 h-4 w-4" />
                        Registrar Salida
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Eliminar del Inventario</DialogTitle>
                <DialogDescription>
                    ¿Estás seguro de eliminar <strong>{{ inventarioToDelete?.producto?.nombre }}</strong>
                    ({{ inventarioToDelete?.producto?.talla?.nombre }} /
                    {{ inventarioToDelete?.producto?.color?.nombre }}) del inventario?
                    Esta acción no se puede deshacer.
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

    <Dialog v-model:open="movimientosDialogOpen">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Movimientos de Inventario</DialogTitle>
                <DialogDescription v-if="selectedInventario">
                    Historial de movimientos de <strong>{{ selectedInventario.producto?.nombre }}</strong>
                    ({{ selectedInventario.producto?.talla?.nombre }} /
                    {{ selectedInventario.producto?.color?.nombre }}).
                    Stock actual: <strong>{{ selectedInventario.stock_actual }}</strong>
                </DialogDescription>
            </DialogHeader>
            <div class="max-h-96 overflow-y-auto py-4">
                <div v-if="loadingMovimientos" class="py-8 text-center text-sm text-muted-foreground">
                    Cargando movimientos...
                </div>
                <div v-else-if="movimientosList.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                    <Package2 class="mx-auto mb-2 h-8 w-8 opacity-40" />
                    No hay movimientos registrados.
                </div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="bg-muted/50 border-b text-left">
                            <th class="px-3 py-2 font-medium">Tipo</th>
                            <th class="px-3 py-2 font-medium">Cantidad</th>
                            <th class="px-3 py-2 font-medium">Motivo</th>
                            <th class="px-3 py-2 font-medium">Registrado por</th>
                            <th class="px-3 py-2 font-medium">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="mov in movimientosList"
                            :key="mov.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-3 py-2">
                                <Badge :variant="mov.tipo === 'entrada' ? 'secondary' : 'outline'">
                                    {{ mov.tipo === 'entrada' ? 'Entrada' : 'Salida' }}
                                </Badge>
                            </td>
                            <td class="px-3 py-2">
                                <span
                                    :class="[
                                        'font-mono font-semibold',
                                        mov.tipo === 'entrada' ? 'text-green-600' : 'text-red-600',
                                    ]"
                                >
                                    {{ mov.tipo === 'entrada' ? '+' : '-' }}{{ mov.cantidad }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs">{{ mov.motivo ?? '—' }}</td>
                            <td class="px-3 py-2 text-xs">{{ mov.user?.name ?? '—' }}</td>
                            <td class="px-3 py-2 text-xs">{{ mov.created_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cerrar</Button>
                </DialogClose>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

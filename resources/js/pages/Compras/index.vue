<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Ban,
    Eye,
    Pencil,
    Plus,
    Printer,
    Search,
    ShoppingCart,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
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
import { anular, index, store, update } from '@/routes/compras';
import type { Compra, PaginatedData, Producto, Proveedor } from '@/types';

const { can } = usePermissions();

type Props = {
    compras: PaginatedData<Compra>;
    proveedores: Proveedor[];
    productos: Producto[];
    filters: { search?: string };
};

type PurchaseProductRow = Compra & {
    row_key: string;
    purchase_number: number;
    is_first_product: boolean;
    grouped_details: Compra['detalles'];
    row_total: number;
};

const props = defineProps<Props>();

const purchaseProductRows = computed<PurchaseProductRow[]>(() =>
    props.compras.data.flatMap((compra, purchaseIndex) => {
        const detailsByProduct = new Map<string, Compra['detalles']>();

        compra.detalles.forEach((detalle) => {
            const productKey = detalle.producto_id
                ? `product-${detalle.producto_id}`
                : `name-${detalle.producto.trim().toLocaleLowerCase('es')}`;
            const productDetails = detailsByProduct.get(productKey) ?? [];

            productDetails.push(detalle);
            detailsByProduct.set(productKey, productDetails);
        });

        if (detailsByProduct.size === 0) {
            return [
                {
                    ...compra,
                    row_key: `purchase-${compra.id}`,
                    purchase_number: purchaseIndex + 1,
                    is_first_product: true,
                    grouped_details: [],
                    row_total: Number(compra.total),
                },
            ];
        }

        return Array.from(detailsByProduct.entries()).map(
            ([productKey, details], productIndex) => ({
                ...compra,
                row_key: `purchase-${compra.id}-${productKey}`,
                purchase_number: purchaseIndex + 1,
                is_first_product: productIndex === 0,
                grouped_details: details,
                row_total: details.reduce(
                    (total, detalle) => total + Number(detalle.subtotal),
                    0,
                ),
            }),
        );
    }),
);

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

type LineItem = {
    producto_id: string;
    producto_variante_id: string;
    talla_id: string;
    color_id: string;
    cantidad: number;
    precio_unitario: number;
    subtotal: number;
};

const emptyLineItem = (): LineItem => ({
    producto_id: '',
    producto_variante_id: '',
    talla_id: '',
    color_id: '',
    cantidad: 1,
    precio_unitario: 0,
    subtotal: 0,
});

function todayDate(): string {
    const today = new Date();
    const localDate = new Date(
        today.getTime() - today.getTimezoneOffset() * 60_000,
    );

    return localDate.toISOString().slice(0, 10);
}

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
const annulDialogOpen = ref(false);
const viewDialogOpen = ref(false);
const printDialogOpen = ref(false);
const compraToAnnul = ref<Compra | null>(null);
const compraToPrint = ref<Compra | null>(null);
const receiptRef = ref<HTMLElement | null>(null);
const duplicateMessage = ref('');
const providerSearch = ref('');
const purchaseProductSearch = ref('');

const filteredProviders = computed(() => {
    const query = providerSearch.value.trim().toLocaleLowerCase('es');
    const providers = [...props.proveedores];
    const historicalProvider = editingCompra.value?.proveedor;

    if (
        historicalProvider &&
        !providers.some((provider) => provider.id === historicalProvider.id)
    ) {
        providers.push(historicalProvider);
    }

    if (!query) {
        return providers;
    }

    return providers.filter((provider) =>
        provider.nombre.toLocaleLowerCase('es').includes(query),
    );
});

const filteredPurchaseProducts = computed(() => {
    const query = purchaseProductSearch.value.trim().toLocaleLowerCase('es');

    if (!query) {
        return props.productos;
    }

    return props.productos.filter((product) =>
        [
            product.nombre,
            product.categoria?.nombre,
            product.marca?.nombre,
            product.talla?.nombre,
            product.color?.nombre,
            ...(product.variantes ?? []).flatMap((variant) => [
                variant.talla?.nombre,
                variant.color?.nombre,
            ]),
        ]
            .filter(Boolean)
            .join(' ')
            .toLocaleLowerCase('es')
            .includes(query),
    );
});

const isEditing = computed(() => !!editingCompra.value);
const dialogTitle = computed(() =>
    isEditing.value ? 'Editar Compra' : 'Nueva Compra',
);

const form = computed(() => (isEditing.value ? editForm : createForm));

const formTotal = computed(() =>
    form.value.detalles.reduce((sum, item) => sum + (item.subtotal || 0), 0),
);

function productImage(productId: string): string | null {
    const image = findProduct(productId)?.imagen;

    return image ? `/storage/${image}` : null;
}

function addLineItem(): void {
    form.value.detalles.push(emptyLineItem());
}

function removeLineItem(index: number): void {
    form.value.detalles.splice(index, 1);
}

function openCreateDialog() {
    editingCompra.value = null;
    duplicateMessage.value = '';
    createForm.reset();
    createForm.fecha = todayDate();
    createForm.detalles = [emptyLineItem()];
    providerSearch.value = '';
    purchaseProductSearch.value = '';
    formDialogOpen.value = true;
}

function openViewDialog(compra: Compra) {
    viewingCompra.value = compra;
    viewDialogOpen.value = true;
}

function openEditDialog(compra: Compra) {
    editingCompra.value = compra;
    editForm.proveedor_id = String(compra.proveedor_id);
    editForm.fecha = compra.fecha.slice(0, 10);
    editForm.detalles = (compra.detalles ?? []).map((d) => ({
        producto_id: String(d.producto_id),
        producto_variante_id: String(d.producto_variante_id ?? ''),
        talla_id: String(d.variante?.talla?.id ?? ''),
        color_id: String(d.variante?.color?.id ?? ''),
        cantidad: Number(d.cantidad),
        precio_unitario: Number(d.precio_unitario),
        subtotal: Number(d.subtotal),
    }));

    if (editForm.detalles.length === 0) {
        editForm.detalles = [emptyLineItem()];
    }

    providerSearch.value = '';
    purchaseProductSearch.value = '';

    formDialogOpen.value = true;
}

function openPrintDialog(compra: Compra) {
    compraToPrint.value = compra;
    printDialogOpen.value = true;
}

async function printPage(): Promise<void> {
    await nextTick();

    if (!receiptRef.value) {
        return;
    }

    document.querySelector('#purchase-print-root')?.remove();

    const printRoot = document.createElement('div');
    printRoot.id = 'purchase-print-root';
    printRoot.appendChild(receiptRef.value.cloneNode(true));
    document.body.appendChild(printRoot);

    const cleanPrintRoot = (): void => {
        printRoot.remove();
    };

    window.addEventListener('afterprint', cleanPrintRoot, { once: true });

    requestAnimationFrame(() => {
        requestAnimationFrame(() => window.print());
    });
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
    if (!editingCompra.value) {
        return;
    }

    editForm.patch(update(editingCompra.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            formDialogOpen.value = false;
            editingCompra.value = null;
            editForm.reset();
        },
    });
}

function confirmAnnul(compra: Compra) {
    compraToAnnul.value = compra;
    annulDialogOpen.value = true;
}

function submitAnnul() {
    if (!compraToAnnul.value) {
        return;
    }

    const id = compraToAnnul.value.id;
    annulDialogOpen.value = false;
    router.post(
        anular(id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                compraToAnnul.value = null;
            },
        },
    );
}

function findProduct(productoId: string): Producto | undefined {
    return props.productos.find((p) => String(p.id) === productoId);
}

function detailSize(detalle: Compra['detalles'][number]): string {
    return detalle.talla ?? detalle.producto_relacion?.talla?.nombre ?? '—';
}

function detailColor(detalle: Compra['detalles'][number]): string {
    return detalle.color ?? detalle.producto_relacion?.color?.nombre ?? '—';
}

function detailColorHex(detalle: Compra['detalles'][number]): string {
    const savedColorName = detalle.color?.trim().toLocaleLowerCase('es');
    const matchingVariant = props.productos
        .find((product) => product.id === detalle.producto_id)
        ?.variantes?.find(
            (variant) =>
                variant.color?.nombre.trim().toLocaleLowerCase('es') ===
                savedColorName,
        );

    return (
        matchingVariant?.color?.codigo_hex ??
        detalle.variante?.color?.codigo_hex ??
        detalle.producto_relacion?.color?.codigo_hex ??
        '#a78bfa'
    );
}

function availableSizes(item: LineItem) {
    const variants = findProduct(item.producto_id)?.variantes ?? [];

    return variants.filter(
        (variant, index) =>
            variant.talla &&
            variants.findIndex(
                (candidate) => candidate.talla_id === variant.talla_id,
            ) === index,
    );
}

function availableColors(item: LineItem) {
    return (findProduct(item.producto_id)?.variantes ?? []).filter(
        (variant) => String(variant.talla_id) === item.talla_id,
    );
}

function hasDuplicateInOtherRow(item: LineItem): boolean {
    return form.value.detalles.some(
        (d) =>
            d.producto_variante_id !== '' &&
            d.producto_variante_id === item.producto_variante_id &&
            d !== item,
    );
}

function handleProductChange(item: LineItem, value: string): void {
    duplicateMessage.value = '';

    if (value === '') {
        item.producto_id = value;
        item.producto_variante_id = '';
        item.talla_id = '';
        item.color_id = '';

        return;
    }

    item.producto_id = value;
    const firstVariant = findProduct(value)?.variantes?.[0];
    item.talla_id = firstVariant ? String(firstVariant.talla_id) : '';
    item.color_id = firstVariant ? String(firstVariant.color_id) : '';
    item.producto_variante_id = firstVariant ? String(firstVariant.id) : '';
}

function handleSizeChange(item: LineItem, value: string): void {
    item.talla_id = value;
    const firstVariant = availableColors(item)[0];
    item.color_id = firstVariant ? String(firstVariant.color_id) : '';
    item.producto_variante_id = firstVariant ? String(firstVariant.id) : '';
}

function handleColorChange(item: LineItem, value: string): void {
    item.color_id = value;
    const variant = availableColors(item).find(
        (candidate) => String(candidate.color_id) === value,
    );
    item.producto_variante_id = variant ? String(variant.id) : '';
}

function formatCurrency(value: string | number): string {
    const num = typeof value === 'string' ? parseFloat(value) : value;

    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
        minimumFractionDigits: 2,
    }).format(num);
}

function formatDate(date: string | Date | null): string {
    if (!date) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-BO', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone: 'UTC',
    })
        .format(new Date(date))
        .replace('.', '');
}

function getFullName(user: Compra['user']): string {
    if (!user) {
        return '—';
    }

    if (user.name) {
        return user.name;
    }

    const parts = [
        user.nombres,
        user.apellido_paterno,
        user.apellido_materno,
    ].filter(Boolean);

    return parts.join(' ') || '—';
}
</script>

<template>
    <Head title="Compras" />

    <div
        class="flex h-full flex-1 flex-col gap-5 rounded-xl bg-gradient-to-br from-violet-100 via-fuchsia-50 to-pink-100 p-4"
    >
        <div
            class="relative overflow-hidden rounded-[2rem] border border-white/90 bg-white shadow-[0_22px_55px_-30px_rgba(126,34,206,0.55)] ring-1 ring-pink-100"
        >
            <img
                src="/images/dashboard-boutique-hero.png"
                alt="Boutique Emma Fashion"
                class="absolute inset-0 h-full w-full object-cover object-right"
            />
            <div
                class="absolute inset-0 bg-gradient-to-r from-white via-white/95 to-white/5 sm:via-white/85 lg:via-white/65"
            ></div>
            <div
                class="relative flex min-h-40 flex-col items-start justify-between gap-5 p-5 sm:flex-row sm:items-center sm:p-7 lg:min-h-44"
            >
                <div class="flex items-center gap-4 sm:gap-5">
                    <div
                        class="flex size-16 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 via-fuchsia-600 to-violet-800 text-white shadow-xl shadow-fuchsia-300/60 sm:size-20"
                    >
                        <ShoppingCart class="size-8 sm:size-9" />
                    </div>
                    <div>
                        <h1
                            class="text-2xl font-black tracking-tight text-slate-950 sm:text-3xl"
                        >
                            Gestión de Compras
                        </h1>
                        <p class="mt-1 text-sm text-slate-600 sm:text-base">
                            Administra las compras del sistema.
                        </p>
                    </div>
                </div>
                <Button
                    v-if="can('compras.crear')"
                    class="w-full bg-gradient-to-r from-fuchsia-600 to-violet-700 shadow-lg shadow-fuchsia-400/40 hover:from-fuchsia-700 hover:to-violet-800 sm:w-auto"
                    @click="openCreateDialog"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    Nueva Compra
                </Button>
            </div>
        </div>

        <div
            class="flex items-center gap-2 rounded-xl border border-fuchsia-200 bg-gradient-to-r from-pink-100/95 via-fuchsia-50 to-violet-100/95 p-4 shadow-md shadow-violet-200/50"
        >
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Buscar por nombre de proveedor o ID de compra..."
                    class="pl-10"
                />
            </div>
            <Button
                variant="outline"
                class="border-violet-200 bg-violet-50 text-violet-800 hover:bg-violet-100"
                @click="clearSearch"
            >
                Limpiar
            </Button>
        </div>

        <div
            class="overflow-x-auto rounded-2xl border border-fuchsia-200 bg-gradient-to-br from-pink-100/95 via-fuchsia-50/95 to-violet-100/95 shadow-xl shadow-violet-300/50"
        >
            <table class="w-full min-w-[1050px] text-sm">
                <thead>
                    <tr
                        class="border-b border-violet-200 bg-gradient-to-r from-violet-200 via-pink-100 to-fuchsia-200 text-left text-slate-900"
                    >
                        <th class="px-4 py-3 font-medium">ID</th>
                        <th class="px-4 py-3 font-medium">Producto</th>
                        <th class="px-4 py-3 text-center font-medium">Talla</th>
                        <th class="px-4 py-3 font-medium">Color</th>
                        <th class="px-4 py-3 font-medium">Proveedor</th>
                        <th class="px-4 py-3 font-medium">Fecha</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Usuario</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="compra in purchaseProductRows"
                        :key="compra.row_key"
                        class="border-b border-violet-100 transition-colors last:border-0 odd:bg-violet-100/50 even:bg-pink-50/70 hover:bg-pink-200/60"
                    >
                        <td class="px-4 py-3">
                            <div
                                v-if="compra.is_first_product"
                                class="grid justify-items-start gap-1.5"
                            >
                                <span
                                    class="inline-flex rounded-lg bg-violet-100 px-2.5 py-1 text-sm font-semibold text-violet-800"
                                >
                                    #{{ compra.purchase_number }}
                                </span>
                                <span
                                    v-if="compra.estado === 'anulada'"
                                    class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700"
                                >
                                    Anulada
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="grid gap-1.5">
                                <div
                                    v-for="detalle in compra.grouped_details.slice(
                                        0,
                                        1,
                                    )"
                                    :key="detalle.id"
                                    class="flex items-center gap-2 text-xs"
                                >
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-full bg-pink-50 text-primary"
                                    >
                                        <ShoppingCart class="h-3.5 w-3.5" />
                                    </span>
                                    <span class="font-semibold text-slate-800">
                                        {{ detalle.producto }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="grid justify-center gap-1.5">
                                <span
                                    v-for="detalle in compra.grouped_details"
                                    :key="detalle.id"
                                    class="inline-flex min-w-8 justify-center rounded-full bg-violet-100 px-2 py-1 text-xs font-semibold text-violet-700"
                                >
                                    {{ detailSize(detalle) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="grid gap-1.5">
                                <span
                                    v-for="detalle in compra.grouped_details"
                                    :key="detalle.id"
                                    class="inline-flex w-fit items-center gap-1.5 rounded-full bg-violet-50 px-2 py-1 text-xs text-slate-700"
                                >
                                    <span
                                        class="h-2.5 w-2.5 rounded-full border border-slate-300"
                                        :style="{
                                            backgroundColor:
                                                detailColorHex(detalle),
                                        }"
                                    />
                                    {{ detailColor(detalle) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10"
                                >
                                    <ShoppingCart
                                        class="h-4 w-4 text-primary"
                                    />
                                </div>
                                <div>
                                    <p class="font-medium">
                                        {{ compra.proveedor?.nombre ?? '—' }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs">{{
                                formatDate(compra.fecha)
                            }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-medium">{{
                                formatCurrency(compra.row_total)
                            }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs">{{ 'Administradora' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="outline"
                                                size="icon-sm"
                                                class="border-primary/20 text-primary hover:bg-primary/10"
                                                @click="openViewDialog(compra)"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Ver compra</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider
                                    v-if="
                                        can('compras.editar') &&
                                        compra.estado !== 'anulada'
                                    "
                                    :delay-duration="0"
                                >
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="outline"
                                                size="icon-sm"
                                                class="border-primary/20 text-primary hover:bg-primary/10"
                                                @click="openEditDialog(compra)"
                                            >
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
                                            <Button
                                                variant="outline"
                                                size="icon-sm"
                                                class="border-amber-200 text-amber-600 hover:bg-amber-50"
                                                @click="openPrintDialog(compra)"
                                            >
                                                <Printer class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Imprimir comprobante</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider
                                    v-if="
                                        can('compras.eliminar') &&
                                        compra.estado !== 'anulada'
                                    "
                                    :delay-duration="0"
                                >
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="outline"
                                                size="icon-sm"
                                                class="border-amber-200 text-amber-600 hover:bg-amber-50"
                                                @click="confirmAnnul(compra)"
                                            >
                                                <Ban class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Anular compra</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="compras.data.length === 0">
                        <td
                            colspan="9"
                            class="py-12 text-center text-sm text-muted-foreground"
                        >
                            <ShoppingCart
                                class="mx-auto mb-2 h-8 w-8 opacity-40"
                            />
                            No se encontraron compras.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="compras.last_page > 1"
            class="flex items-center justify-between"
        >
            <p class="text-sm text-muted-foreground">
                Mostrando {{ compras.from }}–{{ compras.to }} de
                {{ compras.total }} compras
            </p>
            <div class="flex items-center gap-2">
                <Link
                    :href="compras.prev_page_url ?? '#'"
                    :class="[
                        'rounded-lg border px-3 py-1.5 text-sm transition-colors',
                        compras.prev_page_url
                            ? 'hover:bg-muted'
                            : 'pointer-events-none opacity-40',
                    ]"
                >
                    Anterior
                </Link>
                <Link
                    :href="compras.next_page_url ?? '#'"
                    :class="[
                        'rounded-lg border px-3 py-1.5 text-sm transition-colors',
                        compras.next_page_url
                            ? 'hover:bg-muted'
                            : 'pointer-events-none opacity-40',
                    ]"
                >
                    Siguiente
                </Link>
            </div>
        </div>
    </div>

    <Dialog v-model:open="formDialogOpen">
        <DialogContent
            class="flex h-[90vh] max-h-[860px] min-h-0 w-[calc(100vw-2rem)] flex-col gap-0 overflow-hidden p-0 sm:max-w-6xl"
        >
            <DialogHeader
                class="shrink-0 flex-row items-center gap-2 border-b border-violet-100 px-5 py-2.5 text-left"
            >
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-fuchsia-500 to-violet-700 text-white shadow-md shadow-violet-300/50"
                >
                    <ShoppingCart class="h-4 w-4" />
                </div>
                <div>
                    <DialogTitle class="text-lg text-indigo-950">{{
                        dialogTitle
                    }}</DialogTitle>
                    <DialogDescription class="text-xs text-indigo-500">
                        {{
                            isEditing
                                ? 'Modifica los datos reales de la compra seleccionada.'
                                : 'Registra una compra y actualiza automáticamente el inventario.'
                        }}
                    </DialogDescription>
                </div>
            </DialogHeader>
            <form
                class="flex min-h-0 flex-1 flex-col"
                @submit.prevent="isEditing ? submitUpdate() : submitCreate()"
            >
                <div
                    class="grid min-h-0 flex-1 content-start gap-2.5 overflow-y-auto px-4 py-2.5"
                >
                    <div
                        class="flex min-h-0 flex-col overflow-hidden rounded-xl border border-violet-100"
                    >
                        <h3
                            class="bg-gradient-to-r from-violet-100 to-violet-50 px-3 py-2 text-sm font-semibold text-indigo-950"
                        >
                            Información de la Compra
                        </h3>
                        <div class="grid grid-cols-1 gap-3 p-2 sm:grid-cols-2">
                            <div class="grid gap-1">
                                <Label for="proveedor_id">Proveedor</Label>
                                <Select v-model="form.proveedor_id">
                                    <SelectTrigger class="pr-10">
                                        <SelectValue
                                            placeholder="Seleccionar proveedor"
                                        />
                                        <Search
                                            class="ml-auto h-4 w-4 text-muted-foreground"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <div class="relative p-2">
                                            <Search
                                                class="absolute top-1/2 left-5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                                            />
                                            <Input
                                                v-model="providerSearch"
                                                placeholder="Buscar proveedor..."
                                                class="h-9 pl-9"
                                                @keydown.stop
                                            />
                                        </div>
                                        <SelectItem
                                            v-for="proveedor in filteredProviders"
                                            :key="proveedor.id"
                                            :value="String(proveedor.id)"
                                        >
                                            {{ proveedor.nombre }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError
                                    :message="form.errors.proveedor_id"
                                />
                            </div>
                            <div class="grid gap-1">
                                <Label for="fecha">Fecha</Label>
                                <Input
                                    id="fecha"
                                    type="date"
                                    v-model="form.fecha"
                                />
                                <InputError :message="form.errors.fecha" />
                            </div>
                        </div>
                    </div>

                    <div
                        class="overflow-hidden rounded-xl border border-violet-100"
                    >
                        <div
                            class="flex items-center justify-between gap-3 bg-gradient-to-r from-violet-100 to-violet-50 px-3 py-1.5"
                        >
                            <h3 class="text-sm font-semibold text-indigo-950">
                                Productos
                            </h3>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-8 border-violet-200 bg-white text-violet-700 hover:bg-violet-50"
                                @click="addLineItem"
                            >
                                <Plus class="mr-1 h-4 w-4" />
                                Agregar producto
                            </Button>
                        </div>
                        <div
                            class="max-h-[360px] overflow-auto [scrollbar-gutter:stable]"
                        >
                            <table
                                class="w-full min-w-[1050px] table-fixed text-xs"
                            >
                                <thead class="sticky top-0 z-10 bg-white">
                                    <tr
                                        class="border-b border-violet-100 bg-violet-50/40 text-left text-indigo-950"
                                    >
                                        <th class="w-8 px-3 py-2"></th>
                                        <th
                                            class="w-[350px] px-3 py-2 font-medium"
                                        >
                                            Producto
                                        </th>
                                        <th class="w-40 px-3 py-2 font-medium">
                                            Color
                                        </th>
                                        <th
                                            class="w-24 px-3 py-2 text-center font-medium"
                                        >
                                            Talla
                                        </th>
                                        <th
                                            class="w-28 px-3 py-2 text-center font-medium"
                                        >
                                            Cantidad
                                        </th>
                                        <th
                                            class="w-36 px-3 py-2 text-right font-medium"
                                        >
                                            Precio Unitario
                                        </th>
                                        <th
                                            class="w-28 px-3 py-2 text-right font-medium"
                                        >
                                            Total Parcial
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, index) in form.detalles"
                                        :key="index"
                                        class="border-b text-left transition-colors last:border-0 hover:bg-muted/30"
                                    >
                                        <td class="px-2 py-1.5">
                                            <button
                                                type="button"
                                                class="text-muted-foreground transition-colors hover:text-red-500"
                                                @click="removeLineItem(index)"
                                            >
                                                <X class="h-4 w-4" />
                                            </button>
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <div
                                                v-if="item.producto_id"
                                                class="hidden"
                                            >
                                                <img
                                                    v-if="
                                                        productImage(
                                                            item.producto_id,
                                                        )
                                                    "
                                                    :src="
                                                        productImage(
                                                            item.producto_id,
                                                        ) ?? ''
                                                    "
                                                    :alt="
                                                        findProduct(
                                                            item.producto_id,
                                                        )?.nombre
                                                    "
                                                    class="h-8 w-8 rounded-md border border-violet-100 object-cover"
                                                />
                                                <div
                                                    v-else
                                                    class="flex h-8 w-8 items-center justify-center rounded-md bg-violet-50 text-violet-600"
                                                >
                                                    <ShoppingCart
                                                        class="h-4 w-4"
                                                    />
                                                </div>
                                                <div class="leading-tight">
                                                    <p
                                                        class="font-semibold text-indigo-950"
                                                    >
                                                        {{
                                                            findProduct(
                                                                item.producto_id,
                                                            )?.nombre
                                                        }}
                                                    </p>
                                                    <p
                                                        class="text-[11px] text-indigo-400"
                                                    >
                                                        {{
                                                            findProduct(
                                                                item.producto_id,
                                                            )?.marca?.nombre ??
                                                            'Producto'
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                            <Select
                                                :model-value="item.producto_id"
                                                @update:model-value="
                                                    (v: any) =>
                                                        handleProductChange(
                                                            item,
                                                            String(v ?? ''),
                                                        )
                                                "
                                            >
                                                <SelectTrigger
                                                    class="h-9 min-w-72 pr-10"
                                                >
                                                    <SelectValue
                                                        placeholder="Seleccionar producto"
                                                    />
                                                    <Search
                                                        class="ml-auto h-4 w-4 text-muted-foreground"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <div class="relative p-2">
                                                        <Search
                                                            class="absolute top-1/2 left-5 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                                                        />
                                                        <Input
                                                            v-model="
                                                                purchaseProductSearch
                                                            "
                                                            placeholder="Buscar producto..."
                                                            class="h-9 pl-9"
                                                            @keydown.stop
                                                        />
                                                    </div>
                                                    <SelectItem
                                                        v-for="producto in filteredPurchaseProducts"
                                                        :key="producto.id"
                                                        :value="
                                                            String(producto.id)
                                                        "
                                                    >
                                                        {{ producto.nombre }}
                                                        ·
                                                        {{
                                                            producto.categoria
                                                                ?.nombre
                                                        }}
                                                        ·
                                                        {{
                                                            producto.marca
                                                                ?.nombre
                                                        }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                :message="
                                                    form.errors[
                                                        `detalles.${index}.producto_id`
                                                    ]
                                                "
                                            />
                                            <p
                                                v-if="
                                                    hasDuplicateInOtherRow(item)
                                                "
                                                class="mt-1 flex items-center gap-1 text-xs font-medium text-amber-600"
                                            >
                                                Esta talla y este color ya están
                                                agregados en otra fila.
                                            </p>
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <Select
                                                :model-value="item.color_id"
                                                :disabled="!item.talla_id"
                                                @update:model-value="
                                                    (value: any) =>
                                                        handleColorChange(
                                                            item,
                                                            String(value ?? ''),
                                                        )
                                                "
                                            >
                                                <SelectTrigger
                                                    class="h-9 min-w-32"
                                                >
                                                    <SelectValue
                                                        placeholder="Color"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="variant in availableColors(
                                                            item,
                                                        )"
                                                        :key="variant.id"
                                                        :value="
                                                            String(
                                                                variant.color_id,
                                                            )
                                                        "
                                                    >
                                                        {{
                                                            variant.color
                                                                ?.nombre
                                                        }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </td>
                                        <td class="px-2 py-1.5 text-center">
                                            <Select
                                                :model-value="item.talla_id"
                                                :disabled="!item.producto_id"
                                                @update:model-value="
                                                    (value: any) =>
                                                        handleSizeChange(
                                                            item,
                                                            String(value ?? ''),
                                                        )
                                                "
                                            >
                                                <SelectTrigger
                                                    class="h-9 min-w-20"
                                                >
                                                    <SelectValue
                                                        placeholder="Talla"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="variant in availableSizes(
                                                            item,
                                                        )"
                                                        :key="variant.talla_id"
                                                        :value="
                                                            String(
                                                                variant.talla_id,
                                                            )
                                                        "
                                                    >
                                                        {{
                                                            variant.talla
                                                                ?.nombre
                                                        }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                :message="
                                                    form.errors[
                                                        `detalles.${index}.producto_variante_id`
                                                    ]
                                                "
                                            />
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <Input
                                                v-model.number="item.cantidad"
                                                type="number"
                                                min="1"
                                                class="h-9 min-w-20 text-center tabular-nums"
                                                @update:model-value="
                                                    computeSubtotal(item)
                                                "
                                            />
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <Input
                                                v-model.number="
                                                    item.precio_unitario
                                                "
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                class="h-9 min-w-28 [appearance:textfield] px-3 text-right tabular-nums [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                                @update:model-value="
                                                    computeSubtotal(item)
                                                "
                                            />
                                        </td>
                                        <td
                                            class="px-2 py-1.5 text-right font-medium"
                                        >
                                            {{ formatCurrency(item.subtotal) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <InputError :message="form.errors.detalles" />
                        <InputError :message="form.errors.compra" />
                        <p
                            v-if="duplicateMessage"
                            class="mt-2 text-sm font-medium text-amber-600"
                        >
                            {{ duplicateMessage }}
                        </p>
                    </div>

                    <div
                        class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-center"
                    >
                        <div
                            class="flex items-start gap-2 rounded-lg bg-violet-50 px-3 py-2 text-xs text-indigo-600"
                        >
                            <span
                                class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-violet-600 text-[10px] font-bold text-white"
                                >i</span
                            >
                            La compra registrada actualizará automáticamente el
                            stock del inventario.
                        </div>
                        <div
                            class="rounded-lg bg-gradient-to-r from-violet-50 to-fuchsia-50 px-5 py-3 text-right text-indigo-950"
                        >
                            <span class="mr-3 text-sm font-semibold"
                                >Total:</span
                            >
                            <span class="text-xl font-bold">{{
                                formatCurrency(formTotal)
                            }}</span>
                        </div>
                    </div>
                </div>
                <DialogFooter
                    class="shrink-0 border-t border-violet-100 bg-white px-4 py-2.5"
                >
                    <DialogClose as-child>
                        <Button type="button" variant="outline"
                            >Cancelar</Button
                        >
                    </DialogClose>
                    <Button
                        type="submit"
                        class="bg-gradient-to-r from-fuchsia-600 to-violet-700 text-white hover:from-fuchsia-700 hover:to-violet-800"
                        :disabled="form.processing"
                    >
                        <ShoppingCart class="mr-2 h-4 w-4" />
                        {{ isEditing ? 'Guardar Cambios' : 'Registrar Compra' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="annulDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Anular Compra</DialogTitle>
                <DialogDescription>
                    ¿Estás seguro de anular esta compra?
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cancelar</Button>
                </DialogClose>
                <Button
                    class="bg-amber-500 text-white hover:bg-amber-600"
                    @click="submitAnnul"
                >
                    Anular compra
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="viewDialogOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Detalle de Compra</DialogTitle>
                <DialogDescription v-if="viewingCompra">
                    Información completa de la compra
                    <strong>{{ viewingCompra.numero_compra }}</strong
                    >.
                </DialogDescription>
            </DialogHeader>
            <div v-if="viewingCompra" class="grid gap-4 py-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label class="text-xs text-muted-foreground"
                            >N° Compra</Label
                        >
                        <p class="font-mono text-sm font-medium">
                            {{ viewingCompra.numero_compra }}
                        </p>
                    </div>
                    <div>
                        <Label class="text-xs text-muted-foreground"
                            >Fecha</Label
                        >
                        <p class="text-sm font-medium">
                            {{ formatDate(viewingCompra.fecha) }}
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label class="text-xs text-muted-foreground"
                            >Proveedor</Label
                        >
                        <p class="text-sm font-medium">
                            {{ viewingCompra.proveedor?.nombre ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <Label class="text-xs text-muted-foreground"
                            >Total</Label
                        >
                        <p class="text-sm font-medium">
                            {{ formatCurrency(viewingCompra.total) }}
                        </p>
                    </div>
                </div>
                <div>
                    <Label class="text-xs text-muted-foreground"
                        >Registrado por</Label
                    >
                    <p class="text-sm font-medium">
                        {{ getFullName(viewingCompra.user) }}
                    </p>
                </div>
                <div
                    v-if="
                        viewingCompra.detalles &&
                        viewingCompra.detalles.length > 0
                    "
                >
                    <Label class="mb-2 block text-xs text-muted-foreground"
                        >Productos</Label
                    >
                    <div class="overflow-hidden rounded-lg border">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50 text-left">
                                    <th class="px-3 py-2 font-medium">
                                        Producto
                                    </th>
                                    <th
                                        class="px-3 py-2 text-center font-medium"
                                    >
                                        Talla
                                    </th>
                                    <th class="px-3 py-2 font-medium">Color</th>
                                    <th
                                        class="px-3 py-2 text-center font-medium"
                                    >
                                        Cantidad
                                    </th>
                                    <th
                                        class="px-3 py-2 text-right font-medium"
                                    >
                                        P. Unitario
                                    </th>
                                    <th
                                        class="px-3 py-2 text-right font-medium"
                                    >
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="detalle in viewingCompra.detalles"
                                    :key="detalle.id"
                                    class="border-b text-left last:border-0"
                                >
                                    <td class="px-3 py-2 text-sm font-medium">
                                        {{ detalle.producto }}
                                    </td>
                                    <td class="px-3 py-2 text-center text-sm">
                                        {{ detailSize(detalle) }}
                                    </td>
                                    <td class="px-3 py-2 text-sm">
                                        {{ detailColor(detalle) }}
                                    </td>
                                    <td class="px-3 py-2 text-center text-sm">
                                        {{ detalle.cantidad }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm">
                                        {{
                                            formatCurrency(
                                                detalle.precio_unitario,
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="px-3 py-2 text-right text-sm font-medium"
                                    >
                                        {{ formatCurrency(detalle.subtotal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-if="viewingCompra.observaciones">
                    <Label class="text-xs text-muted-foreground"
                        >Observaciones</Label
                    >
                    <p class="text-sm font-medium">
                        {{ viewingCompra.observaciones }}
                    </p>
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
        <DialogContent
            class="max-h-[94vh] w-[calc(100vw-2rem)] max-w-2xl overflow-y-auto"
        >
            <DialogHeader>
                <DialogTitle>Comprobante de Compra</DialogTitle>
                <DialogDescription v-if="compraToPrint">
                    Comprobante de la compra
                    <strong>{{ compraToPrint.numero_compra }}</strong
                    >.
                </DialogDescription>
            </DialogHeader>
            <div v-if="compraToPrint" class="grid gap-4 py-4">
                <div
                    ref="receiptRef"
                    class="purchase-receipt overflow-hidden rounded-2xl border border-pink-100 bg-white shadow-lg shadow-pink-100/60"
                >
                    <div
                        class="receipt-header relative overflow-hidden bg-gradient-to-r from-rose-500 via-pink-600 to-fuchsia-700 px-8 py-7 text-white"
                    >
                        <div
                            class="absolute -top-12 -right-8 size-36 rounded-full bg-white/10"
                        ></div>
                        <div
                            class="absolute -bottom-16 -left-8 size-40 rounded-full bg-white/10"
                        ></div>
                        <div
                            class="relative flex items-center justify-between gap-5"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex size-16 shrink-0 items-center justify-center rounded-2xl border border-white/30 bg-white/20 font-serif text-2xl font-bold shadow-inner"
                                >
                                    EF
                                </div>
                                <div>
                                    <h3
                                        class="text-2xl font-extrabold tracking-wide"
                                    >
                                        EMMA FASHION
                                    </h3>
                                    <p
                                        class="mt-1 text-xs font-medium text-white/90"
                                    >
                                        Tienda de ropa · Eterazama, Bolivia
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p
                                    class="text-xs font-semibold tracking-[0.16em] text-white/80"
                                >
                                    COMPROBANTE
                                </p>
                                <p class="mt-1 text-lg font-bold">DE COMPRA</p>
                            </div>
                        </div>
                    </div>
                    <div
                        class="grid gap-3 bg-gradient-to-br from-pink-50/80 to-white px-8 py-6 text-sm sm:grid-cols-2"
                    >
                        <div
                            class="rounded-xl border border-pink-100 bg-white/80 px-4 py-3"
                        >
                            <p
                                class="text-[10px] font-bold tracking-wider text-pink-600 uppercase"
                            >
                                Número de comprobante
                            </p>
                            <p
                                class="mt-1 font-mono text-sm font-bold text-slate-800"
                            >
                                {{ compraToPrint.numero_compra }}
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-pink-100 bg-white/80 px-4 py-3"
                        >
                            <p
                                class="text-[10px] font-bold tracking-wider text-pink-600 uppercase"
                            >
                                Fecha de compra
                            </p>
                            <p class="mt-1 font-semibold text-slate-800">
                                {{ formatDate(compraToPrint.fecha) }}
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-pink-100 bg-white/80 px-4 py-3"
                        >
                            <p
                                class="text-[10px] font-bold tracking-wider text-pink-600 uppercase"
                            >
                                Proveedor
                            </p>
                            <p class="mt-1 font-semibold text-slate-800">
                                {{ compraToPrint.proveedor?.nombre ?? '—' }}
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-pink-100 bg-white/80 px-4 py-3"
                        >
                            <p
                                class="text-[10px] font-bold tracking-wider text-pink-600 uppercase"
                            >
                                Registrado por
                            </p>
                            <p class="mt-1 font-semibold text-slate-800">
                                {{ getFullName(compraToPrint.user) }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="
                            compraToPrint.detalles &&
                            compraToPrint.detalles.length > 0
                        "
                        class="px-8 py-5"
                    >
                        <table class="receipt-table w-full text-sm">
                            <thead>
                                <tr
                                    class="bg-gradient-to-r from-pink-50 to-fuchsia-50 text-pink-700"
                                >
                                    <th
                                        class="rounded-l-lg px-4 py-3 text-left font-bold"
                                    >
                                        Producto
                                    </th>
                                    <th class="px-2 py-3 text-center font-bold">
                                        Talla
                                    </th>
                                    <th class="px-2 py-3 text-left font-bold">
                                        Color
                                    </th>
                                    <th class="px-2 py-3 text-center font-bold">
                                        Cant.
                                    </th>
                                    <th class="px-2 py-3 text-right font-bold">
                                        P.U.
                                    </th>
                                    <th
                                        class="rounded-r-lg px-4 py-3 text-right font-bold"
                                    >
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="detalle in compraToPrint.detalles"
                                    :key="detalle.id"
                                    class="border-b border-slate-100 last:border-0"
                                >
                                    <td
                                        class="px-4 py-4 font-semibold text-slate-800"
                                    >
                                        {{ detalle.producto }}
                                    </td>
                                    <td class="px-2 py-4 text-center">
                                        {{ detailSize(detalle) }}
                                    </td>
                                    <td class="px-2 py-4">
                                        {{ detailColor(detalle) }}
                                    </td>
                                    <td
                                        class="px-2 py-4 text-center font-medium"
                                    >
                                        {{ detalle.cantidad }}
                                    </td>
                                    <td class="px-2 py-4 text-right">
                                        {{
                                            formatCurrency(
                                                detalle.precio_unitario,
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="px-4 py-4 text-right font-bold text-slate-800"
                                    >
                                        {{ formatCurrency(detalle.subtotal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div
                        class="mx-8 mb-7 flex items-center justify-between rounded-xl bg-gradient-to-r from-slate-900 to-slate-800 px-6 py-5 text-white"
                    >
                        <div class="flex w-full items-center justify-between">
                            <div>
                                <p
                                    class="text-[10px] font-semibold tracking-widest text-pink-300 uppercase"
                                >
                                    Importe total
                                </p>
                                <span class="text-sm font-medium text-white/80"
                                    >Total de la compra</span
                                >
                            </div>
                            <span
                                class="text-3xl font-extrabold text-pink-300"
                                >{{ formatCurrency(compraToPrint.total) }}</span
                            >
                        </div>
                    </div>
                    <div
                        v-if="compraToPrint.observaciones"
                        class="mx-7 mb-4 rounded-lg border border-pink-100 bg-pink-50/60 px-4 py-3 text-xs text-slate-600"
                    >
                        <span class="font-bold text-pink-700"
                            >Observaciones:</span
                        >
                        {{ compraToPrint.observaciones }}
                    </div>
                </div>
            </div>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cerrar</Button>
                </DialogClose>
                <Button @click="printPage">
                    <Printer class="mr-2 h-4 w-4" />
                    Imprimir
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 8mm;
    }

    html,
    body {
        background: white !important;
    }

    body > * {
        display: none !important;
    }

    #purchase-print-root {
        display: block !important;
        width: 100%;
    }

    #purchase-print-root .purchase-receipt {
        width: 100%;
        min-height: 120mm;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    #purchase-print-root .receipt-header,
    #purchase-print-root .receipt-table thead,
    #purchase-print-root .purchase-receipt > div {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    #purchase-print-root .receipt-table thead {
        display: table-header-group;
    }

    #purchase-print-root .receipt-table tr {
        break-inside: avoid;
    }
}
</style>

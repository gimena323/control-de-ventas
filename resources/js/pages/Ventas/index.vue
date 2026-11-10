<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Check,
    Edit,
    Eye,
    History,
    Minus,
    Package,
    Plus,
    Printer,
    Search,
    ShoppingCart,
    Trash2,
    TrendingUp,
    User,
    X,
} from 'lucide-vue-next';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
    watch,
} from 'vue';
import ManagementHero from '@/components/ManagementHero.vue';
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
import VentasClientesTabs from '@/components/VentasClientesTabs.vue';
import { usePermissions } from '@/composables/usePermissions';
import { index, store, update, anular } from '@/routes/ventas';
import {
    cancelar as cancelarSolicitudRoute,
    confirmar as confirmarSolicitudRoute,
} from '@/routes/ventas/solicitudes';
import type { Cliente, PaginatedData, Venta } from '@/types';

const { can, hasRole } = usePermissions();

type ProductoVenta = {
    id: number;
    nombre: string;
    precio_venta: string;
    imagen: string | null;
    stock: number;
    categoria?: { nombre: string };
    marca?: { nombre: string };
    variantes: ProductoVentaVariante[];
};

type ProductoVentaVariante = {
    id: number;
    stock: number;
    disponible: boolean;
    talla?: { id: number; nombre: string } | null;
    color?: { id: number; nombre: string; codigo_hex?: string } | null;
};

type ProductoVentaRow = {
    product: ProductoVenta;
    variant: ProductoVentaVariante | null;
    stock: number;
};

type LineItem = {
    producto_id: number;
    producto_variante_id: number | null;
    producto: string;
    talla: string;
    color: string;
    cantidad: number;
    precio_unitario: number;
    descuento: number;
    subtotal: number;
    stock_disponible: number;
};

type SolicitudProducto = {
    producto_id: number | null;
    nombre: string;
    precio: number;
    cantidad: number;
    subtotal: number;
    descuento?: number;
    color: string | null;
    talla: string | null;
    imagen: string | null;
};

type VentaRegistrada = {
    id: number;
    numero_venta: string;
    cliente: string;
    fecha: string;
    cantidad_productos: number;
    total: number;
};

type Solicitud = {
    id: number;
    numero: string;
    fecha: string;
    estado: string;
    total: number;
    metodo_pago: string | null;
    comprobante_pago: string | null;
    cliente: {
        id?: number | null;
        nombre?: string | null;
        email?: string | null;
        telefono?: string | null;
    };
    productos: SolicitudProducto[];
};

type Props = {
    ventas: PaginatedData<Venta>;
    solicitudes: Solicitud[];
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

const page = usePage();
const solicitudError = computed(
    () =>
        (page.props.errors as Record<string, string> | undefined)?.solicitud ??
        null,
);

const solicitudErrorLocal = ref<string | null>(null);
const solicitudMensajeError = computed(
    () => solicitudErrorLocal.value ?? solicitudError.value,
);

const POLL_INTERVAL_MS = 4000;
let solicitudesPollTimer: number | null = null;

onMounted(() => {
    if (!can('pedidos.gestion')) {
        return;
    }

    solicitudesPollTimer = window.setInterval(() => {
        router.reload({ only: ['solicitudes'] });
    }, POLL_INTERVAL_MS);
});

onBeforeUnmount(() => {
    if (solicitudesPollTimer !== null) {
        window.clearInterval(solicitudesPollTimer);
    }
});

function confirmarSolicitud(solicitud: Solicitud) {
    router.post(
        confirmarSolicitudRoute(solicitud.id).url,
        {},
        {
            preserveScroll: true,
        },
    );
}

function cancelarSolicitud(solicitud: Solicitud) {
    router.post(
        cancelarSolicitudRoute(solicitud.id).url,
        {},
        {
            preserveScroll: true,
        },
    );
}

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
const editingVenta = ref<Venta | null>(null);
const solicitudOrigen = ref<Solicitud | null>(null);
const confirmandoSolicitud = ref(false);
const solicitudDetalle = ref<Solicitud | null>(null);
const detalleDialogOpen = ref(false);
const ventaRegistrada = ref<VentaRegistrada | null>(null);
const ventaSuccessOpen = ref(false);
const deleteDialogOpen = ref(false);
const ventaToDelete = ref<Venta | null>(null);
const printDialogOpen = ref(false);
const ventaToPrint = ref<Venta | null>(null);
const saleReceiptRef = ref<HTMLElement | null>(null);
const productSearch = ref('');

const selectedClienteId = ref('');
const clientSearch = ref('');
const registeringClient = ref(false);
const newClient = reactive({
    nombre: '',
    ci_nit: '',
    telefono: '',
    email: '',
    direccion: '',
});
const selectedFecha = ref(new Date().toISOString().split('T')[0]);
const selectedDetalles = ref<LineItem[]>([]);

function productRowsBySize(product: ProductoVenta): ProductoVentaRow[] {
    if (product.variantes.length === 0) {
        return [{ product, variant: null, stock: product.stock }];
    }

    const variantsBySize = new Map<string, ProductoVentaVariante>();

    product.variantes.forEach((variant) => {
        const sizeKey = variant.talla
            ? `size-${variant.talla.id}`
            : `variant-${variant.id}`;
        const currentVariant = variantsBySize.get(sizeKey);

        if (!currentVariant || variant.stock > currentVariant.stock) {
            variantsBySize.set(sizeKey, variant);
        }
    });

    return Array.from(variantsBySize.values()).map((variant) => ({
        product,
        variant,
        stock: variant.stock,
    }));
}

const filteredProductos = computed(() => {
    const s = productSearch.value.toLowerCase();
    const rows = props.productos.flatMap<ProductoVentaRow>(productRowsBySize);

    if (!s) {
        return rows;
    }

    return rows.filter(({ product, variant }) =>
        [
            product.nombre,
            product.categoria?.nombre,
            product.marca?.nombre,
            variant?.talla?.nombre,
            variant?.color?.nombre,
        ].some((value) => value?.toLowerCase().includes(s)),
    );
});

const selectedCliente = computed(() =>
    props.clientes.find((c) => c.id === Number(selectedClienteId.value)),
);

const filteredClientes = computed(() => {
    const searchTerm = clientSearch.value.trim().toLowerCase();

    if (!searchTerm) {
        return props.clientes.slice(0, 6);
    }

    return props.clientes
        .filter(
            (cliente) =>
                cliente.nombre.toLowerCase().includes(searchTerm) ||
                cliente.ci_nit?.toLowerCase().includes(searchTerm),
        )
        .slice(0, 6);
});

const canContinueClient = computed(() => {
    if (registeringClient.value) {
        return Boolean(
            newClient.nombre.trim() &&
            newClient.ci_nit.trim() &&
            newClient.telefono.trim(),
        );
    }

    return selectedClienteId.value !== '';
});

const wizardTotal = computed(() =>
    selectedDetalles.value.reduce((sum, d) => sum + d.subtotal, 0),
);

const wizardTotalDescuento = computed(() =>
    selectedDetalles.value.reduce((sum, d) => sum + d.descuento, 0),
);

const wizardSubtotal = computed(() =>
    selectedDetalles.value.reduce(
        (sum, d) => sum + d.cantidad * d.precio_unitario,
        0,
    ),
);

const wizardCantidadTotal = computed(() =>
    selectedDetalles.value.reduce((sum, d) => sum + d.cantidad, 0),
);

function openSolicitudDetalle(solicitud: Solicitud) {
    solicitudDetalle.value = solicitud;
    detalleDialogOpen.value = true;
}

function openWizard() {
    editingVenta.value = null;
    solicitudOrigen.value = null;
    confirmandoSolicitud.value = false;
    solicitudErrorLocal.value = null;
    wizardStep.value = 1;
    selectedClienteId.value = '';
    clientSearch.value = '';
    registeringClient.value = false;
    Object.assign(newClient, {
        nombre: '',
        ci_nit: '',
        telefono: '',
        email: '',
        direccion: '',
    });
    selectedFecha.value = new Date().toISOString().split('T')[0];
    selectedDetalles.value = [];
    productSearch.value = '';
    wizardOpen.value = true;
}

function openSolicitudWizard(solicitud: Solicitud) {
    editingVenta.value = null;
    solicitudOrigen.value = solicitud;
    confirmandoSolicitud.value = false;
    solicitudErrorLocal.value = null;
    detalleDialogOpen.value = false;
    wizardStep.value = 3;
    registeringClient.value = false;
    selectedClienteId.value = '';
    clientSearch.value = solicitud.cliente?.nombre ?? 'Cliente General';
    selectedFecha.value =
        solicitud.fecha?.split('T')[0] ??
        new Date().toISOString().split('T')[0];
    selectedDetalles.value = solicitud.productos.map((producto) => ({
        producto_id: producto.producto_id ?? 0,
        producto_variante_id: null,
        producto: producto.nombre,
        talla: producto.talla ?? 'Sin talla',
        color: producto.color ?? 'Sin color',
        cantidad: producto.cantidad,
        precio_unitario: producto.precio,
        descuento: producto.descuento ?? 0,
        subtotal: producto.subtotal,
        stock_disponible: producto.cantidad,
    }));
    productSearch.value = '';
    wizardOpen.value = true;
}

function cerrarSolicitudWizard() {
    wizardOpen.value = false;
    solicitudOrigen.value = null;
    solicitudErrorLocal.value = null;
}

function submitSolicitudSale() {
    if (!solicitudOrigen.value) {
        return;
    }

    confirmandoSolicitud.value = true;
    solicitudErrorLocal.value = null;

    router.post(
        confirmarSolicitudRoute(solicitudOrigen.value.id).url,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFlash: (flash) => {
                const data = (flash as { venta_registrada?: VentaRegistrada })
                    .venta_registrada;

                if (!data) {
                    return;
                }

                ventaRegistrada.value = data;
                ventaSuccessOpen.value = true;
                wizardOpen.value = false;
                solicitudOrigen.value = null;
            },
            onError: (errors) => {
                solicitudErrorLocal.value =
                    errors.solicitud ??
                    'No se pudo registrar la venta. Intente nuevamente.';
            },
            onNetworkError: () => {
                solicitudErrorLocal.value =
                    'No se pudo completar la operación. Verifique su conexión e intente nuevamente.';
            },
            onSuccess: () => {
                wizardOpen.value = false;
                solicitudOrigen.value = null;
            },
            onFinish: () => {
                confirmandoSolicitud.value = false;
            },
        },
    );
}

function aceptarVentaRegistrada() {
    ventaSuccessOpen.value = false;
    ventaRegistrada.value = null;
    router.reload({
        only: ['ventas', 'solicitudes', 'productos'],
    });
}

function openEditVenta(venta: Venta) {
    editingVenta.value = venta;
    wizardStep.value = 1;
    registeringClient.value = false;

    if (venta.cliente?.id) {
        selectedClienteId.value = String(venta.cliente.id);
        clientSearch.value = venta.cliente.ci_nit
            ? `${venta.cliente.nombre} — ${venta.cliente.ci_nit}`
            : getClientFullName(venta.cliente);
    } else {
        selectedClienteId.value = '0';
        clientSearch.value = 'Cliente General';
    }

    selectedFecha.value =
        venta.fecha?.split('T')[0] ?? new Date().toISOString().split('T')[0];
    selectedDetalles.value = (venta.detalles ?? []).map((detalle) => ({
        producto_id: detalle.producto_id ?? 0,
        producto_variante_id: detalle.producto_variante_id ?? null,
        producto: detalle.producto,
        talla: detalle.talla ?? detalle.variante?.talla?.nombre ?? 'Sin talla',
        color: detalle.color ?? detalle.variante?.color?.nombre ?? 'Sin color',
        cantidad: Number(detalle.cantidad),
        precio_unitario: Number(detalle.precio_unitario),
        descuento: Number(detalle.descuento ?? 0),
        subtotal: Number(detalle.subtotal),
        stock_disponible: Number(detalle.cantidad),
    }));
    productSearch.value = '';
    wizardOpen.value = true;
}

function selectClient(cliente: Cliente) {
    selectedClienteId.value = String(cliente.id);
    clientSearch.value = cliente.ci_nit
        ? `${cliente.nombre} — ${cliente.ci_nit}`
        : cliente.nombre;
    registeringClient.value = false;
}

function selectGeneralClient() {
    selectedClienteId.value = '0';
    clientSearch.value = 'Cliente General';
    registeringClient.value = false;
}

function startNewClient() {
    selectedClienteId.value = '';
    registeringClient.value = true;
}

function addProduct(row: ProductoVentaRow) {
    const { product, variant, stock } = row;
    const existing = selectedDetalles.value.find(
        (d) =>
            d.producto_id === product.id &&
            d.producto_variante_id === (variant?.id ?? null),
    );

    if (existing) {
        if (existing.cantidad < stock) {
            existing.cantidad++;
            computeLineSubtotal(existing);
        }

        return;
    }

    selectedDetalles.value.push({
        producto_id: product.id,
        producto_variante_id: variant?.id ?? null,
        producto: product.nombre,
        talla: variant?.talla?.nombre ?? 'Sin talla',
        color: variant?.color?.nombre ?? 'Sin color',
        cantidad: 1,
        precio_unitario: parseFloat(product.precio_venta),
        descuento: 0,
        subtotal: parseFloat(product.precio_venta),
        stock_disponible: stock,
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
    item.subtotal = Math.max(
        0,
        item.cantidad * item.precio_unitario - item.descuento,
    );
}

function saleDiscount(venta: Venta): number {
    return venta.detalles.reduce(
        (total, detalle) => total + Number(detalle.descuento),
        0,
    );
}

const saleForm = useForm({
    cliente_id: null as number | null,
    cliente_nuevo: null as {
        nombre: string;
        ci_nit: string;
        telefono: string;
        email: string;
        direccion: string;
    } | null,
    fecha: '',
    detalles: [] as Array<{
        producto_id: number;
        producto_variante_id: number | null;
        cantidad: number;
        precio_unitario: number;
        descuento: number;
    }>,
});

function submitSale() {
    if (solicitudOrigen.value) {
        submitSolicitudSale();

        return;
    }

    if (editingVenta.value) {
        saleForm.cliente_id = registeringClient.value
            ? null
            : Number(selectedClienteId.value);
        saleForm.cliente_nuevo = null;
        saleForm.fecha = selectedFecha.value;
        saleForm.detalles = selectedDetalles.value.map((d) => ({
            producto_id: d.producto_id,
            producto_variante_id: d.producto_variante_id,
            cantidad: d.cantidad,
            precio_unitario: d.precio_unitario,
            descuento: d.descuento,
        }));

        saleForm.patch(update(editingVenta.value.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                wizardOpen.value = false;
                editingVenta.value = null;
                saleForm.reset();
            },
        });

        return;
    }

    saleForm.cliente_id = registeringClient.value
        ? null
        : Number(selectedClienteId.value);
    saleForm.cliente_nuevo = registeringClient.value ? { ...newClient } : null;
    saleForm.fecha = selectedFecha.value;
    saleForm.detalles = selectedDetalles.value.map((d) => ({
        producto_id: d.producto_id,
        producto_variante_id: d.producto_variante_id,
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
    if (step === 2 && !canContinueClient.value) {
        return;
    }

    if (step === 3 && selectedDetalles.value.length === 0) {
        return;
    }

    wizardStep.value = step;
}

function confirmDelete(venta: Venta) {
    ventaToDelete.value = venta;
    deleteDialogOpen.value = true;
}

function submitAnular() {
    if (!ventaToDelete.value) {
        return;
    }

    const id = ventaToDelete.value.id;
    deleteDialogOpen.value = false;
    router.post(
        anular(id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                ventaToDelete.value = null;
            },
        },
    );
}

function openPrintDialog(venta: Venta) {
    ventaToPrint.value = venta;
    printDialogOpen.value = true;
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
        return '---';
    }

    return new Intl.DateTimeFormat('es-BO', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone: 'America/La_Paz',
    })
        .format(new Date(date))
        .replace('.', '');
}

function resolveImageUrl(imagen: string | null): string {
    if (!imagen) {
        return '';
    }

    if (imagen.startsWith('data:') || imagen.startsWith('blob:')) {
        return imagen;
    }

    const storageMatch = imagen.match(/\/storage\/(.+)$/);

    if (storageMatch) {
        return `/storage/${storageMatch[1]}`;
    }

    if (
        /^https?:\/\//i.test(imagen) ||
        imagen.startsWith('//') ||
        imagen.startsWith('/')
    ) {
        return imagen;
    }

    return `/storage/${imagen.replace(/^\/+/, '')}`;
}

function esPdf(ruta: string | null): boolean {
    return Boolean(ruta && ruta.toLowerCase().endsWith('.pdf'));
}

function getFullName(user: Venta['user']): string {
    if (!user) {
        return '---';
    }

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

function getClientFullName(cliente: Venta['cliente']): string {
    if (!cliente) {
        return '---';
    }

    return [cliente.nombre, cliente.apellido].filter(Boolean).join(' ');
}

async function printComprobante(): Promise<void> {
    await nextTick();

    if (!saleReceiptRef.value) {
        return;
    }

    document.querySelector('#sale-print-root')?.remove();

    const printRoot = document.createElement('div');
    printRoot.id = 'sale-print-root';
    printRoot.appendChild(saleReceiptRef.value.cloneNode(true));
    document.body.appendChild(printRoot);

    const cleanPrintRoot = (): void => {
        printRoot.remove();
    };

    window.addEventListener('afterprint', cleanPrintRoot, { once: true });

    requestAnimationFrame(() => {
        requestAnimationFrame(() => window.print());
    });
}
</script>

<template>
    <Head title="Ventas" />

    <div
        class="flex h-full flex-1 flex-col gap-5 rounded-xl bg-gradient-to-br from-violet-100 via-fuchsia-50 to-pink-100 p-4"
    >
        <ManagementHero
            title="Gestión de Ventas"
            description="Consulta todas las ventas y utiliza el buscador para filtrar."
        >
            <template #icon><TrendingUp class="h-7 w-7" /></template>
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
        </ManagementHero>

        <VentasClientesTabs active="ventas" />

        <div
            v-if="props.solicitudes.length > 0"
            class="overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 via-orange-50 to-rose-50 shadow-lg shadow-amber-200/50"
        >
            <div
                class="flex flex-col gap-2 border-b border-amber-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-500 text-white"
                    >
                        <Package class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-amber-900">
                            Solicitudes de compra pendientes ({{
                                props.solicitudes.length
                            }})
                        </h2>
                        <p class="text-xs text-amber-700">
                            {{
                                hasRole('admin')
                                    ? 'El stock se descuenta solo cuando confirmas la solicitud.'
                                    : 'Revisa la solicitud y regístrala como venta.'
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="solicitudMensajeError"
                class="mx-4 mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700"
            >
                {{ solicitudMensajeError }}
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-amber-200 bg-amber-100/60 text-left text-amber-900"
                        >
                            <th class="px-4 py-2 font-medium">Solicitud</th>
                            <th class="px-4 py-2 font-medium">Cliente</th>
                            <th class="px-4 py-2 font-medium">Productos</th>
                            <th class="px-4 py-2 font-medium">Total</th>
                            <th class="px-4 py-2 font-medium">Fecha</th>
                            <th class="px-4 py-2 text-right font-medium">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="solicitud in props.solicitudes"
                            :key="solicitud.id"
                            class="border-b border-amber-100 last:border-0"
                        >
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs font-semibold">{{
                                    solicitud.numero
                                }}</span>
                                <p class="text-[11px] text-amber-700">
                                    {{ solicitud.metodo_pago ?? '---' }}
                                </p>
                                <a
                                    v-if="solicitud.comprobante_pago"
                                    :href="
                                        resolveImageUrl(
                                            solicitud.comprobante_pago,
                                        )
                                    "
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-2 inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 transition-colors hover:bg-emerald-100"
                                >
                                    <img
                                        v-if="
                                            !esPdf(solicitud.comprobante_pago)
                                        "
                                        :src="
                                            resolveImageUrl(
                                                solicitud.comprobante_pago,
                                            )
                                        "
                                        alt="Comprobante de pago"
                                        class="h-8 w-8 rounded object-cover"
                                    />
                                    Ver comprobante
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800">
                                    {{ solicitud.cliente?.nombre ?? '---' }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ solicitud.cliente?.email ?? '' }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-2">
                                    <div
                                        v-for="(
                                            producto, index
                                        ) in solicitud.productos"
                                        :key="`${solicitud.id}-${index}`"
                                        class="flex items-center gap-2"
                                    >
                                        <div
                                            class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-amber-100 bg-white"
                                        >
                                            <img
                                                v-if="producto.imagen"
                                                :src="
                                                    resolveImageUrl(
                                                        producto.imagen,
                                                    )
                                                "
                                                :alt="producto.nombre"
                                                class="h-full w-full object-cover"
                                            />
                                            <div
                                                v-else
                                                class="flex h-full w-full items-center justify-center text-amber-400"
                                            >
                                                <Package class="h-5 w-5" />
                                            </div>
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-xs font-semibold text-slate-700"
                                            >
                                                {{ producto.nombre }}
                                            </p>
                                            <p
                                                class="text-[11px] text-slate-500"
                                            >
                                                Cant: {{ producto.cantidad }}
                                                <span v-if="producto.talla">
                                                    · Talla:
                                                    {{ producto.talla }}
                                                </span>
                                                <span v-if="producto.color">
                                                    · Color:
                                                    {{ producto.color }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-800">
                                {{ formatCurrency(solicitud.total) }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ formatDate(solicitud.fecha) }}
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="openSolicitudDetalle(solicitud)"
                                    >
                                        <Eye class="mr-1 h-3.5 w-3.5" />
                                        Ver venta
                                    </Button>
                                    <Button
                                        v-if="
                                            hasRole('admin') &&
                                            can('pedidos.gestion')
                                        "
                                        size="sm"
                                        class="bg-emerald-600 text-white hover:bg-emerald-700"
                                        @click="confirmarSolicitud(solicitud)"
                                    >
                                        <Check class="mr-1 h-3.5 w-3.5" />
                                        Confirmar
                                    </Button>
                                    <Button
                                        v-if="
                                            hasRole('admin') &&
                                            can('pedidos.gestion')
                                        "
                                        size="sm"
                                        variant="outline"
                                        class="border-red-200 text-red-600 hover:bg-red-50"
                                        @click="cancelarSolicitud(solicitud)"
                                    >
                                        <X class="mr-1 h-3.5 w-3.5" />
                                        Cancelar
                                    </Button>
                                    <Button
                                        v-if="
                                            !hasRole('admin') &&
                                            can('ventas.crear')
                                        "
                                        size="sm"
                                        class="bg-emerald-600 text-white hover:bg-emerald-700"
                                        @click="openSolicitudWizard(solicitud)"
                                    >
                                        <ShoppingCart
                                            class="mr-1 h-3.5 w-3.5"
                                        />
                                        Realizar venta
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            class="flex items-center gap-2 rounded-xl border border-fuchsia-200 bg-gradient-to-r from-pink-100/95 via-fuchsia-50 to-violet-100/95 p-4 shadow-md"
        >
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Buscar por cliente, producto, número de venta o descripción..."
                    class="pl-10"
                />
            </div>
            <Button variant="outline" @click="clearSearch"> Limpiar </Button>
        </div>

        <div
            class="overflow-hidden rounded-2xl border border-fuchsia-200 bg-gradient-to-br from-pink-100/95 via-fuchsia-50/95 to-violet-100/95 shadow-xl shadow-violet-300/40"
        >
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-violet-200 bg-gradient-to-r from-violet-200 via-pink-100 to-fuchsia-200 text-left"
                    >
                        <th class="px-4 py-3 font-medium">Cliente</th>
                        <th class="px-4 py-3 font-medium">Productos</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Descuento</th>
                        <th class="px-4 py-3 font-medium">Fecha</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="venta in ventas.data"
                        :key="venta.id"
                        class="border-b border-fuchsia-100 bg-pink-50/60 transition-colors last:border-0 hover:bg-violet-100/70"
                    >
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10"
                                >
                                    <User class="h-4 w-4 text-primary" />
                                </div>
                                <div>
                                    <p class="font-semibold">
                                        {{ getClientFullName(venta.cliente) }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="grid gap-1">
                                <div
                                    v-for="detalle in venta.detalles"
                                    :key="detalle.id"
                                >
                                    <p class="text-sm font-medium">
                                        {{ detalle.producto }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Stock: {{ detalle.cantidad }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-medium">{{
                                formatCurrency(venta.total)
                            }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-emerald-600">
                                {{ formatCurrency(saleDiscount(venta)) }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs">{{
                                formatDate(venta.fecha)
                            }}</span>
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
                                                @click="
                                                    router.visit(
                                                        `/ventas/${venta.id}`,
                                                    )
                                                "
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent
                                            ><p>Ver venta</p></TooltipContent
                                        >
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="outline"
                                                size="icon-sm"
                                                class="border-amber-200 text-amber-600 hover:bg-amber-50"
                                                @click="openPrintDialog(venta)"
                                            >
                                                <Printer class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent
                                            ><p>Imprimir</p></TooltipContent
                                        >
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider
                                    v-if="can('ventas.crear')"
                                    :delay-duration="0"
                                >
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="outline"
                                                size="icon-sm"
                                                class="border-blue-200 text-blue-600 hover:bg-blue-50"
                                                @click="openEditVenta(venta)"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent
                                            ><p>
                                                {{
                                                    venta.estado === 'anulada'
                                                        ? 'Reactivar y editar venta'
                                                        : 'Editar venta'
                                                }}
                                            </p></TooltipContent
                                        >
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider
                                    v-if="
                                        can('ventas.crear') &&
                                        venta.estado !== 'anulada'
                                    "
                                    :delay-duration="0"
                                >
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="outline"
                                                size="icon-sm"
                                                class="border-red-200 text-red-500 hover:bg-red-50"
                                                @click="confirmDelete(venta)"
                                            >
                                                <X class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent
                                            ><p>Anular venta</p></TooltipContent
                                        >
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="ventas.data.length === 0">
                        <td
                            colspan="7"
                            class="py-12 text-center text-sm text-muted-foreground"
                        >
                            <ShoppingCart
                                class="mx-auto mb-2 h-8 w-8 opacity-40"
                            />
                            <p class="font-medium">
                                No hay ventas registradas.
                            </p>
                            <p>Crea una nueva venta para comenzar.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="ventas.last_page > 1"
            class="flex items-center justify-between"
        >
            <p class="text-sm text-muted-foreground">
                Mostrando {{ ventas.from }}–{{ ventas.to }} de
                {{ ventas.total }} ventas
            </p>
            <div class="flex items-center gap-2">
                <Link
                    :href="ventas.prev_page_url ?? '#'"
                    :class="[
                        'rounded-lg border px-3 py-1.5 text-sm transition-colors',
                        ventas.prev_page_url
                            ? 'hover:bg-muted'
                            : 'pointer-events-none opacity-40',
                    ]"
                >
                    Anterior
                </Link>
                <Link
                    :href="ventas.next_page_url ?? '#'"
                    :class="[
                        'rounded-lg border px-3 py-1.5 text-sm transition-colors',
                        ventas.next_page_url
                            ? 'hover:bg-muted'
                            : 'pointer-events-none opacity-40',
                    ]"
                >
                    Siguiente
                </Link>
            </div>
        </div>
    </div>

    <!-- ═══════ WIZARD NUEVA VENTA ═══════ -->
    <Dialog v-model:open="wizardOpen">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
            <DialogHeader>
                <DialogTitle>{{
                    editingVenta
                        ? editingVenta.estado === 'anulada'
                            ? `Reactivar venta ${editingVenta.numero_venta ?? '#' + editingVenta.id}`
                            : `Editar venta ${editingVenta.numero_venta ?? '#' + editingVenta.id}`
                        : 'Nueva Venta'
                }}</DialogTitle>
                <DialogDescription>
                    <span v-if="wizardStep === 1"
                        >Selecciona el cliente para la venta.</span
                    >
                    <span v-else-if="wizardStep === 2"
                        >Selecciona los productos que deseas vender.</span
                    >
                    <span v-else
                        >Revisa el resumen antes de
                        {{
                            editingVenta
                                ? editingVenta.estado === 'anulada'
                                    ? 'reactivar la venta'
                                    : 'guardar los cambios'
                                : 'registrar la venta'
                        }}.</span
                    >
                </DialogDescription>
            </DialogHeader>

            <!-- Steps indicator -->
            <div class="flex items-center justify-center gap-4 py-2">
                <div class="flex items-center gap-2">
                    <div
                        :class="[
                            'flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold',
                            wizardStep >= 1
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground',
                        ]"
                    >
                        1
                    </div>
                    <span class="text-xs font-medium">Cliente</span>
                </div>
                <div class="h-px w-8 bg-border" />
                <div class="flex items-center gap-2">
                    <div
                        :class="[
                            'flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold',
                            wizardStep >= 2
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground',
                        ]"
                    >
                        2
                    </div>
                    <span class="text-xs font-medium">Productos</span>
                </div>
                <div class="h-px w-8 bg-border" />
                <div class="flex items-center gap-2">
                    <div
                        :class="[
                            'flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold',
                            wizardStep >= 3
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground',
                        ]"
                    >
                        3
                    </div>
                    <span class="text-xs font-medium">Resumen</span>
                </div>
            </div>

            <!-- STEP 1: Select or register client -->
            <div v-if="wizardStep === 1" class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="client_search">Buscar cliente</Label>
                    <div class="relative">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            id="client_search"
                            v-model="clientSearch"
                            class="pl-10"
                            placeholder="Escribe el nombre o CI/NIT"
                            @input="selectedClienteId = ''"
                        />
                    </div>

                    <div
                        v-if="!registeringClient"
                        class="max-h-48 space-y-1 overflow-y-auto rounded-lg border p-2"
                    >
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm transition-colors hover:bg-muted"
                            :class="{
                                'bg-primary/10 text-primary':
                                    selectedClienteId === '0',
                            }"
                            @click="selectGeneralClient"
                        >
                            <User class="h-4 w-4" />
                            <span>Cliente General</span>
                        </button>
                        <button
                            v-for="cliente in filteredClientes"
                            :key="cliente.id"
                            type="button"
                            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition-colors hover:bg-muted"
                            :class="{
                                'bg-primary/10 text-primary':
                                    selectedClienteId === String(cliente.id),
                            }"
                            @click="selectClient(cliente)"
                        >
                            <span class="font-medium">{{
                                cliente.nombre
                            }}</span>
                            <span
                                v-if="cliente.ci_nit"
                                class="text-xs text-muted-foreground"
                            >
                                CI/NIT: {{ cliente.ci_nit }}
                            </span>
                        </button>
                        <p
                            v-if="filteredClientes.length === 0"
                            class="px-3 py-2 text-sm text-muted-foreground"
                        >
                            No se encontró el cliente. Puedes registrarlo aquí.
                        </p>
                    </div>

                    <Button
                        v-if="!registeringClient"
                        type="button"
                        variant="outline"
                        class="justify-start"
                        @click="startNewClient"
                    >
                        <Plus class="mr-2 h-4 w-4" />
                        Registrar cliente nuevo
                    </Button>
                </div>

                <div
                    v-if="registeringClient"
                    class="grid gap-3 rounded-lg border bg-muted/20 p-4 sm:grid-cols-2"
                >
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="new_client_name">Nombre completo</Label>
                        <Input
                            id="new_client_name"
                            v-model="newClient.nombre"
                            placeholder="Nombre completo del cliente"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="new_client_ci">CI/NIT</Label>
                        <Input
                            id="new_client_ci"
                            v-model="newClient.ci_nit"
                            placeholder="CI o NIT"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="new_client_phone">Teléfono</Label>
                        <Input
                            id="new_client_phone"
                            v-model="newClient.telefono"
                            placeholder="Número de teléfono"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="new_client_email">Email</Label>
                        <Input
                            id="new_client_email"
                            v-model="newClient.email"
                            type="email"
                            placeholder="cliente@email.com"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="new_client_address">Dirección</Label>
                        <Input
                            id="new_client_address"
                            v-model="newClient.direccion"
                            placeholder="Dirección del cliente"
                        />
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        class="justify-start sm:col-span-2"
                        @click="registeringClient = false"
                    >
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver a buscar clientes
                    </Button>
                </div>
                <div class="grid gap-2">
                    <Label for="fecha">Fecha</Label>
                    <Input id="fecha" type="date" v-model="selectedFecha" />
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline"
                            >Cancelar</Button
                        >
                    </DialogClose>
                    <Button :disabled="!canContinueClient" @click="goToStep(2)">
                        Siguiente
                        <ArrowRight class="ml-2 h-4 w-4" />
                    </Button>
                </DialogFooter>
            </div>

            <!-- STEP 2: Select Products -->
            <div v-if="wizardStep === 2" class="grid gap-4 py-4">
                <template v-if="!solicitudOrigen">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <Search
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="productSearch"
                                placeholder="Buscar por nombre de la prenda, marca, categoría o descripción..."
                                class="pl-10"
                            />
                        </div>
                    </div>

                    <div
                        v-if="props.productos.length === 0"
                        class="py-8 text-center"
                    >
                        <Package
                            class="mx-auto mb-3 h-10 w-10 text-muted-foreground opacity-40"
                        />
                        <p class="font-medium text-muted-foreground">
                            No se encontraron productos.
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Primero registre productos en el módulo Gestión de
                            Productos para poder realizar ventas.
                        </p>
                    </div>

                    <div
                        v-else-if="
                            filteredProductos.length === 0 && productSearch
                        "
                        class="py-8 text-center"
                    >
                        <Package
                            class="mx-auto mb-3 h-10 w-10 text-muted-foreground opacity-40"
                        />
                        <p class="text-sm text-muted-foreground">
                            No se encontraron productos para "{{
                                productSearch
                            }}".
                        </p>
                    </div>

                    <div
                        v-else
                        class="max-h-[300px] overflow-y-auto rounded-lg border"
                    >
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-background">
                                <tr class="border-b text-left">
                                    <th class="px-3 py-2 font-medium">
                                        Producto
                                    </th>
                                    <th class="px-3 py-2 font-medium">
                                        Categoría
                                    </th>
                                    <th class="px-3 py-2 font-medium">Marca</th>
                                    <th
                                        class="px-3 py-2 text-center font-medium"
                                    >
                                        Talla
                                    </th>
                                    <th class="px-3 py-2 font-medium">Color</th>
                                    <th
                                        class="px-3 py-2 text-right font-medium"
                                    >
                                        Precio
                                    </th>
                                    <th
                                        class="px-3 py-2 text-center font-medium"
                                    >
                                        Stock
                                    </th>
                                    <th
                                        class="px-3 py-2 text-right font-medium"
                                    >
                                        Acción
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in filteredProductos"
                                    :key="`${row.product.id}-${row.variant?.id ?? 'principal'}`"
                                    class="border-b text-left transition-colors last:border-0 hover:bg-muted/30"
                                >
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <div
                                                v-if="row.product.imagen"
                                                class="h-8 w-8 overflow-hidden rounded bg-muted"
                                            >
                                                <img
                                                    :src="`/storage/${row.product.imagen}`"
                                                    :alt="row.product.nombre"
                                                    class="h-full w-full object-cover"
                                                />
                                            </div>
                                            <div
                                                v-else
                                                class="flex h-8 w-8 items-center justify-center rounded bg-muted"
                                            >
                                                <Package
                                                    class="h-4 w-4 text-muted-foreground"
                                                />
                                            </div>
                                            <div>
                                                <p class="font-medium">
                                                    {{ row.product.nombre }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-xs">
                                        {{
                                            row.product.categoria?.nombre ??
                                            '---'
                                        }}
                                    </td>
                                    <td class="px-3 py-2 text-xs">
                                        {{ row.product.marca?.nombre ?? '---' }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <Badge
                                            variant="secondary"
                                            class="text-xs"
                                        >
                                            {{
                                                row.variant?.talla?.nombre ??
                                                '—'
                                            }}
                                        </Badge>
                                    </td>
                                    <td class="px-3 py-2 text-xs">
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                        >
                                            <span
                                                class="h-3 w-3 rounded-full border border-slate-300"
                                                :style="{
                                                    backgroundColor:
                                                        row.variant?.color
                                                            ?.codigo_hex ??
                                                        '#a78bfa',
                                                }"
                                            />
                                            {{
                                                row.variant?.color?.nombre ??
                                                '—'
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-3 py-2 text-right text-xs font-medium"
                                    >
                                        {{
                                            formatCurrency(
                                                row.product.precio_venta,
                                            )
                                        }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <Badge
                                            :class="[
                                                'text-xs',
                                                row.stock > 0
                                                    ? 'border-green-200 text-green-600'
                                                    : 'border-red-200 text-red-600',
                                            ]"
                                            variant="outline"
                                        >
                                            {{ row.stock }}
                                        </Badge>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :disabled="row.stock <= 0"
                                            @click="addProduct(row)"
                                        >
                                            <Plus class="mr-1 h-3 w-3" />
                                            Agregar
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <p v-if="solicitudOrigen" class="text-xs text-muted-foreground">
                    Revisa los productos de la solicitud. La venta se registrará
                    con la información original enviada por el cliente.
                </p>

                <!-- Selected items -->
                <div
                    v-if="selectedDetalles.length > 0"
                    class="rounded-lg border p-3"
                >
                    <p class="mb-2 text-xs font-medium">
                        Productos seleccionados ({{ selectedDetalles.length }})
                    </p>
                    <div
                        v-for="(item, index) in selectedDetalles"
                        :key="`${item.producto_id}-${item.producto_variante_id ?? 'principal'}`"
                        class="flex items-center gap-3 border-b py-2 last:border-0"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ item.producto }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Talla {{ item.talla }} · {{ item.color }} ·
                                P.U.:
                                {{ formatCurrency(item.precio_unitario) }}
                            </p>
                        </div>
                        <div
                            v-if="!solicitudOrigen"
                            class="flex items-center gap-1"
                        >
                            <Button
                                variant="outline"
                                size="icon"
                                class="h-7 w-7"
                                @click="decrementCantidad(item)"
                            >
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
                            <Button
                                variant="outline"
                                size="icon"
                                class="h-7 w-7"
                                @click="incrementCantidad(item)"
                                :disabled="
                                    item.cantidad >= item.stock_disponible
                                "
                            >
                                <Plus class="h-3 w-3" />
                            </Button>
                        </div>
                        <p v-else class="text-sm font-medium">
                            Cantidad: {{ item.cantidad }}
                        </p>
                        <div
                            v-if="!solicitudOrigen"
                            class="flex w-40 items-center gap-2"
                        >
                            <span class="text-xs text-muted-foreground">
                                Descuento
                            </span>
                            <Input
                                v-model.number="item.descuento"
                                type="number"
                                min="0"
                                class="h-7 w-24 text-right text-xs"
                                placeholder="Desc."
                                @update:model-value="computeLineSubtotal(item)"
                            />
                        </div>
                        <p v-else class="text-xs text-muted-foreground">
                            Descuento: {{ formatCurrency(item.descuento) }}
                        </p>
                        <span class="w-24 text-right text-sm font-medium">{{
                            formatCurrency(item.subtotal)
                        }}</span>
                        <Button
                            v-if="!solicitudOrigen"
                            variant="ghost"
                            size="icon"
                            class="h-7 w-7 text-red-500"
                            @click="removeProduct(index)"
                        >
                            <Trash2 class="h-3 w-3" />
                        </Button>
                    </div>
                </div>

                <DialogFooter>
                    <template v-if="solicitudOrigen">
                        <Button variant="outline" @click="goToStep(3)">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Volver al resumen
                        </Button>
                    </template>
                    <template v-else>
                        <Button variant="outline" @click="goToStep(1)">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Atrás
                        </Button>
                        <Button
                            :disabled="selectedDetalles.length === 0"
                            @click="goToStep(3)"
                        >
                            Siguiente
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </template>
                </DialogFooter>
            </div>

            <!-- STEP 3: Summary -->
            <div v-if="wizardStep === 3" class="grid gap-4 py-4">
                <div class="rounded-lg border p-4">
                    <h4 class="mb-3 text-sm font-semibold">
                        Resumen de la Venta
                    </h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Cliente:</span>
                            <span class="font-medium">
                                {{
                                    solicitudOrigen
                                        ? (solicitudOrigen.cliente?.nombre ??
                                          'Cliente General')
                                        : registeringClient
                                          ? newClient.nombre
                                          : selectedCliente
                                            ? `${selectedCliente.nombre}${selectedCliente.apellido ? ' ' + selectedCliente.apellido : ''}`
                                            : 'Cliente General'
                                }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Fecha:</span>
                            <span class="font-medium">{{
                                formatDate(selectedFecha)
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Productos:</span
                            >
                            <span class="font-medium">{{
                                selectedDetalles.length
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Cantidad total:</span
                            >
                            <span class="font-medium">{{
                                wizardCantidadTotal
                            }}</span>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50 text-left">
                                <th class="px-3 py-2 font-medium">Producto</th>
                                <th class="px-3 py-2 font-medium">Variante</th>
                                <th class="px-3 py-2 text-center font-medium">
                                    Cant.
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    P. Unit.
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    Descuento
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    Subtotal
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in selectedDetalles"
                                :key="`${item.producto_id}-${item.producto_variante_id ?? 'principal'}`"
                                class="border-b last:border-0"
                            >
                                <td class="px-3 py-2 font-medium">
                                    {{ item.producto }}
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    Talla {{ item.talla }} · {{ item.color }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    {{ item.cantidad }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    {{ formatCurrency(item.precio_unitario) }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    {{ formatCurrency(item.descuento) }}
                                </td>
                                <td class="px-3 py-2 text-right font-medium">
                                    {{ formatCurrency(item.subtotal) }}
                                </td>
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
                        <span class="text-muted-foreground"
                            >Descuento total:</span
                        >
                        <span>-{{ formatCurrency(wizardTotalDescuento) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold">
                        <span>Total General:</span>
                        <span>{{ formatCurrency(wizardTotal) }}</span>
                    </div>
                </div>

                <div
                    v-if="solicitudMensajeError"
                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700"
                >
                    {{ solicitudMensajeError }}
                </div>

                <DialogFooter>
                    <template v-if="solicitudOrigen">
                        <Button variant="outline" @click="goToStep(2)">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Atrás
                        </Button>
                        <Button
                            variant="outline"
                            @click="cerrarSolicitudWizard"
                        >
                            Cancelar
                        </Button>
                    </template>
                    <Button v-else variant="outline" @click="goToStep(2)">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Atrás
                    </Button>
                    <Button
                        :disabled="
                            solicitudOrigen
                                ? confirmandoSolicitud
                                : saleForm.processing ||
                                  selectedDetalles.length === 0
                        "
                        @click="submitSale"
                    >
                        <Check class="mr-2 h-4 w-4" />
                        {{
                            solicitudOrigen
                                ? confirmandoSolicitud
                                    ? 'Registrando...'
                                    : 'Registrar Venta'
                                : saleForm.processing
                                  ? editingVenta
                                      ? editingVenta.estado === 'anulada'
                                          ? 'Reactivando...'
                                          : 'Guardando...'
                                      : 'Registrando...'
                                  : editingVenta
                                    ? editingVenta.estado === 'anulada'
                                        ? 'Reactivar venta'
                                        : 'Guardar cambios'
                                    : 'Registrar Venta'
                        }}
                    </Button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>

    <!-- ═══════ DETALLE SOLICITUD (SOLO LECTURA) ═══════ -->
    <Dialog v-model:open="detalleDialogOpen">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    Detalle de la solicitud
                    {{
                        solicitudDetalle?.numero
                            ? `#${solicitudDetalle.numero}`
                            : ''
                    }}
                </DialogTitle>
                <DialogDescription>
                    Información enviada por el cliente. Solo lectura.
                </DialogDescription>
            </DialogHeader>

            <div v-if="solicitudDetalle" class="space-y-4 py-2 text-sm">
                <div class="grid gap-3 rounded-lg border p-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-muted-foreground">Cliente</p>
                        <p class="font-medium">
                            {{
                                solicitudDetalle.cliente?.nombre ??
                                'Cliente General'
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Correo</p>
                        <p class="font-medium">
                            {{ solicitudDetalle.cliente?.email ?? '---' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Teléfono</p>
                        <p class="font-medium">
                            {{ solicitudDetalle.cliente?.telefono ?? '---' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Fecha</p>
                        <p class="font-medium">
                            {{ formatDate(solicitudDetalle.fecha) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">
                            Método de pago
                        </p>
                        <p class="font-medium">
                            {{ solicitudDetalle.metodo_pago ?? '---' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Estado</p>
                        <Badge variant="secondary">
                            {{ solicitudDetalle.estado }}
                        </Badge>
                    </div>
                </div>

                <div
                    v-if="solicitudDetalle.comprobante_pago"
                    class="rounded-lg border p-4"
                >
                    <p class="text-xs text-muted-foreground">
                        Comprobante de pago
                    </p>
                    <img
                        v-if="!esPdf(solicitudDetalle.comprobante_pago)"
                        :src="
                            resolveImageUrl(solicitudDetalle.comprobante_pago)
                        "
                        alt="Comprobante de pago"
                        class="mt-2 max-h-96 rounded-lg border object-contain"
                    />
                    <a
                        :href="
                            resolveImageUrl(solicitudDetalle.comprobante_pago)
                        "
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-emerald-700 underline"
                    >
                        Abrir comprobante
                    </a>
                </div>

                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/40 text-left">
                            <tr>
                                <th class="px-3 py-2 font-medium">Producto</th>
                                <th class="px-3 py-2 text-center font-medium">
                                    Talla
                                </th>
                                <th class="px-3 py-2 font-medium">Color</th>
                                <th class="px-3 py-2 text-center font-medium">
                                    Cant.
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    P. Unit.
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    Desc.
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    Subtotal
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(
                                    producto, index
                                ) in solicitudDetalle.productos"
                                :key="index"
                                class="border-t"
                            >
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <div
                                            v-if="producto.imagen"
                                            class="h-9 w-9 overflow-hidden rounded bg-muted"
                                        >
                                            <img
                                                :src="
                                                    resolveImageUrl(
                                                        producto.imagen,
                                                    )
                                                "
                                                :alt="producto.nombre"
                                                class="h-full w-full object-cover"
                                            />
                                        </div>
                                        <div
                                            v-else
                                            class="flex h-9 w-9 items-center justify-center rounded bg-muted"
                                        >
                                            <Package
                                                class="h-4 w-4 text-muted-foreground"
                                            />
                                        </div>
                                        <span class="font-medium">{{
                                            producto.nombre
                                        }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    {{ producto.talla ?? '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ producto.color ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    {{ producto.cantidad }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    {{ formatCurrency(producto.precio) }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    {{
                                        formatCurrency(producto.descuento ?? 0)
                                    }}
                                </td>
                                <td class="px-3 py-2 text-right font-medium">
                                    {{ formatCurrency(producto.subtotal) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end">
                    <div
                        class="flex items-center gap-3 rounded-lg bg-muted/50 px-4 py-2"
                    >
                        <span class="text-sm text-muted-foreground"
                            >Total:</span
                        >
                        <span class="text-lg font-bold">{{
                            formatCurrency(solicitudDetalle.total)
                        }}</span>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button @click="detalleDialogOpen = false">Cerrar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- ═══════ VENTA REGISTRADA ═══════ -->
    <Dialog v-model:open="ventaSuccessOpen">
        <DialogContent
            :show-close-button="false"
            class="max-w-md gap-0 px-8 py-8 text-center"
        >
            <div
                class="mx-auto flex size-20 items-center justify-center rounded-full border-4 border-emerald-100 text-emerald-500"
            >
                <Check class="size-12 stroke-[1.75]" />
            </div>
            <DialogTitle class="mt-6 text-2xl font-bold text-neutral-700">
                ¡Venta realizada exitosamente!
            </DialogTitle>
            <DialogDescription class="mt-2 text-sm text-neutral-600">
                La venta se ha registrado correctamente y el inventario se ha
                actualizado.
            </DialogDescription>

            <div
                v-if="ventaRegistrada"
                class="mt-6 space-y-2 rounded-lg border bg-muted/30 p-4 text-left text-sm"
            >
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground">Número de venta</span>
                    <span class="font-mono font-semibold">{{
                        ventaRegistrada.numero_venta
                    }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground">Cliente</span>
                    <span class="font-medium">{{
                        ventaRegistrada.cliente
                    }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground">Fecha</span>
                    <span class="font-medium">{{
                        formatDate(ventaRegistrada.fecha)
                    }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground">
                        Cantidad de productos
                    </span>
                    <span class="font-medium">{{
                        ventaRegistrada.cantidad_productos
                    }}</span>
                </div>
                <div class="flex justify-between gap-4 border-t pt-2">
                    <span class="text-muted-foreground">Total de la venta</span>
                    <span class="font-bold">{{
                        formatCurrency(ventaRegistrada.total)
                    }}</span>
                </div>
            </div>

            <Button
                class="mx-auto mt-6 bg-emerald-500 px-7 text-white hover:bg-emerald-600"
                @click="aceptarVentaRegistrada"
            >
                Aceptar
            </Button>
        </DialogContent>
    </Dialog>

    <!-- ═══════ CONFIRM ANULAR ═══════ -->
    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Anular Venta</DialogTitle>
                <DialogDescription>
                    ¿Está seguro de anular la venta
                    <strong>{{ ventaToDelete?.numero_venta }}</strong
                    >? Se devolverá automáticamente el stock de todos los
                    productos vendidos.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cancelar</Button>
                </DialogClose>
                <Button variant="destructive" @click="submitAnular"
                    >Anular Venta</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- ═══════ PRINT COMPROBANTE ═══════ -->
    <Dialog v-model:open="printDialogOpen">
        <DialogContent
            class="max-h-[94vh] w-[calc(100vw-2rem)] max-w-2xl overflow-y-auto"
        >
            <DialogHeader>
                <DialogTitle>Comprobante de Venta</DialogTitle>
                <DialogDescription v-if="ventaToPrint">
                    Comprobante de la venta
                    <strong>{{ ventaToPrint.numero_venta }}</strong
                    >.
                </DialogDescription>
            </DialogHeader>
            <div v-if="ventaToPrint" class="grid gap-4 py-4">
                <div
                    ref="saleReceiptRef"
                    class="sale-receipt overflow-hidden rounded-2xl border border-fuchsia-100 bg-white shadow-lg shadow-fuchsia-100/60"
                >
                    <div
                        class="receipt-header relative overflow-hidden bg-gradient-to-r from-fuchsia-700 via-pink-600 to-rose-500 px-8 py-7 text-white"
                    >
                        <div
                            class="absolute -top-14 -right-8 size-40 rounded-full bg-white/10"
                        ></div>
                        <div
                            class="absolute -bottom-16 -left-8 size-44 rounded-full bg-white/10"
                        ></div>
                        <div
                            class="relative flex items-center justify-between gap-6"
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
                                <p class="mt-1 text-lg font-bold">DE VENTA</p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="grid gap-3 bg-gradient-to-br from-fuchsia-50/80 to-white px-8 py-6 text-sm sm:grid-cols-2"
                    >
                        <div
                            class="rounded-xl border border-fuchsia-100 bg-white/90 px-4 py-3"
                        >
                            <p
                                class="text-[10px] font-bold tracking-wider text-fuchsia-700 uppercase"
                            >
                                Número de comprobante
                            </p>
                            <p
                                class="mt-1 font-mono text-sm font-bold text-slate-800"
                            >
                                {{ ventaToPrint.numero_venta }}
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-fuchsia-100 bg-white/90 px-4 py-3"
                        >
                            <p
                                class="text-[10px] font-bold tracking-wider text-fuchsia-700 uppercase"
                            >
                                Fecha de venta
                            </p>
                            <p class="mt-1 font-semibold text-slate-800">
                                {{ formatDate(ventaToPrint.fecha) }}
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-fuchsia-100 bg-white/90 px-4 py-3"
                        >
                            <p
                                class="text-[10px] font-bold tracking-wider text-fuchsia-700 uppercase"
                            >
                                Cliente
                            </p>
                            <p class="mt-1 font-semibold text-slate-800">
                                {{ getClientFullName(ventaToPrint.cliente) }}
                            </p>
                        </div>
                        <div
                            class="rounded-xl border border-fuchsia-100 bg-white/90 px-4 py-3"
                        >
                            <p
                                class="text-[10px] font-bold tracking-wider text-fuchsia-700 uppercase"
                            >
                                Atendido por
                            </p>
                            <p class="mt-1 font-semibold text-slate-800">
                                {{ getFullName(ventaToPrint.user) }}
                            </p>
                        </div>
                    </div>

                    <div v-if="ventaToPrint.detalles?.length" class="px-8 py-5">
                        <table class="receipt-table w-full text-sm">
                            <thead>
                                <tr
                                    class="bg-gradient-to-r from-fuchsia-50 to-pink-50 text-fuchsia-800"
                                >
                                    <th
                                        class="rounded-l-lg px-4 py-3 text-left font-bold"
                                    >
                                        Producto
                                    </th>
                                    <th class="px-3 py-3 text-center font-bold">
                                        Cantidad
                                    </th>
                                    <th class="px-3 py-3 text-right font-bold">
                                        P. unitario
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
                                    v-for="detalle in ventaToPrint.detalles"
                                    :key="detalle.id"
                                    class="border-b border-slate-100 last:border-0"
                                >
                                    <td
                                        class="px-4 py-4 font-semibold text-slate-800"
                                    >
                                        {{ detalle.producto }}
                                    </td>
                                    <td class="px-3 py-4 text-center">
                                        {{ detalle.cantidad }}
                                    </td>
                                    <td class="px-3 py-4 text-right">
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
                        <div>
                            <p
                                class="text-[10px] font-semibold tracking-widest text-pink-300 uppercase"
                            >
                                Importe total
                            </p>
                            <span class="text-sm font-medium text-white/80"
                                >Total de la venta</span
                            >
                        </div>
                        <span class="text-3xl font-extrabold text-pink-300">
                            {{ formatCurrency(ventaToPrint.total) }}
                        </span>
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

    #sale-print-root {
        display: block !important;
        width: 100%;
    }

    #sale-print-root .sale-receipt {
        width: 100%;
        min-height: 120mm;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    #sale-print-root .receipt-header,
    #sale-print-root .receipt-table thead,
    #sale-print-root .sale-receipt > div {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    #sale-print-root .receipt-table thead {
        display: table-header-group;
    }

    #sale-print-root .receipt-table tr {
        break-inside: avoid;
    }
}
</style>

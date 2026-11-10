<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, Eye, Package, Search, Truck, User, X } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
import {
    cancelar,
    confirmar,
    entregar,
    index,
    preparar,
    estado,
} from '@/routes/gestion/pedidos';
import type { PaginatedData, Pedido, PedidoEstado } from '@/types';

const { can } = usePermissions();

type Props = {
    pedidos: PaginatedData<Pedido>;
    filters: { search?: string };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Pedidos', href: index() }],
    },
});

const ESTADOS: PedidoEstado[] = [
    'Pendiente',
    'Confirmado',
    'En preparación',
    'Enviado',
    'Entregado',
    'Cancelado',
];

const search = ref(props.filters.search ?? '');

const POLL_INTERVAL_MS = 30000;
let pollTimer: number | null = null;

onMounted(() => {
    pollTimer = window.setInterval(() => {
        router.reload({ only: ['pedidos'], preserveScroll: true });
    }, POLL_INTERVAL_MS);
});

onBeforeUnmount(() => {
    if (pollTimer !== null) {
        window.clearInterval(pollTimer);
    }
});

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

const cancelDialogOpen = ref(false);
const pedidoToCancel = ref<Pedido | null>(null);

function openCancelDialog(pedido: Pedido) {
    pedidoToCancel.value = pedido;
    cancelDialogOpen.value = true;
}

function submitCancelar() {
    if (!pedidoToCancel.value) {
        return;
    }

    const id = pedidoToCancel.value.id;
    cancelDialogOpen.value = false;
    router.post(cancelar(id).url, {}, { preserveScroll: true });
}

function confirmarPedido(pedido: Pedido) {
    router.post(confirmar(pedido.id).url, {}, { preserveScroll: true });
}

function prepararPedido(pedido: Pedido) {
    router.post(preparar(pedido.id).url, {}, { preserveScroll: true });
}

function entregarPedido(pedido: Pedido) {
    router.post(entregar(pedido.id).url, {}, { preserveScroll: true });
}

function cambiarEstado(pedido: Pedido, value: string) {
    if (value === pedido.estado) {
        return;
    }

    router.patch(
        estado(pedido.id).url,
        { estado: value },
        { preserveScroll: true },
    );
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
        minimumFractionDigits: 2,
    }).format(value);
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString('es-BO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
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

function estadoBadgeClass(estado: string): string {
    switch (estado) {
        case 'Pendiente':
            return 'border-amber-200 text-amber-600';
        case 'Confirmado':
            return 'border-sky-200 text-sky-600';
        case 'En preparación':
            return 'border-orange-200 text-orange-600';
        case 'Enviado':
            return 'border-sky-200 text-sky-600';
        case 'Entregado':
            return 'border-purple-200 text-purple-600';
        case 'Cancelado':
            return 'border-red-200 text-red-600';
        default:
            return 'border-slate-200 text-slate-600';
    }
}

function totalCantidad(pedido: Pedido): number {
    return pedido.productos.reduce((sum, p) => sum + p.cantidad, 0);
}

function puedeAccionar(): boolean {
    return can('pedidos.gestion');
}
</script>

<template>
    <Head title="Pedidos" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <Heading
                title="Gestión de Pedidos"
                description="Consulta los pedidos realizados por los clientes de la tienda."
            />
        </div>

        <div class="flex items-center gap-2">
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Buscar por número, cliente, producto o estado..."
                    class="pl-10"
                />
            </div>
            <Button variant="outline" @click="clearSearch"> Limpiar </Button>
        </div>

        <div class="overflow-hidden rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50 text-left">
                        <th class="px-4 py-3 font-medium">N° Pedido</th>
                        <th class="px-4 py-3 font-medium">Cliente</th>
                        <th class="px-4 py-3 font-medium">Productos</th>
                        <th class="px-4 py-3 font-medium">Cantidad</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Fecha</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="pedido in pedidos.data"
                        :key="pedido.id"
                        class="border-b transition-colors last:border-0 hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs">{{
                                pedido.numero
                            }}</span>
                            <a
                                v-if="pedido.comprobante_pago"
                                :href="resolveImageUrl(pedido.comprobante_pago)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 block text-[11px] font-semibold text-emerald-700 underline"
                            >
                                Ver comprobante
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10"
                                >
                                    <User class="h-4 w-4 text-primary" />
                                </div>
                                <div>
                                    <span class="font-medium">{{
                                        pedido.cliente?.nombre ?? '---'
                                    }}</span>
                                    <p
                                        v-if="pedido.cliente?.email"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ pedido.cliente.email }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <Badge variant="secondary"
                                >{{
                                    pedido.productos.length
                                }}
                                producto(s)</Badge
                            >
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs"
                                >{{ totalCantidad(pedido) }} u.</span
                            >
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-medium">{{
                                formatCurrency(pedido.total)
                            }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs">{{
                                formatDate(pedido.fecha)
                            }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <Badge
                                :class="[
                                    'text-xs',
                                    estadoBadgeClass(pedido.estado),
                                ]"
                                variant="outline"
                            >
                                {{ pedido.estado }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">
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
                                                        `/gestion/pedidos/${pedido.id}`,
                                                    )
                                                "
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent
                                            ><p>Ver detalle</p></TooltipContent
                                        >
                                    </Tooltip>
                                </TooltipProvider>

                                <div
                                    v-if="puedeAccionar()"
                                    class="flex items-center gap-1"
                                >
                                    <TooltipProvider
                                        v-if="pedido.estado === 'Pendiente'"
                                        :delay-duration="0"
                                    >
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    size="icon-sm"
                                                    class="border-emerald-200 text-emerald-600 hover:bg-emerald-50"
                                                    @click="
                                                        confirmarPedido(pedido)
                                                    "
                                                >
                                                    <Check class="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent
                                                ><p>
                                                    Confirmar pedido
                                                </p></TooltipContent
                                            >
                                        </Tooltip>
                                    </TooltipProvider>

                                    <TooltipProvider
                                        v-if="
                                            pedido.estado === 'Pendiente' ||
                                            pedido.estado === 'Confirmado'
                                        "
                                        :delay-duration="0"
                                    >
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    size="icon-sm"
                                                    class="border-amber-200 text-amber-600 hover:bg-amber-50"
                                                    @click="
                                                        prepararPedido(pedido)
                                                    "
                                                >
                                                    <Package class="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent
                                                ><p>
                                                    Preparar pedido
                                                </p></TooltipContent
                                            >
                                        </Tooltip>
                                    </TooltipProvider>

                                    <TooltipProvider
                                        v-if="
                                            pedido.estado !== 'Entregado' &&
                                            pedido.estado !== 'Cancelado'
                                        "
                                        :delay-duration="0"
                                    >
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    size="icon-sm"
                                                    class="border-purple-200 text-purple-600 hover:bg-purple-50"
                                                    @click="
                                                        entregarPedido(pedido)
                                                    "
                                                >
                                                    <Truck class="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent
                                                ><p>
                                                    Marcar entregado
                                                </p></TooltipContent
                                            >
                                        </Tooltip>
                                    </TooltipProvider>

                                    <TooltipProvider
                                        v-if="
                                            pedido.estado !== 'Entregado' &&
                                            pedido.estado !== 'Cancelado'
                                        "
                                        :delay-duration="0"
                                    >
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    size="icon-sm"
                                                    class="border-red-200 text-red-500 hover:bg-red-50"
                                                    @click="
                                                        openCancelDialog(pedido)
                                                    "
                                                >
                                                    <X class="h-4 w-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent
                                                ><p>
                                                    Cancelar pedido
                                                </p></TooltipContent
                                            >
                                        </Tooltip>
                                    </TooltipProvider>

                                    <Select
                                        @update:model-value="
                                            (v: string) =>
                                                cambiarEstado(pedido, v)
                                        "
                                    >
                                        <SelectTrigger class="h-8 w-32 text-xs">
                                            <SelectValue
                                                :placeholder="pedido.estado"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="estadoOption in ESTADOS"
                                                :key="estadoOption"
                                                :value="estadoOption"
                                            >
                                                {{ estadoOption }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="pedidos.data.length === 0">
                        <td
                            colspan="8"
                            class="py-12 text-center text-sm text-muted-foreground"
                        >
                            <Package class="mx-auto mb-2 h-8 w-8 opacity-40" />
                            <p class="font-medium">
                                No hay pedidos registrados.
                            </p>
                            <p>
                                Los pedidos que realicen los clientes aparecerán
                                aquí.
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="pedidos.last_page > 1"
            class="flex items-center justify-between"
        >
            <p class="text-sm text-muted-foreground">
                Mostrando {{ pedidos.from }}–{{ pedidos.to }} de
                {{ pedidos.total }} pedidos
            </p>
            <div class="flex items-center gap-2">
                <Link
                    :href="pedidos.prev_page_url ?? '#'"
                    :class="[
                        'rounded-lg border px-3 py-1.5 text-sm transition-colors',
                        pedidos.prev_page_url
                            ? 'hover:bg-muted'
                            : 'pointer-events-none opacity-40',
                    ]"
                >
                    Anterior
                </Link>
                <Link
                    :href="pedidos.next_page_url ?? '#'"
                    :class="[
                        'rounded-lg border px-3 py-1.5 text-sm transition-colors',
                        pedidos.next_page_url
                            ? 'hover:bg-muted'
                            : 'pointer-events-none opacity-40',
                    ]"
                >
                    Siguiente
                </Link>
            </div>
        </div>
    </div>

    <!-- ═══════ CONFIRM CANCELAR ═══════ -->
    <Dialog v-model:open="cancelDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Cancelar Pedido</DialogTitle>
                <DialogDescription>
                    ¿Está seguro de cancelar el pedido
                    <strong>{{ pedidoToCancel?.numero }}</strong
                    >? El cliente podrá ver el estado actualizado.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cancelar</Button>
                </DialogClose>
                <Button variant="destructive" @click="submitCancelar"
                    >Cancelar Pedido</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

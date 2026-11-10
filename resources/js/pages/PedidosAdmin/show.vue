<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Check, Package, Truck, User, X } from 'lucide-vue-next';
import { ref } from 'vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import {
    cancelar,
    confirmar,
    entregar,
    estado,
    index,
    preparar,
} from '@/routes/gestion/pedidos';
import type { Pedido, PedidoEstado } from '@/types';

const { can } = usePermissions();

const props = defineProps<{
    pedido: Pedido;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Pedidos', href: index() },
            { title: 'Detalle del Pedido', href: '#' },
        ],
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

const cancelDialogOpen = ref(false);

function submitCancelar() {
    cancelDialogOpen.value = false;
    router.post(cancelar(props.pedido.id).url, {}, { preserveScroll: true });
}

function confirmarPedido() {
    router.post(confirmar(props.pedido.id).url, {}, { preserveScroll: true });
}

function prepararPedido() {
    router.post(preparar(props.pedido.id).url, {}, { preserveScroll: true });
}

function entregarPedido() {
    router.post(entregar(props.pedido.id).url, {}, { preserveScroll: true });
}

function cambiarEstado(value: string) {
    if (value === props.pedido.estado) {
        return;
    }

    router.patch(
        estado(props.pedido.id).url,
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
        month: 'long',
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

function esPdf(ruta: string | null): boolean {
    return Boolean(ruta && ruta.toLowerCase().endsWith('.pdf'));
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

function puedeAccionar(): boolean {
    return can('pedidos.gestion');
}

const pedido = props.pedido;
</script>

<template>
    <Head :title="`Pedido ${pedido.numero}`" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <Heading
                title="Detalle del Pedido"
                :description="`Pedido ${pedido.numero}`"
            />
            <Link :href="index()">
                <Button variant="outline">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver a Pedidos
                </Button>
            </Link>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Cliente + resumen -->
            <div class="space-y-6 lg:col-span-1">
                <div class="overflow-hidden rounded-xl border">
                    <div class="border-b bg-muted/50 px-4 py-3">
                        <h3
                            class="flex items-center gap-2 text-sm font-semibold"
                        >
                            <User class="h-4 w-4 text-primary" />
                            Cliente
                        </h3>
                    </div>
                    <div class="space-y-3 p-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-muted-foreground">Nombre</span>
                            <span class="text-right font-medium">{{
                                pedido.cliente?.nombre ?? '---'
                            }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-muted-foreground">Email</span>
                            <span class="text-right font-medium">{{
                                pedido.cliente?.email ?? '---'
                            }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-muted-foreground">Teléfono</span>
                            <span class="text-right font-medium">{{
                                pedido.cliente?.telefono ?? '---'
                            }}</span>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border">
                    <div class="border-b bg-muted/50 px-4 py-3">
                        <h3 class="text-sm font-semibold">Resumen</h3>
                    </div>
                    <div class="space-y-3 p-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-muted-foreground">Número</span>
                            <span class="font-mono text-xs font-medium">{{
                                pedido.numero
                            }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-muted-foreground">Fecha</span>
                            <span class="text-right font-medium">{{
                                formatDate(pedido.fecha)
                            }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-muted-foreground"
                                >Método de pago</span
                            >
                            <span class="text-right font-medium">{{
                                pedido.metodo_pago ?? '---'
                            }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-muted-foreground">Estado</span>
                            <Badge
                                :class="[
                                    'text-xs',
                                    estadoBadgeClass(pedido.estado),
                                ]"
                                variant="outline"
                            >
                                {{ pedido.estado }}
                            </Badge>
                        </div>
                        <div class="flex justify-between gap-4 border-t pt-3">
                            <span class="font-semibold">Total</span>
                            <span class="text-lg font-bold">{{
                                formatCurrency(pedido.total)
                            }}</span>
                        </div>
                        <div
                            v-if="pedido.comprobante_pago"
                            class="border-t pt-3"
                        >
                            <p class="mb-2 text-muted-foreground">
                                Comprobante de pago
                            </p>
                            <img
                                v-if="!esPdf(pedido.comprobante_pago)"
                                :src="resolveImageUrl(pedido.comprobante_pago)"
                                alt="Comprobante de pago"
                                class="max-h-72 rounded-lg border object-contain"
                            />
                            <a
                                :href="resolveImageUrl(pedido.comprobante_pago)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-emerald-700 underline"
                            >
                                Abrir comprobante
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos + acciones -->
            <div class="space-y-6 lg:col-span-2">
                <div class="overflow-hidden rounded-xl border">
                    <div class="border-b bg-muted/50 px-4 py-3">
                        <h3
                            class="flex items-center gap-2 text-sm font-semibold"
                        >
                            <Package class="h-4 w-4 text-primary" />
                            Productos ({{ pedido.productos.length }})
                        </h3>
                    </div>
                    <div class="divide-y">
                        <div
                            v-for="(producto, idx) in pedido.productos"
                            :key="`${producto.producto_id}-${idx}`"
                            class="flex items-center gap-4 p-4"
                        >
                            <div
                                v-if="producto.imagen"
                                class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-muted"
                            >
                                <img
                                    :src="producto.imagen"
                                    :alt="producto.nombre"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div
                                v-else
                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-muted"
                            >
                                <Package
                                    class="h-6 w-6 text-muted-foreground"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ producto.nombre }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ producto.cantidad }} u.
                                    <span v-if="producto.color">
                                        · {{ producto.color }}</span
                                    >
                                    <span v-if="producto.talla">
                                        · Talla {{ producto.talla }}</span
                                    >
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    P.U.: {{ formatCurrency(producto.precio) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium">
                                    {{
                                        formatCurrency(
                                            producto.subtotal ??
                                                producto.precio *
                                                    producto.cantidad,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="puedeAccionar()"
                    class="overflow-hidden rounded-xl border"
                >
                    <div class="border-b bg-muted/50 px-4 py-3">
                        <h3 class="text-sm font-semibold">Acciones</h3>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 p-4">
                        <Button
                            v-if="pedido.estado === 'Pendiente'"
                            class="border-emerald-200 text-emerald-600 hover:bg-emerald-50"
                            variant="outline"
                            @click="confirmarPedido"
                        >
                            <Check class="mr-2 h-4 w-4" />
                            Confirmar
                        </Button>
                        <Button
                            v-if="
                                pedido.estado === 'Pendiente' ||
                                pedido.estado === 'Confirmado'
                            "
                            class="border-amber-200 text-amber-600 hover:bg-amber-50"
                            variant="outline"
                            @click="prepararPedido"
                        >
                            <Package class="mr-2 h-4 w-4" />
                            Preparar
                        </Button>
                        <Button
                            v-if="
                                pedido.estado !== 'Entregado' &&
                                pedido.estado !== 'Cancelado'
                            "
                            class="border-purple-200 text-purple-600 hover:bg-purple-50"
                            variant="outline"
                            @click="entregarPedido"
                        >
                            <Truck class="mr-2 h-4 w-4" />
                            Marcar entregado
                        </Button>
                        <Button
                            v-if="
                                pedido.estado !== 'Entregado' &&
                                pedido.estado !== 'Cancelado'
                            "
                            variant="outline"
                            class="border-red-200 text-red-500 hover:bg-red-50"
                            @click="cancelDialogOpen = true"
                        >
                            <X class="mr-2 h-4 w-4" />
                            Cancelar
                        </Button>
                        <Select
                            :model-value="pedido.estado"
                            @update:model-value="cambiarEstado"
                        >
                            <SelectTrigger class="w-44">
                                <SelectValue placeholder="Cambiar estado" />
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
                    <strong>{{ pedido.numero }}</strong
                    >?
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">No</Button>
                </DialogClose>
                <Button variant="destructive" @click="submitCancelar"
                    >Sí, cancelar pedido</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

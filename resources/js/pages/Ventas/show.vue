<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Hash, Package, User } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/ventas';
import type { Venta } from '@/types';

type Props = {
    venta: Venta;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Ventas', href: index() },
            { title: 'Detalle de Venta', href: '#' },
        ],
    },
});

function formatCurrency(value: string | number): string {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
        minimumFractionDigits: 2,
    }).format(num);
}

function formatDate(date: string | Date | null): string {
    if (!date) return '---';
    const d = new Date(date);
    const day = String(d.getUTCDate()).padStart(2, '0');
    const month = String(d.getUTCMonth() + 1).padStart(2, '0');
    const year = d.getUTCFullYear();
    return `${day}-${month}-${year}`;
}

function formatDateTime(date: string | Date | null): string {
    if (!date) return '---';
    const d = new Date(date);
    const day = String(d.getUTCDate()).padStart(2, '0');
    const month = String(d.getUTCMonth() + 1).padStart(2, '0');
    const year = d.getUTCFullYear();
    const hours = String(d.getUTCHours()).padStart(2, '0');
    const minutes = String(d.getUTCMinutes()).padStart(2, '0');
    return `${day}-${month}-${year} ${hours}:${minutes}`;
}

function getFullName(user: Venta['user']): string {
    if (!user) return '---';
    if (user.name) return user.name;
    const parts = [
        user.nombres,
        user.apellido_paterno,
        user.apellido_materno,
    ].filter(Boolean);
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
</script>

<template>
    <Head title="Detalle de Venta" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-start justify-between">
            <Heading
                title="Detalle de Venta"
                description="Información completa de la venta."
            />
            <Link :href="index()">
                <Button variant="outline">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </Link>
        </div>

        <div class="grid gap-6">
            <div class="bg-glass overflow-hidden rounded-xl border">
                <div class="border-b px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10"
                        >
                            <Package class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold">
                                {{ venta.numero_venta }}
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Venta #{{ venta.id }}
                            </p>
                        </div>
                        <Badge
                            :class="[
                                'ml-auto text-xs',
                                getEstadoBadgeClass(venta.estado),
                            ]"
                            variant="outline"
                        >
                            {{
                                venta.estado === 'anulada'
                                    ? 'Anulada'
                                    : 'Completada'
                            }}
                        </Badge>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-3">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-muted"
                        >
                            <Hash class="h-4 w-4 text-muted-foreground" />
                        </div>
                        <div>
                            <Label class="text-xs text-muted-foreground"
                                >N° de Venta</Label
                            >
                            <p class="font-mono text-sm font-medium">
                                {{ venta.numero_venta }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-muted"
                        >
                            <User class="h-4 w-4 text-muted-foreground" />
                        </div>
                        <div>
                            <Label class="text-xs text-muted-foreground"
                                >Cliente</Label
                            >
                            <p class="text-sm font-medium">
                                {{ getClientFullName(venta.cliente) }}
                            </p>
                            <p
                                v-if="venta.cliente?.ci_nit"
                                class="font-mono text-xs text-muted-foreground"
                            >
                                CI/NIT: {{ venta.cliente.ci_nit }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-muted"
                        >
                            <User class="h-4 w-4 text-muted-foreground" />
                        </div>
                        <div>
                            <Label class="text-xs text-muted-foreground"
                                >Atendido por</Label
                            >
                            <p class="text-sm font-medium">
                                {{ getFullName(venta.user) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-muted"
                        >
                            <Calendar class="h-4 w-4 text-muted-foreground" />
                        </div>
                        <div>
                            <Label class="text-xs text-muted-foreground"
                                >Fecha de Venta</Label
                            >
                            <p class="text-sm font-medium">
                                {{ formatDate(venta.fecha) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-muted"
                        >
                            <Package class="h-4 w-4 text-muted-foreground" />
                        </div>
                        <div>
                            <Label class="text-xs text-muted-foreground"
                                >Total</Label
                            >
                            <p class="text-lg font-bold">
                                {{ formatCurrency(venta.total) }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="venta.estado === 'anulada'"
                        class="flex items-start gap-3"
                    >
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-50"
                        >
                            <Calendar class="h-4 w-4 text-red-500" />
                        </div>
                        <div>
                            <Label class="text-xs text-muted-foreground"
                                >Anulada el</Label
                            >
                            <p class="text-sm font-medium">
                                {{ formatDateTime(venta.anulada_en) }}
                            </p>
                            <p
                                v-if="venta.anulada_por"
                                class="text-xs text-muted-foreground"
                            >
                                por {{ getFullName(venta.anulada_por) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-glass overflow-hidden rounded-xl border">
                <div class="border-b px-6 py-4">
                    <h3 class="text-base font-semibold">Productos</h3>
                </div>

                <div v-if="venta.detalles && venta.detalles.length > 0">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50 text-left">
                                <th class="px-6 py-3 font-medium">Producto</th>
                                <th class="px-6 py-3 text-center font-medium">
                                    Cantidad
                                </th>
                                <th class="px-6 py-3 text-right font-medium">
                                    P. Unitario
                                </th>
                                <th class="px-6 py-3 text-right font-medium">
                                    Descuento
                                </th>
                                <th class="px-6 py-3 text-right font-medium">
                                    Subtotal
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="detalle in venta.detalles"
                                :key="detalle.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/30"
                            >
                                <td class="px-6 py-3 font-medium">
                                    {{ detalle.producto }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    {{ detalle.cantidad }}
                                </td>
                                <td class="px-6 py-3 text-right">
                                    {{
                                        formatCurrency(detalle.precio_unitario)
                                    }}
                                </td>
                                <td class="px-6 py-3 text-right">
                                    {{ formatCurrency(detalle.descuento) }}
                                </td>
                                <td class="px-6 py-3 text-right font-medium">
                                    {{ formatCurrency(detalle.subtotal) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t bg-muted/30">
                                <td
                                    colspan="4"
                                    class="px-6 py-3 text-right font-bold"
                                >
                                    Total:
                                </td>
                                <td
                                    class="px-6 py-3 text-right text-lg font-bold"
                                >
                                    {{ formatCurrency(venta.total) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div v-else class="px-6 py-12 text-center">
                    <Package
                        class="mx-auto mb-3 h-10 w-10 text-muted-foreground opacity-40"
                    />
                    <p class="text-sm text-muted-foreground">
                        No hay productos en esta venta.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

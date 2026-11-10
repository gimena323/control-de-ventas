<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    Hash,
    Phone,
    ShoppingCart,
    Truck,
    User,
} from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/proveedores';
import type { Compra, Proveedor } from '@/types';

type Props = {
    proveedor: Proveedor & {
        compras?: Compra[];
        compras_count: number;
    };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Proveedores',
                href: index(),
            },
            {
                title: 'Detalles del Proveedor',
                href: '#',
            },
        ],
    },
});

function formatDate(date: string | Date | null): string {
    if (!date) return '—';
    const d = new Date(date);
    const day = String(d.getUTCDate()).padStart(2, '0');
    const month = String(d.getUTCMonth() + 1).padStart(2, '0');
    const year = d.getUTCFullYear();
    return `${day}-${month}-${year}`;
}

function formatCurrency(value: string | number): string {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
        minimumFractionDigits: 2,
    }).format(num);
}
</script>

<template>
    <Head title="Detalles del Proveedor" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-start justify-between">
            <Heading
                title="Detalles del Proveedor"
                description="Información completa del proveedor."
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
                            <Truck class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold">
                                {{ proveedor.nombre }}
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Proveedor #{{ proveedor.id }}
                            </p>
                        </div>
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
                                >ID del Proveedor</Label
                            >
                            <p class="text-sm font-medium">
                                #{{ proveedor.id }}
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
                                >Nombre</Label
                            >
                            <p class="text-sm font-medium">
                                {{ proveedor.nombre }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-muted"
                        >
                            <Hash class="h-4 w-4 text-muted-foreground" />
                        </div>
                        <div>
                            <Label class="text-xs text-muted-foreground"
                                >CI/NIT</Label
                            >
                            <p class="font-mono text-sm font-medium">
                                {{ proveedor.ci_nit }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-muted"
                        >
                            <Phone class="h-4 w-4 text-muted-foreground" />
                        </div>
                        <div>
                            <Label class="text-xs text-muted-foreground"
                                >Teléfono</Label
                            >
                            <p class="text-sm font-medium">
                                {{ proveedor.telefono ?? '—' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-muted"
                        >
                            <ShoppingCart
                                class="h-4 w-4 text-muted-foreground"
                            />
                        </div>
                        <div>
                            <Label class="text-xs text-muted-foreground"
                                >Compras Realizadas</Label
                            >
                            <Badge variant="secondary" class="mt-1">{{
                                proveedor.compras_count
                            }}</Badge>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-glass overflow-hidden rounded-xl border">
                <div class="border-b px-6 py-4">
                    <h3 class="text-base font-semibold">Compras Recientes</h3>
                </div>

                <div v-if="proveedor.compras && proveedor.compras.length > 0">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50 text-left">
                                <th class="px-6 py-3 font-medium">Fecha</th>
                                <th class="px-6 py-3 font-medium">N° Compra</th>
                                <th class="px-6 py-3 font-medium">Total</th>
                                <th class="px-6 py-3 font-medium">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="compra in proveedor.compras"
                                :key="compra.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/30"
                            >
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-2">
                                        <Calendar
                                            class="h-4 w-4 text-muted-foreground"
                                        />
                                        <span class="text-xs">{{
                                            formatDate(compra.fecha)
                                        }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="font-mono text-xs">{{
                                        compra.numero_compra
                                    }}</span>
                                </td>
                                <td class="px-6 py-3 font-medium">
                                    {{ formatCurrency(compra.total) }}
                                </td>
                                <td class="px-6 py-3">
                                    <Badge variant="default" class="text-xs"
                                        >Completada</Badge
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="px-6 py-12 text-center">
                    <ShoppingCart
                        class="mx-auto mb-3 h-10 w-10 text-muted-foreground opacity-40"
                    />
                    <p class="text-sm text-muted-foreground">
                        No hay compras registradas para este proveedor.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

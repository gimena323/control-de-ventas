<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, History, Search, User, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/ventas';
import type { PaginatedData, Venta } from '@/types';

type Props = {
    ventas: PaginatedData<Venta>;
    filters: { search?: string };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Ventas', href: index() },
            { title: 'Ventas Anuladas', href: '#' },
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

function getClientFullName(cliente: Venta['cliente']): string {
    if (!cliente) return '---';
    return [cliente.nombre, cliente.apellido].filter(Boolean).join(' ');
}

function getFullName(user: Venta['anulada_por']): string {
    if (!user) return '---';
    if (user.name) return user.name;
    const parts = [user.nombres, user.apellido_paterno, user.apellido_materno].filter(Boolean);
    return parts.join(' ') || '---';
}
</script>

<template>
    <Head title="Ventas Anuladas" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <Heading title="Ventas Anuladas" description="Historial de ventas anuladas del sistema." />
            <Link :href="index()">
                <Button variant="outline">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver a Ventas
                </Button>
            </Link>
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
                        <th class="px-4 py-3 font-medium">ID</th>
                        <th class="px-4 py-3 font-medium">Fecha</th>
                        <th class="px-4 py-3 font-medium">Cliente</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Fecha Anulación</th>
                        <th class="px-4 py-3 font-medium">Anulada por</th>
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
                            <div class="flex items-center gap-2">
                                <Calendar class="text-muted-foreground h-4 w-4" />
                                <span class="text-xs">{{ formatDate(venta.fecha) }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
                                    <User class="text-primary h-4 w-4" />
                                </div>
                                <span class="font-medium">{{ getClientFullName(venta.cliente) }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-medium">{{ formatCurrency(venta.total) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs">{{ formatDateTime(venta.anulada_en) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs">{{ getFullName(venta.anulada_por) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <Badge class="border-red-200 text-xs text-red-600" variant="outline">
                                <X class="mr-1 h-3 w-3" />
                                Anulada
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="`/ventas/${venta.id}`">
                                <Button variant="outline" size="sm">Ver</Button>
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="ventas.data.length === 0">
                        <td colspan="8" class="py-12 text-center text-sm text-muted-foreground">
                            <History class="mx-auto mb-2 h-8 w-8 opacity-40" />
                            <p class="font-medium">No se encontraron ventas anuladas.</p>
                            <p>Intenta ajustar los filtros de búsqueda.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="ventas.last_page > 1" class="flex items-center justify-between">
            <p class="text-muted-foreground text-sm">
                Mostrando {{ ventas.from }}–{{ ventas.to }} de {{ ventas.total }} ventas anuladas
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
</template>

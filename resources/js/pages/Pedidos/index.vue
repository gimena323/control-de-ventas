<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Package, PackageOpen, ShoppingBag, Store } from 'lucide-vue-next';
import type { Pedido } from '@/types';

defineProps<{
    pedidos: Pedido[];
}>();

function formatCurrency(value: number): string {
    return `${Math.round(value)} bs`;
}

function formatFecha(iso: string): string {
    return new Date(iso).toLocaleDateString('es-BO', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function estadoBadgeClass(estado: string): string {
    switch (estado) {
        case 'Pendiente':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'Confirmado':
            return 'bg-sky-100 text-sky-700 border-sky-200';
        case 'En preparación':
            return 'bg-orange-100 text-orange-700 border-orange-200';
        case 'Pagado':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'Enviado':
            return 'bg-sky-100 text-sky-700 border-sky-200';
        case 'Entregado':
            return 'bg-purple-100 text-purple-700 border-purple-200';
        case 'Cancelado':
            return 'bg-rose-100 text-rose-700 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-600 border-slate-200';
    }
}
</script>

<template>
    <Head title="Mis Compras" />

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 sm:py-12">
        <div class="mb-8">
            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-pink-200/70 bg-white/70 px-4 py-1.5 text-xs font-medium text-pink-600 shadow-sm"
            >
                <PackageOpen class="h-3 w-3" />
                Área del cliente
            </span>
            <h1 class="mt-4 text-2xl font-bold text-slate-900 sm:text-3xl">
                Mis Compras
            </h1>
            <p class="mt-2 text-sm text-slate-500">
                Consulta el estado y los detalles de tus compras realizadas.
            </p>
        </div>

        <div
            v-if="pedidos.length === 0"
            class="rounded-2xl border border-purple-100/60 bg-white/80 py-20 text-center backdrop-blur"
        >
            <div
                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-purple-100 to-pink-100"
            >
                <ShoppingBag class="h-8 w-8 text-purple-400" />
            </div>
            <p class="font-semibold text-slate-600">Aún no tienes compras</p>
            <p class="mt-1 text-sm text-slate-400">
                Agrega productos a tu carrito y realiza tu primera compra.
            </p>
            <Link
                href="/catalogo"
                class="mt-6 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all hover:scale-[1.02] hover:shadow-xl"
            >
                <Store class="h-4 w-4" />
                Explorar catálogo
            </Link>
        </div>

        <div v-else class="space-y-5">
            <article
                v-for="pedido in pedidos"
                :key="pedido.numero"
                class="overflow-hidden rounded-2xl border border-purple-100/60 bg-white/80 backdrop-blur"
            >
                <div
                    class="flex flex-col gap-3 border-b border-purple-100/50 bg-gradient-to-r from-purple-50/60 to-pink-50/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p class="text-xs text-slate-400">Número de compra</p>
                        <p class="font-bold text-slate-800">
                            {{ pedido.numero }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ formatFecha(pedido.fecha) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold"
                            :class="estadoBadgeClass(pedido.estado)"
                        >
                            {{ pedido.estado }}
                        </span>
                        <p class="text-lg font-extrabold text-slate-900">
                            {{ formatCurrency(pedido.total) }}
                        </p>
                    </div>
                </div>

                <div class="px-5 py-4">
                    <ul class="space-y-3">
                        <li
                            v-for="producto in pedido.productos"
                            :key="
                                producto.producto_id +
                                '-' +
                                producto.color +
                                '-' +
                                producto.talla
                            "
                            class="flex items-center gap-3"
                        >
                            <div
                                class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-gradient-to-br from-purple-50 to-pink-50"
                            >
                                <img
                                    v-if="producto.imagen"
                                    :src="producto.imagen"
                                    :alt="producto.nombre"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full items-center justify-center"
                                >
                                    <Package
                                        class="h-5 w-5 text-purple-300/50"
                                    />
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ producto.nombre }}
                                </p>
                                <p class="text-xs text-slate-400">
                                    Cantidad: {{ producto.cantidad }}
                                    <span v-if="producto.color">
                                        · Color: {{ producto.color }}</span
                                    >
                                    <span v-if="producto.talla">
                                        · Talla: {{ producto.talla }}</span
                                    >
                                </p>
                            </div>
                            <p class="text-sm font-bold text-purple-700">
                                {{
                                    formatCurrency(
                                        producto.precio * producto.cantidad,
                                    )
                                }}
                            </p>
                        </li>
                    </ul>
                    <p
                        class="mt-4 border-t border-purple-100/50 pt-3 text-xs text-slate-400"
                    >
                        Método de pago: {{ pedido.metodo_pago }}
                    </p>
                </div>
            </article>
        </div>
    </div>
</template>

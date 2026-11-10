<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ShoppingBag,
    Package,
    Trash2,
    Minus,
    Plus,
    ChevronLeft,
    ShoppingCart,
    Upload,
    QrCode,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useCart } from '@/composables/useCart';
import type { CartItem } from '@/composables/useCart';
import { usePermissions } from '@/composables/usePermissions';
import { redirect as googleRedirect } from '@/routes/auth/google';
import { store as pedidoStore } from '@/routes/pedidos';

const { items, totalItems, subtotal, removeItem, updateCantidad, clearCart } =
    useCart();

const page = usePage();
const { hasRole } = usePermissions();
const user = computed(() => page.props.auth?.user ?? null);
const esCliente = computed(() => Boolean(user.value) && hasRole('cliente'));
const isProcessing = ref(false);
const localError = ref<string | null>(null);
const comprobante = ref<File | null>(null);
const comprobantePreview = ref<string | null>(null);
const comprobanteError = ref<string | null>(null);
const checkoutError = computed(
    () =>
        (page.props.errors as Record<string, string> | undefined)?.pedido ??
        null,
);

const WA_PHONE = '59174828621';
const whatsappPath =
    'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z';

function formatCurrency(value: number): string {
    return `${Math.round(value)} bs`;
}

function onCantidadChange(item: CartItem, event: Event) {
    const target = event.target as HTMLInputElement;
    const parsed = Number.parseInt(target.value, 10);
    const next = Number.isNaN(parsed)
        ? 1
        : Math.max(1, Math.min(parsed, item.stock));
    updateCantidad(item.id, next);
}

function buildWhatsAppUrl(): string {
    const lines = [
        'Hola, Emma Fashion. Deseo consultar por los siguientes productos:',
        '',
        ...items.value.map(
            (item) =>
                `- ${item.nombre} | Talla: ${item.talla || 'No especificada'} | Color: ${item.color || 'No especificado'} | Cantidad: ${item.cantidad} | Subtotal: ${formatCurrency(item.precio * item.cantidad)}`,
        ),
        '',
        `Total: ${formatCurrency(subtotal.value)}`,
    ];

    return `https://wa.me/${WA_PHONE}?text=${encodeURIComponent(lines.join('\n'))}`;
}

function onComprobanteChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    comprobanteError.value = null;

    if (!file) {
        comprobante.value = null;
        comprobantePreview.value = null;

        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        comprobanteError.value = 'El comprobante no debe superar los 5 MB.';
        comprobante.value = null;
        comprobantePreview.value = null;

        return;
    }

    comprobante.value = file;
    comprobantePreview.value = file.type.startsWith('image/')
        ? URL.createObjectURL(file)
        : null;
}

function registrarSolicitud(
    canal: 'web' | 'whatsapp' | 'qr' | 'transferencia',
) {
    if (items.value.length === 0 || isProcessing.value) {
        return;
    }

    localError.value = null;

    if (!user.value) {
        window.location.href = googleRedirect({
            query: { redirect: '/carrito' },
        }).url;

        return;
    }

    if (!esCliente.value) {
        localError.value =
            'Solo las cuentas de cliente pueden registrar compras. Cierra sesión e ingresa con Google como cliente.';

        return;
    }

    if ((canal === 'qr' || canal === 'transferencia') && !comprobante.value) {
        comprobanteError.value =
            canal === 'qr'
                ? 'Sube el comprobante de tu pago por QR antes de continuar.'
                : 'Sube el comprobante de tu transferencia antes de continuar.';

        return;
    }

    const whatsappUrl = canal === 'whatsapp' ? buildWhatsAppUrl() : null;
    const productos = items.value.map((item) => ({
        producto_id: item.producto_id,
        producto_variante_id: item.producto_variante_id,
        nombre: item.nombre,
        precio: item.precio,
        cantidad: item.cantidad,
        color: item.color,
        talla: item.talla,
        imagen: item.imagen,
    }));

    const whatsappWindow =
        canal === 'whatsapp' ? window.open('', '_blank') : null;
    isProcessing.value = true;

    router.post(
        pedidoStore().url,
        {
            productos,
            metodo_pago:
                canal === 'whatsapp'
                    ? 'Reserva por WhatsApp'
                    : canal === 'qr'
                      ? 'Pago por QR'
                      : canal === 'transferencia'
                        ? 'Transferencia bancaria'
                        : 'Solicitud de compra desde el carrito',
            ...(comprobante.value
                ? { comprobante_pago: comprobante.value }
                : {}),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                clearCart();
                comprobante.value = null;
                comprobantePreview.value = null;
                comprobanteError.value = null;

                if (whatsappUrl) {
                    if (whatsappWindow && !whatsappWindow.closed) {
                        whatsappWindow.location.href = whatsappUrl;
                    } else {
                        window.location.href = whatsappUrl;
                    }
                }
            },
            onError: () => {
                whatsappWindow?.close();
            },
            onFinish: () => {
                isProcessing.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Carrito de Compras" />

    <div class="min-h-screen bg-gradient-to-b from-purple-50/30 to-white">
        <!-- Top bar -->
        <div class="border-b border-purple-100/60 bg-white/70 backdrop-blur-sm">
            <div
                class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4"
            >
                <Link
                    href="/"
                    class="flex items-center gap-2 text-sm text-slate-500 transition-colors hover:text-purple-700"
                >
                    <ChevronLeft class="h-4 w-4" />
                    Seguir comprando
                </Link>
                <div class="flex items-center gap-2 text-sm font-semibold">
                    <ShoppingBag class="h-4 w-4 text-purple-500" />
                    <span
                        class="bg-gradient-to-r from-purple-600 to-pink-500 bg-clip-text text-transparent"
                        >Emma Fashion</span
                    >
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-4xl px-4 py-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-slate-900">
                    Carrito de Compras
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ totalItems }} producto{{ totalItems !== 1 ? 's' : '' }}
                    en tu carrito
                </p>
            </div>

            <div
                v-if="localError || checkoutError"
                class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
            >
                {{ localError || checkoutError }}
            </div>

            <div
                v-if="items.length > 0 && !user"
                class="mb-4 rounded-xl border border-purple-200 bg-purple-50 px-4 py-3 text-sm text-purple-800"
            >
                Inicia sesión con Google para registrar tu solicitud de compra.
                La vendedora la confirmará y recién ahí se descuenta el
                inventario.
            </div>

            <div
                v-else-if="items.length > 0 && !esCliente"
                class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
            >
                Estás usando una cuenta de personal. Para registrar una
                solicitud de compra como cliente, cierra sesión e ingresa con
                Google usando una cuenta de cliente.
            </div>

            <!-- Empty cart -->
            <div
                v-if="items.length === 0"
                class="rounded-xl border border-purple-100/60 bg-white/70 py-16 text-center backdrop-blur-sm"
            >
                <ShoppingCart class="mx-auto mb-4 h-16 w-16 text-slate-300" />
                <h2 class="mb-2 text-xl font-semibold text-slate-600">
                    Tu carrito está vacío
                </h2>
                <p class="mb-6 text-sm text-slate-400">
                    Agrega productos desde nuestro catálogo
                </p>
                <Link
                    href="/catalogo"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all hover:scale-[1.02] hover:shadow-xl"
                >
                    <ShoppingBag class="h-4 w-4" />
                    Explorar catálogo
                </Link>
            </div>

            <!-- Cart items -->
            <div v-else class="space-y-4">
                <div
                    v-for="item in items"
                    :key="item.id"
                    class="flex gap-4 rounded-xl border border-purple-100/60 bg-white/70 p-4 backdrop-blur-sm transition-all hover:shadow-sm"
                >
                    <div
                        class="h-24 w-24 shrink-0 overflow-hidden rounded-lg bg-gradient-to-br from-purple-50 to-pink-50"
                    >
                        <img
                            v-if="item.imagen"
                            :src="item.imagen"
                            :alt="item.nombre"
                            class="h-full w-full object-cover"
                        />
                        <div
                            v-else
                            class="flex h-full items-center justify-center"
                        >
                            <Package class="h-8 w-8 text-purple-300/50" />
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col justify-between">
                        <div>
                            <Link
                                :href="'/catalogo/' + item.producto_id"
                                class="font-semibold text-slate-800 transition-colors hover:text-purple-700"
                            >
                                {{ item.nombre }}
                            </Link>
                            <div
                                class="mt-0.5 flex gap-3 text-xs text-slate-500"
                            >
                                <span v-if="item.color"
                                    >Color: {{ item.color }}</span
                                >
                                <span v-if="item.talla"
                                    >Talla: {{ item.talla }}</span
                                >
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <button
                                    @click="
                                        updateCantidad(
                                            item.id,
                                            item.cantidad - 1,
                                        )
                                    "
                                    :disabled="item.cantidad <= 1"
                                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-purple-200 text-slate-500 transition-all hover:bg-purple-50 disabled:opacity-30"
                                >
                                    <Minus class="h-3 w-3" />
                                </button>
                                <input
                                    type="number"
                                    :min="1"
                                    :max="item.stock"
                                    :value="item.cantidad"
                                    :aria-label="'Cantidad de ' + item.nombre"
                                    title="Máximo: {{ item.stock }}"
                                    class="h-7 w-14 rounded-lg border border-purple-200 bg-white text-center text-sm font-semibold text-slate-700 focus:border-purple-400 focus:ring-2 focus:ring-purple-300 focus:outline-none"
                                    @change="onCantidadChange(item, $event)"
                                />
                                <button
                                    @click="
                                        updateCantidad(
                                            item.id,
                                            item.cantidad + 1,
                                        )
                                    "
                                    :disabled="item.cantidad >= item.stock"
                                    class="flex h-7 w-7 items-center justify-center rounded-lg border border-purple-200 text-slate-500 transition-all hover:bg-purple-50 disabled:opacity-30"
                                >
                                    <Plus class="h-3 w-3" />
                                </button>
                            </div>
                            <div class="flex items-center gap-3">
                                <p class="text-sm font-bold text-purple-700">
                                    {{
                                        formatCurrency(
                                            item.precio * item.cantidad,
                                        )
                                    }}
                                </p>
                                <button
                                    @click="removeItem(item.id)"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-all hover:bg-red-50 hover:text-red-500"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pago por QR -->
                <div
                    class="rounded-xl border border-purple-100/60 bg-white/70 p-6 backdrop-blur-sm"
                >
                    <div
                        class="flex flex-col items-center gap-6 sm:flex-row sm:items-start"
                    >
                        <div
                            class="shrink-0 rounded-xl border border-purple-100 bg-white p-2 shadow-sm"
                        >
                            <img
                                src="/images/qr-pago.png"
                                alt="QR de pago Emma Fashion"
                                class="h-auto w-56 object-contain"
                            />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <QrCode class="h-5 w-5 text-purple-600" />
                                <h2 class="text-lg font-bold text-slate-900">
                                    Pago por QR o transferencia
                                </h2>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                Escanea el QR o realiza una transferencia y
                                adjunta tu comprobante. La administradora lo
                                revisará antes de confirmar tu compra.
                            </p>

                            <label
                                class="mt-4 inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-purple-200 px-5 py-2.5 text-sm font-medium text-purple-700 transition-all hover:bg-purple-50"
                            >
                                <Upload class="h-4 w-4" />
                                Subir comprobante
                                <input
                                    type="file"
                                    accept="image/*,application/pdf"
                                    class="hidden"
                                    @change="onComprobanteChange"
                                />
                            </label>

                            <p
                                v-if="comprobante"
                                class="mt-2 text-xs font-medium text-emerald-600"
                            >
                                {{ comprobante.name }}
                            </p>
                            <p
                                v-if="comprobanteError"
                                class="mt-2 text-xs font-medium text-red-600"
                            >
                                {{ comprobanteError }}
                            </p>
                            <img
                                v-if="comprobantePreview"
                                :src="comprobantePreview"
                                alt="Vista previa del comprobante"
                                class="mt-3 h-32 w-32 rounded-lg border border-purple-100 object-cover"
                            />
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div
                    class="rounded-xl border border-purple-100/60 bg-white/70 p-6 backdrop-blur-sm"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <span class="text-slate-600"
                            >Subtotal ({{ totalItems }} productos)</span
                        >
                        <span
                            class="bg-gradient-to-r from-purple-600 to-pink-500 bg-clip-text text-lg font-bold text-transparent"
                            >{{ formatCurrency(subtotal) }}</span
                        >
                    </div>
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:justify-between"
                    >
                        <Link
                            href="/catalogo"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-purple-200 px-6 py-2.5 text-sm font-medium text-purple-700 transition-all hover:bg-purple-50"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            Continuar comprando
                        </Link>
                        <div class="flex flex-wrap gap-2">
                            <button
                                @click="registrarSolicitud('web')"
                                :disabled="isProcessing"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 to-pink-500 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-purple-200/60 transition-all hover:scale-[1.02] hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:scale-100"
                            >
                                <ShoppingBag class="h-4 w-4" />
                                {{
                                    isProcessing
                                        ? 'Procesando...'
                                        : 'Realizar compra'
                                }}
                            </button>
                            <button
                                @click="registrarSolicitud('qr')"
                                :disabled="isProcessing || !comprobante"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-slate-800 to-slate-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-300/60 transition-all hover:scale-[1.02] hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:scale-100"
                            >
                                <QrCode class="h-4 w-4" />
                                {{
                                    isProcessing
                                        ? 'Procesando...'
                                        : 'Pagar por QR'
                                }}
                            </button>
                            <button
                                @click="registrarSolicitud('transferencia')"
                                :disabled="isProcessing || !comprobante"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-sky-600 to-blue-500 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-sky-200/60 transition-all hover:scale-[1.02] hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:scale-100"
                            >
                                <Upload class="h-4 w-4" />
                                {{
                                    isProcessing
                                        ? 'Procesando...'
                                        : 'Pagar por transferencia'
                                }}
                            </button>
                            <button
                                @click="registrarSolicitud('whatsapp')"
                                :disabled="isProcessing"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#25D366] px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-200/50 transition-all hover:scale-[1.02] hover:bg-[#1ebe5b] hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:scale-100"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    class="h-4 w-4"
                                >
                                    <path :d="whatsappPath" />
                                </svg>
                                {{
                                    isProcessing
                                        ? 'Registrando reserva...'
                                        : 'Solicitar por WhatsApp'
                                }}
                            </button>
                            <button
                                @click="clearCart"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 px-6 py-2.5 text-sm font-medium text-red-600 transition-all hover:bg-red-50"
                            >
                                <Trash2 class="h-4 w-4" />
                                Vaciar carrito
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

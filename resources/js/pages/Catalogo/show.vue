<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ShoppingBag,
    Package,
    ChevronLeft,
    ChevronRight,
    Minus,
    Plus,
    Check,
    ZoomIn,
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { useCart } from '@/composables/useCart';
import { usePermissions } from '@/composables/usePermissions';
import { carrito, catalogo, dashboard } from '@/routes';

type Variante = {
    id: number;
    color_id: number;
    talla_id: number;
    color_nombre: string;
    color_hex: string;
    talla: string;
    stock: number;
    disponible: boolean;
    comprable: boolean;
};

type ColorResumen = {
    color_id: number;
    color_nombre: string;
    color_hex: string;
    stock_total: number;
    disponible: boolean;
};

type TallaResumen = {
    talla_id: number;
    talla: string;
    stock_total: number;
    disponible: boolean;
};

type ProductoData = {
    id: number;
    nombre: string;
    descripcion: string;
    precio_venta: number;
    imagen: string | null;
    imagenes: string[];
    imagen_colores: Array<number | null>;
    categoria: string;
    categoria_id: number;
    marca: string;
    talla: string;
    color: string;
    color_hex: string;
    stock: number;
    disponible: boolean;
    comprable: boolean;
    codigo: number;
    variantes: Variante[];
    colores: ColorResumen[];
    tallas: TallaResumen[];
};

const props = defineProps<{
    producto: ProductoData;
}>();

const page = usePage();
const { hasRole } = usePermissions();
const esPersonal = computed(
    () => Boolean(page.props.auth.user) && !hasRole('cliente'),
);
const rutaRegreso = computed(() =>
    esPersonal.value ? dashboard() : catalogo(),
);

const baseVariant =
    props.producto.variantes.find(
        (variant) => variant.disponible && variant.stock > 0,
    ) ??
    props.producto.variantes[0] ??
    null;
const selectedColorId = ref<number | null>(baseVariant?.color_id ?? null);
const selectedTallaId = ref<number | null>(baseVariant?.talla_id ?? null);
const cantidad = ref(props.producto.stock > 0 ? 1 : 0);
const mainImageError = ref(false);

const hasVariants = computed(() => props.producto.variantes.length > 0);

const filteredTallas = computed(() => {
    if (!selectedColorId.value) {
        return props.producto.tallas;
    }

    return props.producto.variantes
        .filter((v) => v.color_id === selectedColorId.value)
        .reduce<TallaResumen[]>((acc, v) => {
            if (!acc.find((t) => t.talla_id === v.talla_id)) {
                acc.push({
                    talla_id: v.talla_id,
                    talla: v.talla,
                    stock_total: v.stock,
                    disponible: v.disponible && v.stock > 0,
                });
            }

            return acc;
        }, []);
});

const filteredColores = computed(() => {
    if (!selectedTallaId.value) {
        return props.producto.colores;
    }

    return props.producto.variantes
        .filter((v) => v.talla_id === selectedTallaId.value)
        .reduce<ColorResumen[]>((acc, v) => {
            if (!acc.find((c) => c.color_id === v.color_id)) {
                const original = props.producto.colores.find(
                    (c) => c.color_id === v.color_id,
                );

                if (original) {
                    acc.push({
                        ...original,
                        stock_total: v.stock,
                        disponible: v.disponible && v.stock > 0,
                    });
                }
            }

            return acc;
        }, []);
});

const tallaOptions = computed(() =>
    props.producto.tallas
        .filter((t) => ['XS', 'S', 'M', 'L', 'XL'].includes(t.talla))
        .map((t) => {
            const match = filteredTallas.value.find(
                (ft) => ft.talla_id === t.talla_id,
            );

            return {
                talla_id: t.talla_id,
                talla: t.talla,
                disponible: Boolean(match?.disponible),
            };
        }),
);

const colorOptions = computed(() => filteredColores.value);

const selectedColor = computed(() => {
    if (!selectedColorId.value) {
        return null;
    }

    return (
        colorOptions.value.find((c) => c.color_id === selectedColorId.value) ??
        null
    );
});

const selectedTalla = computed(() => {
    if (!selectedTallaId.value) {
        return null;
    }

    return (
        tallaOptions.value.find((t) => t.talla_id === selectedTallaId.value) ??
        null
    );
});

const maxStock = computed(() => {
    if (selectedColorId.value && selectedTallaId.value) {
        if (!hasVariants.value) {
            return props.producto.stock;
        }

        const variante = props.producto.variantes.find(
            (v) =>
                v.color_id === selectedColorId.value &&
                v.talla_id === selectedTallaId.value,
        );

        return variante ? variante.stock : 0;
    }

    return props.producto.stock;
});

const isAvailable = computed(() => {
    if (selectedColorId.value && selectedTallaId.value) {
        if (!hasVariants.value) {
            return props.producto.comprable;
        }

        const variante = props.producto.variantes.find(
            (v) =>
                v.color_id === selectedColorId.value &&
                v.talla_id === selectedTallaId.value,
        );

        return variante ? variante.disponible && variante.stock > 0 : false;
    }

    return props.producto.comprable;
});

const selectedVariante = computed(() => {
    if (!selectedColorId.value || !selectedTallaId.value) {
        return null;
    }

    return (
        props.producto.variantes.find(
            (v) =>
                v.color_id === selectedColorId.value &&
                v.talla_id === selectedTallaId.value,
        ) ?? null
    );
});

function selectColor(colorId: number) {
    selectedColorId.value = colorId === selectedColorId.value ? null : colorId;
}

function selectTalla(tallaId: number) {
    selectedTallaId.value = tallaId === selectedTallaId.value ? null : tallaId;
}

watch(maxStock, (stock) => {
    cantidad.value =
        stock > 0 ? Math.min(Math.max(cantidad.value, 1), stock) : 0;
});

// ---------- Gallery ----------
const galleryImages = computed<string[]>(() => {
    const images = props.producto.imagenes ?? [];

    if (images.length > 0) {
        return images;
    }

    return props.producto.imagen ? [props.producto.imagen] : [];
});

const activeImageIndex = ref(0);
const currentImage = computed(
    () => galleryImages.value[activeImageIndex.value] ?? null,
);

watch(currentImage, () => {
    mainImageError.value = false;
});

const zoomActive = ref(false);
const zoomPos = ref({ x: 50, y: 50 });

function handleZoomMove(e: MouseEvent) {
    const el = e.currentTarget as HTMLElement;
    const rect = el.getBoundingClientRect();
    zoomPos.value = {
        x: ((e.clientX - rect.left) / rect.width) * 100,
        y: ((e.clientY - rect.top) / rect.height) * 100,
    };
}

function selectGalleryImage(index: number) {
    activeImageIndex.value = index;

    const imageColorId = props.producto.imagen_colores[index];

    if (imageColorId) {
        selectedColorId.value = imageColorId;
    }
}

function goPrev() {
    if (galleryImages.value.length === 0) {
        return;
    }

    const previousIndex =
        (activeImageIndex.value - 1 + galleryImages.value.length) %
        galleryImages.value.length;
    void selectGalleryImage(previousIndex);
}

function goNext() {
    if (galleryImages.value.length === 0) {
        return;
    }

    const nextIndex = (activeImageIndex.value + 1) % galleryImages.value.length;
    void selectGalleryImage(nextIndex);
}

// ---------- Cart ----------
const { addItem, totalItems } = useCart();

function addToCart() {
    if (!selectedVariante.value || !isAvailable.value || maxStock.value < 1) {
        return;
    }

    addItem(
        {
            id: props.producto.id,
            nombre: props.producto.nombre,
            precio_venta: props.producto.precio_venta,
            imagen: props.producto.imagen,
            stock: maxStock.value,
        },
        cantidad.value,
        selectedColor.value?.color_nombre ?? null,
        selectedTalla.value?.talla ?? null,
        selectedVariante.value?.id ?? null,
    );

    const toast = document.createElement('div');
    toast.className =
        'fixed bottom-4 right-4 z-50 flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 to-pink-500 px-5 py-3 text-sm font-medium text-white shadow-2xl animate-fade-in';
    toast.textContent =
        '✓ Agregado al carrito (' + totalItems.value + ' productos)';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function formatCurrency(value: number): string {
    return `Bs. ${Number(value).toFixed(2)}`;
}

function isLightColor(hex: string): boolean {
    if (!hex || !hex.startsWith('#')) {
        return false;
    }

    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    return luminance > 0.6;
}

</script>

<template>
    <Head :title="producto.nombre + ' - Emma Fashion'" />

    <div
        class="min-h-screen bg-gradient-to-br from-violet-100 via-pink-50 to-fuchsia-100"
    >
        <!-- ===================== TOP BAR ===================== -->
        <header
            class="sticky top-0 z-40 border-b border-purple-100/50 bg-white/85 backdrop-blur-xl"
        >
            <div
                class="relative mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6"
            >
                <Link
                    :href="rutaRegreso"
                    prefetch="mount"
                    class="group inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors hover:text-purple-700"
                >
                    <ArrowLeft
                        class="h-4 w-4 transition-transform group-hover:-translate-x-0.5"
                    />
                    Volver atrás
                </Link>

                <span
                    class="absolute left-1/2 -translate-x-1/2 bg-gradient-to-r from-purple-600 via-pink-500 to-purple-600 bg-clip-text text-lg font-extrabold tracking-tight text-transparent"
                    >Emma Fashion</span
                >

                <Link
                    :href="carrito()"
                    class="relative inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors hover:text-purple-700"
                >
                    <ShoppingBag class="h-4 w-4" />
                    <span class="hidden sm:inline">Carrito</span>
                    <span
                        v-if="totalItems > 0"
                        class="absolute -top-2 -right-2 flex h-4 w-4 items-center justify-center rounded-full bg-gradient-to-r from-purple-500 to-pink-500 text-[9px] font-bold text-white"
                        >{{ totalItems > 9 ? '9+' : totalItems }}</span
                    >
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-10">
            <div class="grid gap-8 lg:grid-cols-2 lg:gap-14">
                <!-- ===================== LEFT: GALLERY ===================== -->
                <section class="lg:self-start" aria-label="Galería de imágenes">
                    <div
                        class="group relative aspect-square overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_20px_60px_-20px_rgba(147,51,234,0.25)]"
                        @mousemove="handleZoomMove"
                        @mouseenter="zoomActive = true"
                        @mouseleave="zoomActive = false"
                    >
                        <img
                            v-if="currentImage && !mainImageError"
                            :key="activeImageIndex"
                            :src="currentImage"
                            :alt="producto.nombre"
                            loading="eager"
                            class="img-in h-full w-full object-contain select-none"
                            :class="
                                zoomActive ? 'cursor-zoom-in' : 'cursor-default'
                            "
                            :style="
                                zoomActive
                                    ? {
                                          transform: 'scale(1.9)',
                                          transformOrigin: `${zoomPos.x}% ${zoomPos.y}%`,
                                      }
                                    : {}
                            "
                            @error="mainImageError = true"
                        />
                        <div
                            v-else
                            class="flex h-full items-center justify-center bg-gradient-to-br from-purple-50 to-pink-50"
                        >
                            <Package class="h-24 w-24 text-purple-300/50" />
                        </div>

                        <!-- Availability pill -->
                        <div class="absolute top-4 left-4">
                            <span
                                v-if="producto.disponible"
                                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/95 px-3 py-1.5 text-xs font-semibold text-white shadow-sm backdrop-blur"
                            >
                                <span class="relative flex h-2 w-2">
                                    <span
                                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"
                                    ></span>
                                    <span
                                        class="relative inline-flex h-2 w-2 rounded-full bg-white"
                                    ></span>
                                </span>
                                Disponible
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/95 px-3 py-1.5 text-xs font-semibold text-white shadow-sm backdrop-blur"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-white"
                                ></span>
                                No disponible
                            </span>
                        </div>

                        <!-- Zoom hint -->
                        <div
                            class="absolute right-4 bottom-4 flex items-center gap-1.5 rounded-full bg-white/85 px-2.5 py-1.5 text-[11px] font-medium text-slate-500 shadow-sm backdrop-blur transition-opacity duration-300"
                            :class="zoomActive ? 'opacity-0' : 'opacity-100'"
                        >
                            <ZoomIn class="h-3.5 w-3.5" />
                            Pasa el cursor para acercar
                        </div>

                        <!-- Zoom lens -->
                        <div
                            v-if="zoomActive"
                            class="pointer-events-none absolute h-40 w-40 rounded-full border-2 border-purple-400/60 bg-white/10 shadow-[0_0_0_9999px_rgba(255,255,255,0.15)]"
                            :style="{
                                left: zoomPos.x + '%',
                                top: zoomPos.y + '%',
                                transform: 'translate(-50%, -50%)',
                            }"
                        ></div>

                        <!-- Arrows -->
                        <button
                            v-if="galleryImages.length > 1"
                            type="button"
                            aria-label="Imagen anterior"
                            class="absolute top-1/2 left-4 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-600 shadow-lg ring-1 ring-slate-200 transition-all hover:scale-105 hover:text-purple-700"
                            @click="goPrev"
                        >
                            <ChevronLeft class="h-5 w-5" />
                        </button>
                        <button
                            v-if="galleryImages.length > 1"
                            type="button"
                            aria-label="Imagen siguiente"
                            class="absolute top-1/2 right-4 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-600 shadow-lg ring-1 ring-slate-200 transition-all hover:scale-105 hover:text-purple-700"
                            @click="goNext"
                        >
                            <ChevronRight class="h-5 w-5" />
                        </button>
                    </div>
                </section>

                <!-- ===================== RIGHT: INFO ===================== -->
                <section class="flex flex-col gap-6">
                    <!-- Name -->
                    <div>
                        <h1
                            class="text-2xl font-bold tracking-tight sm:text-3xl"
                        >
                            {{ producto.nombre }}
                        </h1>
                        <p class="mt-1 text-sm text-slate-400">
                            Código: #{{ producto.codigo }}
                        </p>
                    </div>

                    <!-- Price -->
                    <div class="border-b border-slate-100 pb-6">
                        <div>
                            <div class="flex items-baseline gap-3">
                                <p
                                    class="bg-gradient-to-r from-purple-600 via-pink-500 to-purple-600 bg-clip-text text-4xl font-extrabold tracking-tight text-transparent"
                                >
                                    {{ formatCurrency(producto.precio_venta) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Short description -->
                    <p
                        v-if="producto.descripcion"
                        class="line-clamp-2 text-sm leading-relaxed text-slate-500"
                    >
                        {{ producto.descripcion }}
                    </p>

                    <!-- Sizes -->
                    <div v-if="tallaOptions.length > 0">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-800">
                                Talla
                            </p>
                            <p
                                v-if="selectedTalla"
                                class="text-sm font-medium text-purple-700"
                            >
                                {{ selectedTalla.talla }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="talla in tallaOptions"
                                :key="talla.talla"
                                type="button"
                                :disabled="!talla.disponible"
                                class="relative flex h-12 min-w-12 items-center justify-center rounded-xl px-3 text-sm font-semibold transition-all duration-200"
                                :class="[
                                    talla.disponible
                                        ? selectedTallaId === talla.talla_id
                                            ? 'border-2 border-purple-600 bg-purple-600 text-white shadow-lg shadow-purple-500/25'
                                            : 'border-2 border-slate-200 bg-white text-slate-700 hover:border-purple-400 hover:bg-purple-50'
                                        : 'cursor-not-allowed border-2 border-slate-100 bg-slate-50 text-slate-300',
                                ]"
                                @click="
                                    talla.disponible &&
                                    talla.talla_id !== null &&
                                    selectTalla(talla.talla_id)
                                "
                            >
                                {{ talla.talla }}
                            </button>
                        </div>
                    </div>

                    <!-- Colors -->
                    <div v-if="colorOptions.length > 0">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-800">
                                Color
                            </p>
                            <p
                                v-if="selectedColor"
                                class="text-sm font-medium text-purple-700"
                            >
                                {{ selectedColor.color_nombre }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button
                                v-for="color in colorOptions"
                                :key="color.color_nombre"
                                type="button"
                                :disabled="!color.disponible"
                                :aria-label="color.color_nombre"
                                :title="color.color_nombre"
                                class="relative flex h-11 w-11 items-center justify-center rounded-full transition-all duration-200"
                                :class="[
                                    color.disponible
                                        ? selectedColorId === color.color_id
                                            ? 'cursor-pointer scale-110 shadow-lg ring-2 shadow-purple-500/20 ring-purple-500 ring-offset-2'
                                            : 'cursor-pointer ring-1 ring-slate-200 hover:scale-110 hover:ring-slate-300'
                                        : 'cursor-not-allowed opacity-30 ring-1 ring-slate-200',
                                ]"
                                :style="{ backgroundColor: color.color_hex }"
                                @click="
                                    color.disponible &&
                                    selectColor(color.color_id)
                                "
                            >
                                <Check
                                    v-if="selectedColorId === color.color_id"
                                    class="h-5 w-5"
                                    :class="
                                        isLightColor(color.color_hex)
                                            ? 'text-slate-800'
                                            : 'text-white'
                                    "
                                    stroke-width="3"
                                />
                            </button>
                        </div>

                        <div
                            v-if="galleryImages.length > 1"
                            class="mt-5 flex flex-wrap gap-3"
                            aria-label="Imágenes disponibles del producto"
                        >
                            <button
                                v-for="(img, i) in galleryImages"
                                :key="'color-image-' + i"
                                type="button"
                                :aria-label="'Mostrar imagen ' + (i + 1)"
                                class="h-20 w-16 overflow-hidden border-2 bg-white transition-all duration-200 hover:-translate-y-0.5"
                                :class="
                                    activeImageIndex === i
                                        ? 'border-slate-900 shadow-md'
                                        : 'border-slate-200 opacity-75 hover:border-purple-300 hover:opacity-100'
                                "
                                @click="selectGalleryImage(i)"
                            >
                                <img
                                    :src="img"
                                    :alt="`${producto.nombre}, imagen ${i + 1}`"
                                    loading="lazy"
                                    class="h-full w-full object-cover"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Quantity -->
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-800">
                                Cantidad
                            </p>
                            <span
                                class="rounded-full bg-purple-100 px-3 py-1 text-sm font-bold text-purple-700"
                            >
                                Existencias disponibles: {{ maxStock }}
                            </span>
                        </div>
                        <div
                            class="flex w-fit items-center gap-1 rounded-xl border-2 border-slate-200 bg-white p-1"
                        >
                            <button
                                type="button"
                                aria-label="Disminuir cantidad"
                                :disabled="cantidad <= 1 || maxStock === 0"
                                class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition-all hover:bg-purple-50 hover:text-purple-700 disabled:opacity-30 disabled:hover:bg-transparent"
                                @click="cantidad = Math.max(1, cantidad - 1)"
                            >
                                <Minus class="h-4 w-4" />
                            </button>
                            <span
                                class="w-14 text-center text-lg font-bold text-slate-800 tabular-nums"
                                >{{ cantidad }}</span
                            >
                            <button
                                type="button"
                                aria-label="Aumentar cantidad"
                                :disabled="
                                    maxStock === 0 || cantidad >= maxStock
                                "
                                class="flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition-all hover:bg-purple-50 hover:text-purple-700 disabled:opacity-30 disabled:hover:bg-transparent"
                                @click="
                                    cantidad = Math.min(maxStock, cantidad + 1)
                                "
                            >
                                <Plus class="h-4 w-4" />
                            </button>
                        </div>
                        <p
                            v-if="maxStock === 0"
                            class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                        >
                            Esta prenda está registrada, pero todavía no tiene
                            existencias. La administradora debe registrar una
                            compra de mercadería para aumentar el inventario.
                        </p>
                    </div>

                    <!-- Add to cart -->
                    <button
                        type="button"
                        :disabled="!isAvailable"
                        class="inline-flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-purple-600 to-pink-500 px-8 text-sm font-bold tracking-wide text-white shadow-lg shadow-purple-300/40 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-purple-400/50 active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:translate-y-0"
                        @click="addToCart"
                    >
                        <ShoppingBag class="h-5 w-5" />
                        {{
                            isAvailable
                                ? 'Agregar al carrito'
                                : 'No disponible para comprar'
                        }}
                    </button>
                </section>
            </div>
        </main>

    </div>
</template>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

@keyframes img-in {
    from {
        opacity: 0;
        transform: scale(1.02);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
.img-in {
    animation: img-in 0.25s ease-out;
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}
</style>

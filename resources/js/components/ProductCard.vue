<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Eye,
    Heart,
    Package,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { show as catalogoShow } from '@/routes/catalogo';

export type ProductColorSummary = {
    color_nombre: string;
    color_hex: string;
    disponible: boolean;
};

export type ProductCardData = {
    id: number;
    nombre: string;
    descripcion?: string | null;
    precio_venta: number;
    imagen: string | null;
    imagenes?: string[];
    categoria: string;
    marca: string;
    genero?: string;
    stock: number;
    disponible: boolean;
    colores?: ProductColorSummary[];
};

const props = withDefaults(
    defineProps<{
        producto: ProductCardData;
        variant?: 'catalog' | 'related';
    }>(),
    {
        variant: 'catalog',
    },
);

const detailUrl = computed(
    () => catalogoShow({ producto: props.producto.id }).url,
);

const productImages = computed(() => {
    const images = props.producto.imagenes?.length
        ? props.producto.imagenes
        : props.producto.imagen
          ? [props.producto.imagen]
          : [];

    return [...new Set(images)];
});

const currentImageIndex = ref(0);
const currentImage = computed(
    () => productImages.value[currentImageIndex.value] ?? null,
);
const hasMultipleImages = computed(() => productImages.value.length > 1);
let slideshowTimer: ReturnType<typeof setInterval> | null = null;

function stopSlideshow(): void {
    if (slideshowTimer !== null) {
        clearInterval(slideshowTimer);
        slideshowTimer = null;
    }
}

function startSlideshow(): void {
    stopSlideshow();

    if (!hasMultipleImages.value) {
        return;
    }

    slideshowTimer = setInterval(() => {
        currentImageIndex.value =
            (currentImageIndex.value + 1) % productImages.value.length;
    }, 3000);
}

function showPreviousImage(): void {
    currentImageIndex.value =
        (currentImageIndex.value - 1 + productImages.value.length) %
        productImages.value.length;
    startSlideshow();
}

function showNextImage(): void {
    currentImageIndex.value =
        (currentImageIndex.value + 1) % productImages.value.length;
    startSlideshow();
}

onMounted(() => {
    productImages.value.forEach((source) => {
        const image = new Image();
        image.src = source;
    });

    startSlideshow();
});

onBeforeUnmount(stopSlideshow);

function formatCurrency(value: number): string {
    return `Bs. ${Number(value).toFixed(2)}`;
}

const FAV_KEY = 'emma_fashion_favorites';
const favorites = ref<number[]>([]);

try {
    favorites.value = JSON.parse(localStorage.getItem(FAV_KEY) || '[]');
} catch {
    favorites.value = [];
}

watch(
    favorites,
    (value) => localStorage.setItem(FAV_KEY, JSON.stringify(value)),
    { deep: true },
);

const isFavorite = computed(() => favorites.value.includes(props.producto.id));

function toggleFavorite() {
    if (isFavorite.value) {
        favorites.value = favorites.value.filter(
            (id) => id !== props.producto.id,
        );
    } else {
        favorites.value = [...favorites.value, props.producto.id];
    }

    window.dispatchEvent(
        new CustomEvent('emma-favorites-updated', {
            detail: favorites.value,
        }),
    );
}
</script>

<template>
    <div class="group relative">
        <Link
            :href="detailUrl"
            prefetch="hover"
            class="block h-full overflow-hidden rounded-[1.6rem] border-[3px] border-white/95 bg-gradient-to-b from-white via-white to-rose-50/90 shadow-[0_16px_38px_-20px_rgba(190,24,93,0.5)] backdrop-blur-sm transition-all duration-300 hover:-translate-y-1.5 hover:border-pink-100 hover:shadow-[0_24px_48px_-16px_rgba(219,39,119,0.32)]"
        >
            <div
                class="relative aspect-[4/5] overflow-hidden bg-gradient-to-br from-purple-50/70 to-pink-50/70"
            >
                <span
                    class="absolute top-3 left-3 z-10 inline-flex items-center gap-1.5 rounded-full bg-emerald-500 px-3 py-1.5 text-[11px] font-semibold text-white shadow-md"
                >
                    <span class="h-2 w-2 rounded-full bg-white"></span>
                    Disponible
                </span>
                <Transition
                    v-if="currentImage"
                    enter-active-class="transition-opacity duration-500 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-500 ease-out"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <img
                        v-if="currentImage"
                        :key="currentImage"
                        :src="currentImage"
                        :alt="producto.nombre"
                        loading="lazy"
                        class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                </Transition>
                <div v-else class="flex h-full items-center justify-center">
                    <Package class="h-14 w-14 text-purple-300/50" />
                </div>

                <div
                    class="absolute inset-x-0 bottom-0 flex translate-y-full items-center justify-center bg-gradient-to-t from-purple-600/90 via-purple-600/60 to-transparent pt-8 pb-3 transition-transform duration-300 group-hover:translate-y-0"
                >
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1 text-[11px] font-semibold text-purple-700 shadow-md"
                    >
                        <Eye class="h-3.5 w-3.5" />
                        Ver producto
                    </span>
                </div>
            </div>

            <div
                class="bg-gradient-to-br from-white via-rose-50/50 to-violet-50/75 p-3.5 sm:p-4"
            >
                <p
                    class="mb-1 truncate text-[10px] font-semibold tracking-wider text-slate-400 uppercase"
                >
                    {{ producto.categoria }} · {{ producto.marca }}
                </p>
                <h3
                    class="line-clamp-1 text-sm font-semibold text-slate-800 transition-colors group-hover:text-purple-700 sm:text-[15px]"
                >
                    {{ producto.nombre }}
                </h3>

                <div class="mt-2 flex items-baseline gap-2">
                    <p class="text-base font-black text-fuchsia-700 sm:text-lg">
                        {{ formatCurrency(producto.precio_venta) }}
                    </p>
                </div>

                <div
                    v-if="producto.colores && producto.colores.length > 0"
                    class="mt-3 flex flex-wrap items-center gap-2"
                    aria-label="Colores disponibles"
                >
                    <span
                        v-for="color in producto.colores"
                        :key="color.color_nombre"
                        :title="color.color_nombre"
                        class="inline-block h-4 w-4 rounded-full border border-slate-200 shadow-sm"
                        :style="{ backgroundColor: color.color_hex }"
                    />
                </div>

                <p
                    v-if="producto.genero"
                    class="mt-2 text-[10px] font-semibold text-slate-500"
                >
                    Género:
                    {{
                        producto.genero === 'femenino'
                            ? 'Femenino'
                            : 'Masculino'
                    }}
                </p>
            </div>
        </Link>

        <template v-if="hasMultipleImages">
            <button
                type="button"
                aria-label="Ver imagen anterior"
                class="absolute top-[38%] left-2 z-20 flex size-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-md backdrop-blur transition-all hover:scale-110 hover:text-pink-600"
                @click="showPreviousImage"
            >
                <ChevronLeft class="size-4" />
            </button>
            <button
                type="button"
                aria-label="Ver imagen siguiente"
                class="absolute top-[38%] right-2 z-20 flex size-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-pink-600 shadow-md backdrop-blur transition-all hover:scale-110"
                @click="showNextImage"
            >
                <ChevronRight class="size-4" />
            </button>
        </template>

        <button
            type="button"
            :aria-label="
                isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos'
            "
            class="absolute top-3 right-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-500 shadow-md backdrop-blur transition-all duration-200 hover:scale-110"
            :class="isFavorite ? 'text-rose-500' : 'hover:text-rose-500'"
            :title="isFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos'"
            @click="toggleFavorite"
        >
            <Heart
                class="h-4.5 w-4.5"
                :fill="isFavorite ? 'currentColor' : 'none'"
            />
        </button>
    </div>
</template>

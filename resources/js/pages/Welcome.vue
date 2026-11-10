<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Heart,
    MessageCircle,
    Shirt,
    Sparkles,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import BrandLogo from '@/components/BrandLogo.vue';
import ProductCard from '@/components/ProductCard.vue';
import type { ProductCardData } from '@/components/ProductCard.vue';
import StorefrontHeader from '@/components/StorefrontHeader.vue';
import { catalogo } from '@/routes';

const props = defineProps<{
    categorias: { id: number; nombre: string; total_productos: number }[];
    marcas: { id: number; nombre: string; total_productos: number }[];
    productosRecientes: ProductCardData[];
}>();

const hiddenCategoryNames = new Set([
    'accesorios',
    'shorts',
    'ropa deportiva',
    'ropa interior',
]);

const visibleCategories = computed(() =>
    props.categorias.filter(
        (category) =>
            !hiddenCategoryNames.has(category.nombre.trim().toLowerCase()),
    ),
);

const categoryImages: Record<string, string> = {
    blusas: '/images/categories/blusas.webp',
    camisas: '/images/categories/camisas.webp',
    chompas: '/images/categories/chompas.webp',
    faldas: '/images/categories/faldas.webp',
    jeans: '/images/categories/jeans.webp',
    pantalones: '/images/categories/pantalones.webp',
    poleras: '/images/categories/poleras.webp',
    sudaderas: '/images/categories/sudaderas.webp',
    vestidos: '/images/categories/vestidos.webp',
};

function categoryImage(name: string): string | undefined {
    return categoryImages[name.trim().toLowerCase()];
}

const brandsTrack = ref<HTMLElement | null>(null);

function scrollBrands(direction: 'left' | 'right'): void {
    brandsTrack.value?.scrollBy({
        left: direction === 'right' ? 520 : -520,
        behavior: 'smooth',
    });
}
</script>

<template>
    <div class="min-h-screen bg-white text-slate-800">
        <Head title="Emma Fashion — Tienda de ropa" />
        <StorefrontHeader />
        <section
            class="relative isolate min-h-[31rem] overflow-hidden sm:min-h-[38rem] lg:min-h-[42rem]"
        >
            <img
                src="/images/emma-fashion-collection-v2.png"
                alt="Colección de prendas Emma Fashion"
                class="absolute inset-0 -z-20 h-full w-full object-cover object-[62%_center] sm:object-center"
                fetchpriority="high"
            />
            <div
                class="absolute inset-0 -z-10 bg-gradient-to-r from-black/55 via-black/25 to-transparent sm:from-black/40 sm:via-black/10"
            ></div>
            <div
                class="mx-auto flex min-h-[31rem] max-w-[88rem] items-center px-4 py-10 sm:min-h-[38rem] sm:px-6 sm:py-20 lg:min-h-[42rem]"
            >
                <div class="max-w-2xl py-6 sm:p-9">
                    <p
                        class="mb-4 inline-flex items-center gap-2 rounded-full bg-pink-100/90 px-3 py-1.5 text-[10px] font-semibold tracking-wide text-fuchsia-600 backdrop-blur-sm sm:px-4 sm:text-xs sm:tracking-wider"
                    >
                        <Sparkles class="size-3.5" />
                        EMMA FASHION · BOUTIQUE DE MODA
                    </p>
                    <h1
                        class="font-['Georgia',serif] text-4xl leading-[0.95] font-bold tracking-tight text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.55)] min-[420px]:text-5xl sm:text-7xl"
                    >
                        Encuentra tu estilo,
                        <br />
                        <span
                            class="mt-2 inline-block -rotate-2 font-['Brush_Script_MT','Segoe_Script',cursive] text-[1.08em] leading-none font-normal tracking-normal text-pink-500 drop-shadow-[0_2px_3px_rgba(0,0,0,0.35)]"
                            >viste a tu manera.</span
                        >
                    </h1>
                    <div
                        class="mt-8 grid max-w-md grid-cols-1 gap-3 min-[420px]:grid-cols-2 sm:mt-10 sm:flex sm:flex-wrap sm:items-center sm:gap-4"
                    >
                        <Link
                            :href="catalogo()"
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-pink-500 to-fuchsia-500 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all hover:shadow-xl"
                            >Explorar colección</Link
                        >
                        <a
                            href="https://wa.me/59174828621"
                            class="inline-flex items-center justify-center gap-2 rounded-full border border-white/70 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20"
                            ><MessageCircle class="size-4" /> Escríbenos</a
                        >
                    </div>
                </div>
            </div>
            <div
                class="absolute right-0 bottom-0 left-0 h-24 bg-gradient-to-t from-white to-transparent"
            ></div>
        </section>
        <main class="mx-auto max-w-[88rem] px-4 sm:px-5">
            <div
                class="grid gap-5 border-b border-rose-100 py-7 min-[520px]:grid-cols-2 sm:py-9 lg:grid-cols-3"
            >
                <div
                    class="group flex min-h-32 items-center gap-5 rounded-2xl border border-rose-100 bg-gradient-to-br from-white via-rose-50/45 to-pink-50/80 px-5 py-5 shadow-[0_10px_30px_rgba(190,24,93,0.08)] transition-all hover:-translate-y-1 hover:border-pink-200 hover:shadow-[0_16px_35px_rgba(190,24,93,0.13)]"
                >
                    <div
                        class="relative size-20 shrink-0 overflow-hidden rounded-full border-4 border-white bg-rose-100 shadow-[0_8px_20px_rgba(244,63,94,0.18)]"
                    >
                        <img
                            src="/images/categories/blusas.webp"
                            alt="Blusa femenina"
                            class="size-full object-cover transition-transform duration-300 group-hover:scale-110"
                        />
                    </div>
                    <div class="min-w-0 border-l border-pink-300/80 pl-5">
                        <p class="font-serif text-xl font-bold text-slate-800">
                            Prendas para tu estilo
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            Explora nuestra colección única.
                        </p>
                    </div>
                </div>
                <div
                    class="group flex min-h-32 items-center gap-5 rounded-2xl border border-rose-100 bg-gradient-to-br from-white via-fuchsia-50/40 to-pink-50/80 px-5 py-5 shadow-[0_10px_30px_rgba(190,24,93,0.08)] transition-all hover:-translate-y-1 hover:border-fuchsia-200 hover:shadow-[0_16px_35px_rgba(190,24,93,0.13)]"
                >
                    <div
                        class="flex size-20 shrink-0 items-center justify-center rounded-full border-4 border-white bg-gradient-to-br from-white to-fuchsia-100 shadow-[0_8px_20px_rgba(217,70,239,0.2)]"
                    >
                        <MessageCircle
                            :stroke-width="1.8"
                            class="size-11 text-pink-500 drop-shadow-sm"
                        />
                    </div>
                    <div class="min-w-0 border-l border-pink-300/80 pl-5">
                        <p class="font-serif text-xl font-bold text-slate-800">
                            Atención personalizada
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            Comunícate por WhatsApp.
                        </p>
                    </div>
                </div>
                <div
                    class="group flex min-h-32 items-center gap-5 rounded-2xl border border-rose-100 bg-gradient-to-br from-white via-rose-50/40 to-pink-50/80 px-5 py-5 shadow-[0_10px_30px_rgba(190,24,93,0.08)] transition-all hover:-translate-y-1 hover:border-rose-200 hover:shadow-[0_16px_35px_rgba(190,24,93,0.13)] min-[520px]:col-span-2 lg:col-span-1"
                >
                    <div
                        class="flex size-20 shrink-0 items-center justify-center rounded-full border-4 border-white bg-gradient-to-br from-white to-rose-100 shadow-[0_8px_20px_rgba(244,63,94,0.2)]"
                    >
                        <Heart
                            :stroke-width="1.8"
                            class="size-11 text-rose-500 drop-shadow-sm"
                        />
                    </div>
                    <div class="min-w-0 border-l border-pink-300/80 pl-5">
                        <p class="font-serif text-xl font-bold text-slate-800">
                            Tus favoritos
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            Encuentra prendas que te encantan.
                        </p>
                    </div>
                </div>
            </div>
            <section class="py-8">
                <div class="mb-5">
                    <div>
                        <h2 class="text-xl font-bold">Categorías</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Encuentra lo que buscas por tipo de prenda.
                        </p>
                    </div>
                </div>
                <div class="flex gap-3 overflow-x-auto pb-2">
                    <Link
                        v-for="categoria in visibleCategories"
                        :key="categoria.id"
                        :href="
                            catalogo({ query: { categoria_id: categoria.id } })
                        "
                        class="group flex min-w-44 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white p-2 text-sm transition-all hover:border-pink-200 hover:shadow-md"
                    >
                        <img
                            v-if="categoryImage(categoria.nombre)"
                            :src="categoryImage(categoria.nombre)"
                            :alt="`Categoría ${categoria.nombre}`"
                            class="aspect-square w-full rounded-lg object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                            loading="lazy"
                        />
                        <div
                            v-else
                            class="flex aspect-square w-full items-center justify-center rounded-lg bg-pink-50"
                        >
                            <Shirt class="size-8 text-fuchsia-500" />
                        </div>
                        <span class="py-3 text-center font-semibold">
                            {{ categoria.nombre }}
                        </span>
                    </Link>
                </div>
            </section>
            <section id="nuevos" class="pb-12">
                <div class="mb-6">
                    <div>
                        <h2 class="text-xl font-bold">Productos nuevos</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Lo último que llegó a nuestra tienda para ti.
                        </p>
                    </div>
                </div>
                <div
                    v-if="productosRecientes.length"
                    class="grid grid-cols-1 gap-4 min-[420px]:grid-cols-2 md:grid-cols-3 xl:grid-cols-5"
                >
                    <ProductCard
                        v-for="producto in productosRecientes"
                        :key="producto.id"
                        :producto="producto"
                    />
                </div>
                <div
                    v-else
                    class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-10 text-center"
                >
                    <Shirt class="mx-auto mb-3 size-10 text-pink-300" />
                    <p class="text-sm font-medium text-slate-500">
                        Pronto encontrarás nuevas prendas disponibles.
                    </p>
                </div>
            </section>
            <section
                v-if="marcas.length"
                class="border-t border-pink-100 py-10"
            >
                <div class="mb-7 text-center">
                    <h2
                        class="text-2xl font-extrabold tracking-tight text-slate-900 uppercase"
                    >
                        ENCUENTRA TUS MARCAS FAVORITAS
                    </h2>
                </div>

                <div class="relative px-10 sm:px-14">
                    <button
                        type="button"
                        aria-label="Ver marcas anteriores"
                        class="absolute top-[4.5rem] left-0 z-10 flex size-10 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-md transition hover:border-fuchsia-300 hover:text-fuchsia-600"
                        @click="scrollBrands('left')"
                    >
                        <ChevronLeft class="size-6" />
                    </button>

                    <div
                        ref="brandsTrack"
                        class="flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth px-2 pb-5"
                    >
                        <Link
                            v-for="marca in marcas"
                            :key="marca.id"
                            :href="catalogo({ query: { marca_id: marca.id } })"
                            :aria-label="`Ver productos de la marca ${marca.nombre}`"
                            class="group flex shrink-0 snap-center flex-col items-center gap-3"
                        >
                            <div
                                class="flex size-36 items-center justify-center rounded-full border-4 border-slate-900 bg-black p-5 shadow-lg transition-all duration-300 group-hover:-translate-y-1 group-hover:border-fuchsia-500 group-hover:shadow-xl sm:size-40"
                            >
                                <BrandLogo :name="marca.nombre" />
                            </div>
                            <span
                                class="max-w-36 truncate text-sm font-semibold text-slate-700 group-hover:text-fuchsia-600"
                            >
                                {{ marca.nombre }}
                            </span>
                        </Link>
                    </div>

                    <button
                        type="button"
                        aria-label="Ver más marcas"
                        class="absolute top-[4.5rem] right-0 z-10 flex size-10 -translate-y-1/2 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-md transition hover:border-fuchsia-300 hover:text-fuchsia-600"
                        @click="scrollBrands('right')"
                    >
                        <ChevronRight class="size-6" />
                    </button>
                </div>
            </section>
        </main>
        <footer
            id="contacto"
            class="bg-gradient-to-br from-slate-900 to-slate-800 text-slate-300"
        >
            <div
                class="mx-auto grid max-w-[88rem] gap-8 px-5 py-10 sm:grid-cols-3"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <img
                            src="/images/icono-emma-fashion-ef.png"
                            alt="Ícono de Emma Fashion"
                            class="size-8 rounded-lg object-cover"
                        />
                        <p class="text-lg font-bold text-white">Emma Fashion</p>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed">
                        Moda y prendas para tu estilo. Cada pieza seleccionada
                        con amor para que te sientas única.
                    </p>
                </div>
                <div>
                    <p class="mb-4 text-sm font-semibold text-white">
                        Contacto
                    </p>
                    <p class="text-sm">Eterazama, Bolivia</p>
                    <a
                        href="https://wa.me/59174828621"
                        class="mt-2 inline-flex items-center gap-2 text-sm transition-colors hover:text-pink-300"
                        ><MessageCircle class="size-4" /> WhatsApp: +591
                        74828621</a
                    >
                </div>
                <div>
                    <p class="mb-4 text-sm font-semibold text-white">
                        Nuestra colección
                    </p>
                    <Link
                        :href="catalogo()"
                        class="text-sm transition-colors hover:text-pink-300"
                        >Explorar catálogo</Link
                    >
                </div>
            </div>
            <div class="border-t border-white/10 px-5 py-5 text-center">
                <p class="text-xs text-slate-400">
                    &copy; {{ new Date().getFullYear() }} Emma Fashion. Todos
                    los derechos reservados.
                </p>
            </div>
        </footer>
    </div>
</template>

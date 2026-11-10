<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Heart,
    X,
    ShoppingBag,
    Store,
    LogOut,
    UserRound,
    Tags,
    ChevronRight,
    Flower2,
    Shirt,
    MessageCircle,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ProductCard from '@/components/ProductCard.vue';
import StorefrontHeader from '@/components/StorefrontHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCart } from '@/composables/useCart';
import { usePermissions } from '@/composables/usePermissions';
import { carrito, catalogo, logout, perfil, principal } from '@/routes';

type ColorResumen = {
    color_nombre: string;
    color_hex: string;
    stock_total: number;
    disponible: boolean;
};

type Producto = {
    id: number;
    nombre: string;
    descripcion: string;
    precio_venta: number;
    imagen: string | null;
    imagenes: string[];
    categoria: string;
    marca: string;
    genero: string;
    stock: number;
    disponible: boolean;
    colores: ColorResumen[];
};

type Marca = {
    id: number;
    nombre: string;
};

type Props = {
    productos: Producto[];
    destacados: Producto[];
    categorias: { id: number; nombre: string }[];
    marcas: Marca[];
    colores: { id: number; nombre: string; codigo_hex: string }[];
    tallas: { id: number; nombre: string }[];
    filtros: Record<string, string>;
};

const props = defineProps<Props>();

const { hasRole } = usePermissions();
const { totalItems } = useCart();

const esCliente = computed(() => hasRole('cliente'));
const search = ref(props.filtros.search ?? '');
const categoriaId = ref(props.filtros.categoria_id ?? 'all');
const marcaId = ref(props.filtros.marca_id ?? 'all');
const genero = ref(props.filtros.genero ?? 'all');
const favorites = ref<number[]>([]);
const showFavorites = ref(false);

const visibleProductos = computed(() =>
    showFavorites.value
        ? props.productos.filter((producto) =>
              favorites.value.includes(producto.id),
          )
        : props.productos,
);

function updateFavorites(event?: Event) {
    if (event instanceof CustomEvent && Array.isArray(event.detail)) {
        favorites.value = event.detail;

        return;
    }

    try {
        favorites.value = JSON.parse(
            localStorage.getItem('emma_fashion_favorites') || '[]',
        );
    } catch {
        favorites.value = [];
    }
}

onMounted(() => {
    updateFavorites();
    window.addEventListener('emma-favorites-updated', updateFavorites);
});

onBeforeUnmount(() => {
    window.removeEventListener('emma-favorites-updated', updateFavorites);
});

const activeFilterChips = computed(() => {
    const chips: { key: string; label: string }[] = [];

    if (search.value) {
        chips.push({ key: 'search', label: `Búsqueda: "${search.value}"` });
    }

    const categoria = props.categorias.find(
        (c) => c.id === Number(categoriaId.value),
    );

    if (categoriaId.value && categoria) {
        chips.push({ key: 'categoria_id', label: categoria.nombre });
    }

    const marca = props.marcas.find((m) => String(m.id) === marcaId.value);

    if (marcaId.value && marcaId.value !== 'all' && marca) {
        chips.push({ key: 'marca_id', label: marca.nombre });
    }

    if (genero.value !== 'all') {
        chips.push({
            key: 'genero',
            label: genero.value === 'masculino' ? 'Hombres' : 'Mujeres',
        });
    }

    return chips;
});

function getFilterParams(): Record<string, string> {
    const params: Record<string, string> = {};

    if (search.value) {
        params.search = search.value;
    }

    if (categoriaId.value && categoriaId.value !== 'all') {
        params.categoria_id = categoriaId.value;
    }

    if (marcaId.value && marcaId.value !== 'all') {
        params.marca_id = marcaId.value;
    }

    if (genero.value !== 'all') {
        params.genero = genero.value;
    }

    return params;
}

function applyFilters() {
    router.get(catalogo().url, getFilterParams(), {
        preserveState: true,
        replace: true,
    });
}

function changeCategory(value: string) {
    categoriaId.value = value;
    applyFilters();
}

function changeGender(value: 'masculino' | 'femenino') {
    genero.value = genero.value === value ? 'all' : value;
    applyFilters();
}

function removeFilter(key: string) {
    if (key === 'search') {
        search.value = '';
    }

    if (key === 'categoria_id') {
        categoriaId.value = 'all';
    }

    if (key === 'marca_id') {
        marcaId.value = 'all';
    }

    if (key === 'genero') {
        genero.value = 'all';
    }

    applyFilters();
}

function clearFilters() {
    showFavorites.value = false;
    search.value = '';
    categoriaId.value = 'all';
    marcaId.value = 'all';
    genero.value = 'all';
    applyFilters();
}
</script>

<template>
    <Head title="Catálogo de Productos" />

    <div
        class="catalog-boutique relative min-h-screen overflow-hidden bg-[linear-gradient(135deg,#fff9f5_0%,#fff0f5_38%,#f9efff_70%,#f4f1ff_100%)]"
    >
        <div
            class="pointer-events-none absolute top-52 -left-32 size-96 rounded-full bg-pink-300/25 blur-3xl"
        ></div>
        <div
            class="pointer-events-none absolute top-[55rem] -right-32 size-[28rem] rounded-full bg-violet-300/20 blur-3xl"
        ></div>
        <Flower2
            class="pointer-events-none absolute top-48 -left-10 size-36 -rotate-12 text-fuchsia-400/35 sm:size-52"
        />
        <Flower2
            class="pointer-events-none absolute top-[30rem] left-2 size-20 rotate-12 text-violet-400/25 sm:size-28"
        />
        <Flower2
            class="pointer-events-none absolute right-0 bottom-6 size-44 rotate-12 text-violet-400/30 sm:size-64"
        />
        <Flower2
            class="pointer-events-none absolute top-64 -right-8 size-28 -rotate-12 text-pink-400/30 sm:size-40"
        />
        <Shirt
            class="pointer-events-none absolute right-5 bottom-64 size-24 -rotate-6 text-fuchsia-400/15 sm:size-36"
            :stroke-width="1.2"
        />
        <StorefrontHeader v-if="!esCliente" :initial-search="search">
            <template #catalog-actions>
                <div class="flex shrink-0 items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        class="h-11 rounded-xl border-pink-200 bg-white px-3 text-xs font-semibold shadow-sm"
                        :class="
                            showFavorites
                                ? 'border-pink-500 bg-pink-50 text-pink-600'
                                : 'text-slate-600'
                        "
                        @click="showFavorites = !showFavorites"
                    >
                        <Heart
                            class="h-4 w-4"
                            :fill="showFavorites ? 'currentColor' : 'none'"
                        />
                        Favoritos
                        <span
                            class="rounded-full bg-pink-100 px-1.5 py-0.5 text-pink-600"
                            >{{ favorites.length }}</span
                        >
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        class="h-11 rounded-xl border border-pink-100 bg-white px-3 text-xs font-semibold text-slate-500 shadow-sm"
                        @click="clearFilters"
                    >
                        <X class="h-3.5 w-3.5" />
                        Limpiar
                    </Button>
                </div>
            </template>
        </StorefrontHeader>
        <header
            v-else
            class="sticky top-0 z-40 border-b border-pink-100/70 bg-white/90 shadow-sm backdrop-blur-xl"
        >
            <div
                class="relative mx-auto flex min-h-20 max-w-[88rem] items-center justify-between gap-5 px-4 py-3 sm:px-6"
            >
                <Link
                    :href="catalogo()"
                    class="flex shrink-0 items-center gap-2.5"
                >
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-purple-500 text-white shadow-lg shadow-pink-200"
                    >
                        <Store class="h-5 w-5" />
                    </span>
                    <span class="text-lg font-extrabold text-pink-600"
                        >Emma Fashion</span
                    >
                </Link>

                <nav
                    class="hidden items-center gap-4 text-sm font-semibold text-slate-700 xl:flex 2xl:gap-7"
                >
                    <Link :href="principal()">Inicio</Link>
                    <Link :href="catalogo()" class="text-pink-600"
                        >Catálogo</Link
                    >
                    <Link :href="catalogo()">Productos</Link>
                    <a href="/#nosotros">Nosotros</a>
                    <a href="/#contacto">Contacto</a>
                    <button
                        type="button"
                        class="transition-colors hover:text-pink-600"
                        :class="genero === 'masculino' ? 'text-pink-600' : ''"
                        @click="changeGender('masculino')"
                    >
                        Hombres
                    </button>
                    <button
                        type="button"
                        class="transition-colors hover:text-pink-600"
                        :class="genero === 'femenino' ? 'text-pink-600' : ''"
                        @click="changeGender('femenino')"
                    >
                        Mujeres
                    </button>
                    <a
                        href="https://wa.me/59174828621"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-xl bg-emerald-500 px-4 py-2.5 text-white shadow-md shadow-emerald-200"
                        >WhatsApp</a
                    >
                </nav>

                <div class="flex shrink-0 items-center gap-3">
                    <Link
                        :href="perfil()"
                        class="group hidden h-11 items-center gap-2 rounded-2xl border border-pink-200 bg-gradient-to-r from-pink-50 to-fuchsia-50 px-3 shadow-sm transition-all hover:border-pink-400 hover:shadow-md sm:flex"
                        aria-label="Mi cuenta"
                    >
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-fuchsia-600 text-white shadow-md shadow-pink-200 transition-transform group-hover:scale-105"
                        >
                            <UserRound class="h-5 w-5" />
                        </span>
                        <span class="text-sm font-bold text-slate-800">
                            Mi cuenta
                        </span>
                    </Link>
                    <Link
                        :href="perfil()"
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-pink-500 to-fuchsia-600 text-white shadow-md shadow-pink-200 sm:hidden"
                        aria-label="Mi cuenta"
                    >
                        <UserRound class="h-5 w-5" />
                    </Link>
                    <Link
                        :href="carrito()"
                        class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-pink-600 shadow-md ring-1 ring-pink-100"
                        aria-label="Mi carrito"
                    >
                        <ShoppingBag class="h-5 w-5" />
                        <span
                            class="absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-pink-500 px-1 text-[10px] font-bold text-white"
                            >{{ totalItems }}</span
                        >
                    </Link>
                    <Link
                        :href="logout()"
                        method="post"
                        as="button"
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-rose-500 shadow-md ring-1 ring-pink-100 transition-colors hover:bg-rose-50"
                        aria-label="Cerrar sesión"
                    >
                        <LogOut class="h-5 w-5" />
                    </Link>
                </div>
            </div>
            <nav
                class="flex items-center gap-2 overflow-x-auto border-t border-pink-100 px-4 py-2 text-xs font-semibold text-slate-700 xl:hidden"
                aria-label="Navegación del catálogo"
            >
                <Link
                    :href="principal()"
                    class="shrink-0 rounded-full bg-white px-3 py-2"
                >
                    Inicio
                </Link>
                <Link
                    :href="catalogo()"
                    class="shrink-0 rounded-full bg-pink-100 px-3 py-2 text-pink-700"
                >
                    Catálogo
                </Link>
                <button
                    type="button"
                    class="shrink-0 rounded-full px-3 py-2"
                    :class="
                        genero === 'masculino'
                            ? 'bg-pink-500 text-white'
                            : 'bg-white'
                    "
                    @click="changeGender('masculino')"
                >
                    Hombres
                </button>
                <button
                    type="button"
                    class="shrink-0 rounded-full px-3 py-2"
                    :class="
                        genero === 'femenino'
                            ? 'bg-pink-500 text-white'
                            : 'bg-white'
                    "
                    @click="changeGender('femenino')"
                >
                    Mujeres
                </button>
                <a
                    href="/#contacto"
                    class="shrink-0 rounded-full bg-white px-3 py-2"
                >
                    Contacto
                </a>
            </nav>
        </header>

        <main
            class="relative z-10 mx-auto min-h-screen max-w-[92rem] px-3 py-3 sm:px-6 sm:py-4"
        >
            <div
                class="grid items-start gap-5 lg:grid-cols-[270px_minmax(0,1fr)] xl:gap-7"
            >
                <!-- ===================== FILTERS PANEL ===================== -->
                <div
                    class="relative grid gap-3 overflow-hidden rounded-[2rem] border border-white/90 bg-gradient-to-br from-white/95 via-pink-50/95 to-violet-100/90 p-4 shadow-[0_22px_55px_-28px_rgba(168,85,247,0.55)] backdrop-blur-xl lg:sticky lg:top-28 lg:gap-4 lg:p-5"
                >
                    <div
                        class="pointer-events-none absolute -top-12 -right-10 h-32 w-32 rounded-full bg-pink-300/30 blur-2xl"
                    ></div>
                    <div class="relative">
                        <div class="mb-4 flex items-center gap-3">
                            <span
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-pink-500 to-violet-600 text-white shadow-lg shadow-pink-300/50"
                            >
                                <Tags class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="font-bold text-slate-800">
                                    Categorías
                                </p>
                                <p class="text-[11px] text-slate-500">
                                    Elige tu prenda favorita
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex gap-2 overflow-x-auto pb-1 lg:grid lg:overflow-visible lg:pb-0"
                        >
                            <button
                                type="button"
                                class="flex min-w-[185px] shrink-0 items-center justify-between rounded-xl border px-3 py-2 text-left text-sm transition-all lg:min-w-0"
                                :class="
                                    categoriaId === 'all'
                                        ? 'border-pink-500 bg-pink-50/80 text-pink-700 shadow-sm'
                                        : 'border-pink-100 bg-white/90 text-slate-700 hover:border-pink-300 hover:bg-pink-50/60'
                                "
                                @click="changeCategory('all')"
                            >
                                <span
                                    class="flex items-center gap-2 font-semibold"
                                >
                                    <span
                                        class="flex size-7 items-center justify-center rounded-lg bg-pink-100 text-pink-600"
                                        ><Tags class="size-3.5"
                                    /></span>
                                    Todas las categorías
                                </span>
                                <ChevronRight class="size-4 opacity-60" />
                            </button>
                            <button
                                v-for="c in categorias"
                                :key="c.id"
                                type="button"
                                class="flex min-w-[150px] shrink-0 items-center justify-between rounded-xl border px-3 py-2 text-left text-sm transition-all lg:min-w-0"
                                :class="
                                    categoriaId === String(c.id)
                                        ? 'border-pink-500 bg-pink-50/80 text-pink-700 shadow-sm'
                                        : 'border-pink-100 bg-white/90 text-slate-700 hover:border-pink-300 hover:bg-pink-50/60'
                                "
                                @click="changeCategory(String(c.id))"
                            >
                                <span
                                    class="flex items-center gap-2 font-medium"
                                >
                                    <span
                                        class="flex size-7 items-center justify-center rounded-lg bg-violet-50 text-violet-600"
                                        ><Shirt class="size-3.5"
                                    /></span>
                                    {{ c.nombre }}
                                </span>
                                <ChevronRight class="size-4 opacity-50" />
                            </button>
                        </div>
                        <div v-if="false"></div>
                    </div>
                </div>

                <div class="min-w-0">
                    <div
                        v-if="esCliente"
                        class="mb-3 flex flex-wrap items-center justify-end gap-2"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            class="h-10 rounded-2xl border-pink-200 bg-white px-4 text-xs font-semibold shadow-sm"
                            :class="
                                showFavorites
                                    ? 'border-pink-500 bg-pink-50 text-pink-600'
                                    : 'text-slate-600'
                            "
                            @click="showFavorites = !showFavorites"
                        >
                            <Heart
                                class="h-4 w-4"
                                :fill="showFavorites ? 'currentColor' : 'none'"
                            />
                            Favoritos
                            <span
                                class="rounded-full bg-pink-100 px-2 py-0.5 text-pink-600"
                                >{{ favorites.length }}</span
                            >
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-10 rounded-2xl border border-white/70 bg-white/80 px-4 text-xs font-semibold text-slate-500 shadow-sm hover:text-rose-500"
                            @click="clearFilters"
                        >
                            <X class="h-3.5 w-3.5" />
                            Limpiar
                        </Button>
                    </div>

                    <!-- ===================== ACTIVE FILTERS ===================== -->
                    <div
                        v-if="activeFilterChips.length > 0"
                        class="mb-4 flex flex-wrap gap-2"
                    >
                        <Badge
                            v-for="chip in activeFilterChips"
                            :key="chip.key"
                            variant="secondary"
                            class="text-xs"
                        >
                            {{ chip.label }}
                            <button
                                @click="removeFilter(chip.key)"
                                class="ml-1"
                            >
                                &times;
                            </button>
                        </Badge>
                        <button
                            class="text-xs font-medium text-purple-600 hover:text-purple-800"
                            @click="clearFilters"
                        >
                            Limpiar todo
                        </button>
                    </div>

                    <!-- ===================== PRODUCTS GRID ===================== -->
                    <template v-if="visibleProductos.length > 0">
                        <div
                            class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4"
                        >
                            <ProductCard
                                v-for="producto in visibleProductos"
                                :key="producto.id"
                                :producto="producto"
                                variant="catalog"
                            />
                        </div>
                    </template>

                    <!-- ===================== EMPTY STATE ===================== -->
                    <div
                        v-else
                        class="rounded-3xl border border-white/80 bg-gradient-to-br from-white/95 via-rose-50/90 to-violet-50/90 py-20 text-center shadow-xl shadow-pink-200/30 backdrop-blur"
                    >
                        <div
                            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-purple-100 to-pink-100"
                        >
                            <ShoppingBag class="h-8 w-8 text-purple-400" />
                        </div>
                        <p class="font-semibold text-slate-600">
                            {{
                                showFavorites
                                    ? 'Todavía no tienes productos favoritos.'
                                    : 'No se encontraron productos.'
                            }}
                        </p>
                        <p class="mt-1 text-sm text-slate-400">
                            Intenta ajustar los filtros de búsqueda.
                        </p>
                        <Button
                            variant="outline"
                            class="mt-5 text-xs"
                            @click="clearFilters"
                        >
                            <X class="h-3.5 w-3.5" />
                            Limpiar todos los filtros
                        </Button>
                    </div>
                </div>
            </div>
        </main>

    </div>
</template>

<style scoped>
.catalog-bg {
    background:
        radial-gradient(
            1200px 800px at 85% -10%,
            rgba(233, 213, 255, 0.55),
            transparent 60%
        ),
        radial-gradient(
            1000px 700px at -10% 20%,
            rgba(251, 207, 232, 0.55),
            transparent 60%
        ),
        radial-gradient(
            900px 700px at 110% 90%,
            rgba(252, 165, 165, 0.35),
            transparent 60%
        ),
        radial-gradient(
            700px 500px at 0% 100%,
            rgba(233, 213, 255, 0.45),
            transparent 55%
        ),
        linear-gradient(
            160deg,
            #fdf2f8 0%,
            #fce7f3 30%,
            #fdf4ff 60%,
            #f3e8ff 100%
        );
}
</style>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Search,
    ShoppingBag,
    MapPin,
    MessageCircle,
    Menu,
    CircleUserRound,
    X,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import {
    principal,
    catalogo,
    login,
    dashboard,
    carrito,
    perfil,
} from '@/routes';

const props = withDefaults(defineProps<{ initialSearch?: string }>(), {
    initialSearch: '',
});

const search = ref(props.initialSearch);
const mobileMenuOpen = ref(false);
const page = usePage();

watch(
    () => props.initialSearch,
    (value) => {
        search.value = value;
    },
);

function searchProducts() {
    router.get(catalogo().url, { search: search.value });
}
</script>

<template>
    <header
        class="sticky top-0 z-40 border-b border-pink-100/80 bg-white/95 text-slate-700 shadow-md shadow-pink-100/50 backdrop-blur-xl"
    >
        <div class="bg-slate-900 text-xs text-white/90">
            <div
                class="mx-auto flex max-w-[88rem] items-center justify-between gap-4 px-5 py-2"
            >
                <span class="inline-flex items-center gap-2"
                    ><MapPin class="size-3" /> Eterazama, Bolivia</span
                >
                <a class="hidden sm:block" href="https://wa.me/59174828621"
                    >Atención por WhatsApp</a
                >
            </div>
        </div>
        <div
            class="mx-auto flex max-w-[88rem] flex-wrap items-center gap-3 px-4 py-3 sm:px-5 sm:py-4"
        >
            <Link
                :href="principal()"
                class="flex min-w-0 items-center gap-2 text-base font-bold text-fuchsia-600 sm:text-lg"
                aria-label="Emma Fashion, inicio"
            >
                <img
                    src="/images/icono-emma-fashion-ef.png"
                    alt="Ícono de Emma Fashion"
                    class="size-10 rounded-xl object-cover shadow-sm"
                />
                <span class="truncate">Emma Fashion</span>
            </Link>
            <Link :href="principal()" class="hidden text-sm md:block"
                >Inicio</Link
            >
            <Link :href="catalogo()" class="hidden text-sm md:block"
                >Catálogo</Link
            >
            <form
                class="order-last flex w-full items-center gap-2 rounded-lg border border-pink-100 bg-pink-50/40 px-3 md:order-none md:ml-auto md:min-w-64 md:flex-1"
                @submit.prevent="searchProducts"
            >
                <Search class="size-4 shrink-0" />
                <input
                    v-model="search"
                    aria-label="Buscar productos"
                    placeholder="Buscar prendas..."
                    class="min-w-0 flex-1 bg-transparent py-2.5 text-sm outline-none"
                />
                <button
                    type="submit"
                    class="text-xs font-semibold text-pink-600"
                >
                    Buscar
                </button>
            </form>
            <slot name="catalog-actions" />
            <Link
                v-if="
                    page.props.auth?.user &&
                    page.props.auth.roles?.includes('cliente')
                "
                :href="perfil()"
                aria-label="Mi cuenta"
                class="inline-flex items-center gap-2 rounded-xl border border-pink-100 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-pink-50 hover:text-pink-600"
            >
                <CircleUserRound class="size-5 text-fuchsia-600" />
                <span class="hidden lg:inline">Mi cuenta</span>
            </Link>
            <Link :href="carrito()" aria-label="Carrito" class="ml-auto md:ml-0"
                ><ShoppingBag class="size-5"
            /></Link>
            <Link
                :href="
                    page.props.auth?.user
                        ? dashboard()
                        : login({ query: { cliente: 1 } })
                "
                class="hidden rounded-xl bg-gradient-to-r from-rose-500 to-fuchsia-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-pink-200 transition hover:-translate-y-0.5 hover:shadow-lg sm:inline-flex"
                >{{ page.props.auth?.user ? 'Panel' : 'Iniciar sesión' }}</Link
            >
            <button
                type="button"
                class="inline-flex size-9 items-center justify-center rounded-lg border border-pink-100 text-fuchsia-600 sm:hidden"
                :aria-label="mobileMenuOpen ? 'Cerrar menú' : 'Abrir menú'"
                @click="mobileMenuOpen = !mobileMenuOpen"
            >
                <X v-if="mobileMenuOpen" class="size-5" />
                <Menu v-else class="size-5" />
            </button>
        </div>
        <div
            v-if="mobileMenuOpen"
            class="grid gap-1 border-t border-pink-100 bg-white px-4 py-3 text-sm font-semibold text-slate-700 sm:hidden"
        >
            <Link
                :href="principal()"
                class="rounded-lg px-3 py-2 hover:bg-pink-50"
                >Inicio</Link
            >
            <Link
                :href="catalogo()"
                class="rounded-lg px-3 py-2 hover:bg-pink-50"
                >Catálogo</Link
            >
            <Link
                :href="
                    page.props.auth?.user
                        ? dashboard()
                        : login({ query: { cliente: 1 } })
                "
                class="rounded-lg bg-gradient-to-r from-rose-500 to-fuchsia-600 px-3 py-2 text-center text-white"
                >{{
                    page.props.auth?.user ? 'Ir al panel' : 'Iniciar sesión'
                }}</Link
            >
        </div>
        <nav class="bg-gradient-to-r from-fuchsia-700 to-pink-600 text-white">
            <div
                class="mx-auto flex max-w-[88rem] items-center gap-4 overflow-x-auto px-4 py-2.5 text-[11px] font-semibold whitespace-nowrap sm:gap-6 sm:px-5 sm:py-3 sm:text-xs"
            >
                <Link :href="catalogo()">CATEGORÍAS</Link>
                <Link :href="catalogo()">CATÁLOGO</Link>
                <Link :href="principal({ query: {} }).url + '#nuevos'"
                    >NOVEDADES</Link
                >
                <a
                    href="https://wa.me/59174828621"
                    class="ml-auto inline-flex items-center gap-2"
                    ><MessageCircle class="size-4" /> WHATSAPP</a
                >
            </div>
        </nav>
    </header>
</template>

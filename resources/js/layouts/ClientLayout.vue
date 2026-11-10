<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Heart,
    Home,
    LogOut,
    Package,
    ShoppingBag,
    Store,
    UserRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Toaster } from '@/components/ui/sonner';
import { useCart } from '@/composables/useCart';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { carrito, catalogo, logout, pedidos, perfil } from '@/routes';

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

const user = computed(() => page.props.auth.user ?? null);
const { totalItems } = useCart();

const navItems = computed(() => [
    { title: 'Inicio', href: catalogo().url, icon: Home },
    { title: 'Catálogo', href: catalogo().url, icon: Store },
    {
        title: 'Mi Carrito',
        href: carrito().url,
        icon: ShoppingBag,
        badge: totalItems.value,
    },
    { title: 'Mis Compras', href: pedidos().url, icon: Package },
    { title: 'Mi Perfil', href: perfil().url, icon: UserRound },
]);
</script>

<template>
    <div
        class="min-h-screen bg-gradient-to-b from-purple-50/40 via-white to-pink-50/30"
    >
        <header
            class="sticky top-0 z-40 border-b border-purple-100/50 bg-white/85 backdrop-blur-xl"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6"
            >
                <Link
                    :href="catalogo().url"
                    class="flex shrink-0 items-center gap-2"
                >
                    <Heart class="h-5 w-5 fill-rose-500 text-rose-500" />
                    <span
                        class="bg-gradient-to-r from-purple-600 via-pink-500 to-purple-600 bg-clip-text text-lg font-extrabold tracking-tight text-transparent"
                        >Emma Fashion</span
                    >
                </Link>

                <nav class="flex items-center gap-1 overflow-x-auto">
                    <Link
                        v-for="item in navItems"
                        :key="item.title"
                        :href="item.href"
                        class="group relative inline-flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold whitespace-nowrap transition-colors"
                        :class="
                            isCurrentUrl(item.href)
                                ? 'text-rose-600'
                                : 'text-slate-500 hover:text-purple-700'
                        "
                    >
                        <item.icon class="h-4 w-4" />
                        <span>{{ item.title }}</span>
                        <span
                            v-if="item.badge"
                            class="flex h-4 min-w-4 items-center justify-center rounded-full bg-gradient-to-r from-rose-500 to-pink-500 px-1 text-[9px] font-bold text-white"
                            >{{ item.badge }}</span
                        >
                    </Link>
                </nav>

                <div class="flex shrink-0 items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="text-xs font-semibold text-slate-700">
                            {{ user?.name }}
                        </p>
                        <p class="text-[10px] text-slate-400">Cliente</p>
                    </div>
                    <Link
                        :href="logout().url"
                        method="post"
                        as="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white/70 px-3 py-2 text-sm font-semibold text-rose-600 transition-colors hover:bg-rose-50"
                    >
                        <LogOut class="h-4 w-4" />
                        <span class="hidden sm:inline">Cerrar sesión</span>
                    </Link>
                </div>
            </div>
        </header>

        <main>
            <slot />
        </main>

        <Toaster />
    </div>
</template>

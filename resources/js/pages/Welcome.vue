<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { dashboard, login } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const isScrolled = ref(false);
const dashboardUrl = dashboard().url;

const handleScroll = () => {
    isScrolled.value = window.scrollY > 20;
};

onMounted(() => window.addEventListener('scroll', handleScroll));
onUnmounted(() => window.removeEventListener('scroll', handleScroll));
</script>

<template>
    <Head title="Emma Fashion - Control de Compras, Ventas e Inventario">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen font-sans text-slate-900">
        <!-- Navbar -->
        <header
            :class="[
                'fixed top-0 left-0 right-0 z-50 transition-all duration-300',
                isScrolled
                    ? 'bg-white/70 backdrop-blur-xl shadow-sm border-b border-purple-100/50'
                    : 'bg-transparent',
            ]"
        >
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 via-pink-500 to-blue-500 shadow-lg shadow-purple-200/50">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 4L9 2H15L17 4M7 4H4L2 9M7 4V11M17 4H20L22 9M17 4V11M2 9L4 20H9M2 9H22M22 9L20 20H15M9 20H15M9 20V13M15 20V13M9 11H15" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold tracking-tight bg-gradient-to-r from-purple-600 via-pink-500 to-blue-500 bg-clip-text text-transparent">Emma Fashion</span>
                </Link>
                <nav class="flex items-center gap-3">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="dashboardUrl"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-purple-200/40 transition-all hover:shadow-xl hover:shadow-purple-300/50 hover:scale-[1.02]"
                        >
                            Dashboard
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 transition-all hover:bg-purple-50 hover:text-purple-700"
                        >
                            Iniciar sesión
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-purple-200/40 transition-all hover:shadow-xl hover:shadow-purple-300/50 hover:scale-[1.02]"
                        >
                            Registrarse
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden pt-28 pb-20 sm:pt-36 sm:pb-28">
            <!-- Full-screen fashion background image -->
            <div class="absolute inset-0 z-0">
                <img
                    src="https://images.unsplash.com/photo-1532453288672-3a27e9be9efd?w=1920"
                    alt="Fashion boutique"
                    class="h-full w-full object-cover"
                />
                <div class="absolute inset-0 bg-gradient-to-br from-purple-900/70 via-slate-900/40 to-pink-900/70"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/30 via-transparent to-transparent"></div>
            </div>
            <!-- Decorative blur ambiance -->
            <div class="pointer-events-none absolute top-[-200px] left-1/2 -translate-x-1/2 w-[900px] h-[900px] rounded-full bg-gradient-to-br from-purple-400/15 to-pink-400/15 blur-3xl"></div>
            <div class="pointer-events-none absolute bottom-[-100px] right-[-100px] w-[600px] h-[600px] rounded-full bg-gradient-to-tl from-blue-400/10 to-purple-400/10 blur-3xl"></div>
            <div class="pointer-events-none absolute top-[40%] left-[-200px] w-[400px] h-[400px] rounded-full bg-gradient-to-tr from-pink-400/10 to-blue-400/10 blur-3xl"></div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <!-- Left: Text content -->
                    <div class="text-center lg:text-left">
                        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-medium text-white/90 backdrop-blur-md shadow-sm">
                            <span class="flex h-2 w-2 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 animate-pulse"></span>
                            Sistema de gestión empresarial
                        </div>

                        <h1 class="mb-6 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                            Control total de tu
                            <span class="bg-gradient-to-r from-purple-300 via-pink-300 to-blue-300 bg-clip-text text-transparent">tienda de ropa</span>
                        </h1>

                        <p class="mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-white/70 sm:text-xl lg:mx-0">
                            Gestiona compras, ventas e inventario de Emma Fashion en un solo lugar.
                            Decisiones inteligentes para tu negocio.
                        </p>

                        <div class="flex flex-col items-center justify-center gap-4 sm:flex-row lg:justify-start">
                            <template v-if="!$page.props.auth.user">
                                <Link
                                    :href="register()"
                                    v-if="canRegister"
                                    class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 px-8 text-sm font-semibold text-white shadow-lg shadow-purple-300/30 transition-all hover:shadow-xl hover:shadow-purple-400/40 hover:scale-[1.02] sm:w-auto"
                                >
                                    Comenzar gratis
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </Link>
                            </template>
                            <template v-else>
                                <Link
                                    :href="dashboardUrl"
                                    class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 px-8 text-sm font-semibold text-white shadow-lg shadow-purple-300/30 transition-all hover:shadow-xl hover:shadow-purple-400/40 hover:scale-[1.02] sm:w-auto"
                                >
                                    Ir al Dashboard
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </Link>
                            </template>
                        </div>
                    </div>

                    <!-- Right: Fashion image collage -->
                    <div class="relative hidden lg:block">
                        <div class="relative grid grid-cols-2 gap-4">
                            <!-- Large main image -->
                            <div class="relative col-span-2 overflow-hidden rounded-3xl shadow-2xl shadow-purple-300/30">
                                <img
                                    src="https://images.unsplash.com/photo-1483985988355-763728e1935b?w=900"
                                    alt="Fashion boutique"
                                    class="h-[380px] w-full object-cover"
                                />
                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-tr from-purple-900/15 via-transparent to-pink-900/10"></div>
                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-white/10 via-transparent to-transparent"></div>
                            </div>

                            <!-- Two smaller images -->
                            <div class="relative overflow-hidden rounded-2xl shadow-xl shadow-purple-300/25 group">
                                <img
                                    src="https://images.unsplash.com/photo-1445205170230-053b83016050?w=600"
                                    alt="Fashion model"
                                    class="h-44 w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                />
                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-purple-900/20 via-transparent to-transparent"></div>
                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
                            </div>
                            <div class="relative overflow-hidden rounded-2xl shadow-xl shadow-purple-300/25 group">
                                <img
                                    src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600"
                                    alt="Fashion accessories"
                                    class="h-44 w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                />
                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-pink-900/20 via-transparent to-transparent"></div>
                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
                            </div>
                        </div>

                        <!-- Decorative glassmorphism elements -->
                        <div class="pointer-events-none absolute -top-5 -right-5 h-28 w-28 rounded-3xl border border-white/40 bg-white/20 backdrop-blur-xl shadow-2xl"></div>
                        <div class="pointer-events-none absolute -bottom-5 -left-5 h-20 w-20 rounded-2xl border border-white/30 bg-gradient-to-br from-purple-200/20 to-pink-200/20 backdrop-blur-xl shadow-xl"></div>
                    </div>

                    <!-- Mobile fashion image -->
                    <div class="mt-10 lg:hidden">
                        <div class="relative overflow-hidden rounded-2xl shadow-xl shadow-purple-300/20">
                            <img
                                src="https://images.unsplash.com/photo-1483985988355-763728e1935b?w=800"
                                alt="Fashion boutique"
                                class="h-72 w-full object-cover"
                            />
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-purple-900/30 via-transparent to-transparent"></div>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div class="overflow-hidden rounded-xl shadow-md">
                                <img
                                    src="https://images.unsplash.com/photo-1445205170230-053b83016050?w=500"
                                    alt="Fashion"
                                    class="h-28 w-full object-cover"
                                />
                            </div>
                            <div class="overflow-hidden rounded-xl shadow-md">
                                <img
                                    src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=500"
                                    alt="Fashion"
                                    class="h-28 w-full object-cover"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Fashion Gallery Section -->
        <section class="relative border-t border-purple-100/60 bg-gradient-to-b from-white to-purple-50/30 py-20 sm:py-28">
            <div class="pointer-events-none absolute top-0 left-0 w-[500px] h-[500px] rounded-full bg-gradient-to-tr from-pink-100/20 to-purple-100/20 blur-3xl"></div>
            <div class="pointer-events-none absolute bottom-0 right-0 w-[400px] h-[400px] rounded-full bg-gradient-to-bl from-blue-100/20 to-purple-100/20 blur-3xl"></div>
            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto mb-16 max-w-2xl text-center">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-purple-200/60 bg-white/60 px-4 py-1.5 text-xs font-medium text-purple-600 backdrop-blur-sm">
                        Inspiración
                    </div>
                    <h2 class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl">
                        Descubre nuestra
                        <span class="bg-gradient-to-r from-purple-600 to-pink-500 bg-clip-text text-transparent">colección fashion</span>
                    </h2>
                    <p class="text-lg text-slate-500">
                        Prendas modernas, accesorios elegantes y el estilo que tu tienda necesita.
                    </p>
                </div>

                <!-- 4-image gallery -->
                <div class="grid gap-7 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="group relative overflow-hidden rounded-2xl shadow-xl shadow-purple-200/20 aspect-[4/5]">
                        <img
                            src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800"
                            alt="Fashion model"
                            class="h-full w-full object-cover transition-all duration-700 group-hover:scale-110 brightness-100 group-hover:brightness-110"
                        />
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-purple-900/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        <div class="pointer-events-none absolute inset-0 ring-1 ring-inset ring-white/0 group-hover:ring-white/30 rounded-2xl transition-all duration-500"></div>
                    </div>
                    <div class="group relative overflow-hidden rounded-2xl shadow-xl shadow-purple-200/20 aspect-[4/5]">
                        <img
                            src="https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=800"
                            alt="Shopping bags"
                            class="h-full w-full object-cover transition-all duration-700 group-hover:scale-110 brightness-100 group-hover:brightness-110"
                        />
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-pink-900/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        <div class="pointer-events-none absolute inset-0 ring-1 ring-inset ring-white/0 group-hover:ring-white/30 rounded-2xl transition-all duration-500"></div>
                    </div>
                    <div class="group relative overflow-hidden rounded-2xl shadow-xl shadow-purple-200/20 aspect-[4/5]">
                        <img
                            src="https://images.unsplash.com/photo-1532453288672-3a27e9be9efd?w=800"
                            alt="Boutique"
                            class="h-full w-full object-cover transition-all duration-700 group-hover:scale-110 brightness-100 group-hover:brightness-110"
                        />
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-blue-900/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        <div class="pointer-events-none absolute inset-0 ring-1 ring-inset ring-white/0 group-hover:ring-white/30 rounded-2xl transition-all duration-500"></div>
                    </div>
                    <div class="group relative overflow-hidden rounded-2xl shadow-xl shadow-purple-200/20 aspect-[4/5]">
                        <img
                            src="https://images.unsplash.com/photo-1555529669-e69e7aa0ba9a?w=800"
                            alt="Fashion accessories"
                            class="h-full w-full object-cover transition-all duration-700 group-hover:scale-110 brightness-100 group-hover:brightness-110"
                        />
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-purple-900/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        <div class="pointer-events-none absolute inset-0 ring-1 ring-inset ring-white/0 group-hover:ring-white/30 rounded-2xl transition-all duration-500"></div>
                    </div>
                </div>

                <!-- Decorative floating glass cards -->
                <div class="pointer-events-none absolute top-36 left-[5%] hidden h-14 w-14 items-center justify-center rounded-2xl border border-white/50 bg-white/30 backdrop-blur-xl shadow-xl lg:flex">
                    <svg class="h-6 w-6 text-purple-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                    </svg>
                </div>
                <div class="pointer-events-none absolute bottom-36 right-[5%] hidden h-16 w-16 items-center justify-center rounded-2xl border border-white/40 bg-white/20 backdrop-blur-xl shadow-lg lg:flex">
                    <svg class="h-7 w-7 text-pink-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v-.225a18.81 18.81 0 00-3.8-6.158l-1.256-.383 1.256-.383A18.81 18.81 0 0021 2.898v-.225M21 11.25v7.665c0 .574-.244 1.104-.667 1.48a8.73 8.73 0 01-2.833 1.445c-.932.336-1.936.51-3 .51s-2.068-.174-3-.51a8.73 8.73 0 01-2.833-1.445c-.423-.376-.667-.906-.667-1.48V5.085c0-.574.244-1.104.667-1.48A8.73 8.73 0 0112 2.16c1.064 0 2.068.174 3 .51s1.932.822 2.833 1.445c.423.376.667.906.667 1.48v9.352" />
                    </svg>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="relative border-t border-purple-100/60 bg-white/70 backdrop-blur-sm py-20 sm:py-28">
            <div class="pointer-events-none absolute top-0 right-0 w-[400px] h-[400px] rounded-full bg-gradient-to-bl from-purple-100/30 to-pink-100/30 blur-3xl"></div>
            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto mb-16 max-w-2xl text-center">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-purple-200/60 bg-white/60 px-4 py-1.5 text-xs font-medium text-purple-600 backdrop-blur-sm">
                        Funcionalidades
                    </div>
                    <h2 class="mb-4 text-3xl font-bold tracking-tight sm:text-4xl">
                        Todo lo que necesitas para
                        <span class="bg-gradient-to-r from-purple-600 to-pink-500 bg-clip-text text-transparent">gestionar tu tienda</span>
                    </h2>
                    <p class="text-lg text-slate-500">
                        Un sistema completo diseñado para emprendedores de moda.
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="group relative overflow-hidden rounded-2xl border border-purple-100/60 bg-white/70 p-8 shadow-sm backdrop-blur-sm transition-all hover:border-purple-200 hover:shadow-lg hover:shadow-purple-100/30 hover:bg-white/90">
                        <div class="pointer-events-none absolute -top-10 -right-10 h-24 w-24 rounded-full bg-gradient-to-br from-purple-100/50 to-pink-100/50 blur-2xl transition-all group-hover:from-purple-200/50 group-hover:to-pink-200/50"></div>
                        <div class="relative mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 text-purple-600 shadow-sm transition-all group-hover:from-purple-500 group-hover:to-pink-500 group-hover:text-white group-hover:shadow-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-slate-900">Control de Compras</h3>
                        <p class="text-sm leading-relaxed text-slate-500">
                            Registra y administra todas tus compras a proveedores con fechas, montos y estados personalizados.
                        </p>
                    </div>

                    <div class="group relative overflow-hidden rounded-2xl border border-purple-100/60 bg-white/70 p-8 shadow-sm backdrop-blur-sm transition-all hover:border-purple-200 hover:shadow-lg hover:shadow-purple-100/30 hover:bg-white/90">
                        <div class="pointer-events-none absolute -top-10 -right-10 h-24 w-24 rounded-full bg-gradient-to-br from-purple-100/50 to-pink-100/50 blur-2xl transition-all group-hover:from-purple-200/50 group-hover:to-pink-200/50"></div>
                        <div class="relative mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 text-purple-600 shadow-sm transition-all group-hover:from-purple-500 group-hover:to-pink-500 group-hover:text-white group-hover:shadow-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-slate-900">Gestión de Ventas</h3>
                        <p class="text-sm leading-relaxed text-slate-500">
                            Lleva el registro detallado de todas tus ventas con reportes y análisis en tiempo real.
                        </p>
                    </div>

                    <div class="group relative overflow-hidden rounded-2xl border border-purple-100/60 bg-white/70 p-8 shadow-sm backdrop-blur-sm transition-all hover:border-purple-200 hover:shadow-lg hover:shadow-purple-100/30 hover:bg-white/90">
                        <div class="pointer-events-none absolute -top-10 -right-10 h-24 w-24 rounded-full bg-gradient-to-br from-purple-100/50 to-pink-100/50 blur-2xl transition-all group-hover:from-purple-200/50 group-hover:to-pink-200/50"></div>
                        <div class="relative mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 text-purple-600 shadow-sm transition-all group-hover:from-purple-500 group-hover:to-pink-500 group-hover:text-white group-hover:shadow-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-slate-900">Inventario Inteligente</h3>
                        <p class="text-sm leading-relaxed text-slate-500">
                            Controla tu stock con alertas de inventario bajo y movimientos automatizados.
                        </p>
                    </div>

                    <div class="group relative overflow-hidden rounded-2xl border border-purple-100/60 bg-white/70 p-8 shadow-sm backdrop-blur-sm transition-all hover:border-purple-200 hover:shadow-lg hover:shadow-purple-100/30 hover:bg-white/90">
                        <div class="pointer-events-none absolute -top-10 -right-10 h-24 w-24 rounded-full bg-gradient-to-br from-purple-100/50 to-pink-100/50 blur-2xl transition-all group-hover:from-purple-200/50 group-hover:to-pink-200/50"></div>
                        <div class="relative mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 text-purple-600 shadow-sm transition-all group-hover:from-purple-500 group-hover:to-pink-500 group-hover:text-white group-hover:shadow-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-slate-900">Seguridad Garantizada</h3>
                        <p class="text-sm leading-relaxed text-slate-500">
                            Tus datos protegidos con autenticación segura y cifrado de última generación.
                        </p>
                    </div>

                    <div class="group relative overflow-hidden rounded-2xl border border-purple-100/60 bg-white/70 p-8 shadow-sm backdrop-blur-sm transition-all hover:border-purple-200 hover:shadow-lg hover:shadow-purple-100/30 hover:bg-white/90">
                        <div class="pointer-events-none absolute -top-10 -right-10 h-24 w-24 rounded-full bg-gradient-to-br from-purple-100/50 to-pink-100/50 blur-2xl transition-all group-hover:from-purple-200/50 group-hover:to-pink-200/50"></div>
                        <div class="relative mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 text-purple-600 shadow-sm transition-all group-hover:from-purple-500 group-hover:to-pink-500 group-hover:text-white group-hover:shadow-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-slate-900">Personalizable</h3>
                        <p class="text-sm leading-relaxed text-slate-500">
                            Adapta el sistema a las necesidades específicas de tu tienda de ropa.
                        </p>
                    </div>

                    <div class="group relative overflow-hidden rounded-2xl border border-purple-100/60 bg-white/70 p-8 shadow-sm backdrop-blur-sm transition-all hover:border-purple-200 hover:shadow-lg hover:shadow-purple-100/30 hover:bg-white/90">
                        <div class="pointer-events-none absolute -top-10 -right-10 h-24 w-24 rounded-full bg-gradient-to-br from-purple-100/50 to-pink-100/50 blur-2xl transition-all group-hover:from-purple-200/50 group-hover:to-pink-200/50"></div>
                        <div class="relative mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 text-purple-600 shadow-sm transition-all group-hover:from-purple-500 group-hover:to-pink-500 group-hover:text-white group-hover:shadow-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-slate-900">Soporte Prioritario</h3>
                        <p class="text-sm leading-relaxed text-slate-500">
                            Atención personalizada para resolver tus dudas y acompañarte en cada paso.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="relative overflow-hidden border-t border-purple-100/60 bg-gradient-to-br from-purple-600 via-pink-500 to-blue-500 py-20 sm:py-28">
            <div class="pointer-events-none absolute inset-0 opacity-20">
                <div class="absolute top-0 left-1/4 h-64 w-64 rounded-full bg-white/20 blur-3xl"></div>
                <div class="absolute bottom-0 right-1/4 h-80 w-80 rounded-full bg-white/10 blur-3xl"></div>
            </div>
            <div class="pointer-events-none absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSA2MCAwIEwgMCAwIDAgNjAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] opacity-30"></div>
            <div class="relative mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <h2 class="mb-4 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    ¿Listo para transformar tu tienda?
                </h2>
                <p class="mx-auto mb-8 max-w-xl text-lg text-purple-100">
                    Únete a Emma Fashion y lleva el control de tu negocio al siguiente nivel.
                </p>
                <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <template v-if="!$page.props.auth.user">
                        <Link
                            :href="register()"
                            v-if="canRegister"
                            class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-white px-8 text-sm font-semibold text-purple-600 shadow-lg shadow-purple-900/20 transition-all hover:bg-purple-50 hover:shadow-xl hover:scale-[1.02]"
                        >
                            Crear cuenta gratis
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="dashboardUrl"
                            class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-white px-8 text-sm font-semibold text-purple-600 shadow-lg shadow-purple-900/20 transition-all hover:bg-purple-50 hover:shadow-xl hover:scale-[1.02]"
                        >
                            Ir al Dashboard
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </Link>
                    </template>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-purple-100/60 bg-white/80 backdrop-blur-sm py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 via-pink-500 to-blue-500 shadow-md">
                            <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 4L9 2H15L17 4M7 4H4L2 9M7 4V11M17 4H20L22 9M17 4V11M2 9L4 20H9M2 9H22M22 9L20 20H15M9 20H15M9 20V13M15 20V13M9 11H15" />
                            </svg>
                        </div>
                        <span class="text-sm font-semibold bg-gradient-to-r from-purple-600 to-pink-500 bg-clip-text text-transparent">Emma Fashion</span>
                    </div>
                    <p class="text-sm text-slate-400">
                        &copy; {{ new Date().getFullYear() }} Emma Fashion. Todos los derechos reservados.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title: string;
    description: string;
    icon?: Component;
}>();
</script>

<template>
    <section
        class="relative overflow-hidden rounded-[2rem] border border-white/90 bg-white shadow-[0_22px_55px_-30px_rgba(126,34,206,0.55)] ring-1 ring-pink-100"
    >
        <img
            src="/images/dashboard-boutique-hero.png"
            alt="Boutique Emma Fashion"
            class="absolute inset-0 h-full w-full object-cover object-right"
        />
        <div
            class="absolute inset-0 bg-gradient-to-r from-white via-white/95 to-white/5 sm:via-white/85 lg:via-white/65"
        ></div>
        <div
            class="pointer-events-none absolute -bottom-20 left-1/3 size-52 rounded-full bg-fuchsia-200/35 blur-3xl"
        ></div>
        <div
            class="relative flex min-h-40 flex-col items-start justify-between gap-5 p-5 sm:flex-row sm:items-center sm:p-7 lg:min-h-44"
        >
            <div class="flex max-w-2xl items-center gap-4 sm:gap-5">
                <div
                    class="flex size-16 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 via-fuchsia-600 to-violet-800 text-white shadow-xl shadow-fuchsia-300/60 sm:size-20"
                >
                    <slot name="icon">
                        <component
                            v-if="icon"
                            :is="icon"
                            class="size-8 sm:size-9"
                        />
                    </slot>
                </div>
                <div>
                    <h1
                        class="text-2xl font-black tracking-tight text-slate-950 sm:text-3xl"
                    >
                        {{ title }}
                    </h1>
                    <p
                        class="mt-1 max-w-xl text-sm text-slate-600 sm:text-base"
                    >
                        {{ description }}
                    </p>
                </div>
            </div>
            <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                <slot />
            </div>
        </div>
    </section>
</template>

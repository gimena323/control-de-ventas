<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden p-6 md:p-10"
    >
        <div
            class="absolute inset-0 bg-[linear-gradient(-45deg,#fda4af,#f9a8d4,#c084fc,#fbbf24,#fb7185,#a78bfa)] bg-[length:400%_400%] animate-gradient dark:bg-[linear-gradient(-45deg,#1e1b4b,#312e81,#1e3a5f,#4a1d6a,#831843,#0f172a)]"
        ></div>

        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 size-80 animate-float-slow rounded-full bg-white/20 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 size-80 animate-float-delayed rounded-full bg-white/15 blur-3xl"></div>
            <div class="absolute top-1/2 left-1/4 size-64 animate-float-medium rounded-full bg-pink-300/20 blur-3xl"></div>
        </div>

        <div class="relative flex w-full max-w-md flex-col gap-6">
            <Link
                :href="home()"
                class="flex items-center gap-2 self-center font-medium"
            >
                <div class="flex h-9 w-9 items-center justify-center">
                    <AppLogoIcon
                        class="size-9 fill-current text-black dark:text-white"
                    />
                </div>
            </Link>

            <div class="flex flex-col gap-6">
                <div class="rounded-2xl border border-white/40 bg-[linear-gradient(135deg,#fff1f2,#fdf4ff,#faf5ff,#fff7ed,#fdf2f8)] bg-[length:300%_300%] shadow-2xl shadow-black/5 backdrop-blur-xl animate-card-gradient dark:border-neutral-700/60 dark:bg-[linear-gradient(135deg,#2e1065,#4c1130,#0c4a6e,#1e1b4b)] dark:shadow-neutral-900/40">
                    <CardHeader class="px-10 pt-8 pb-0 text-center">
                        <CardTitle class="text-xl">{{ title }}</CardTitle>
                        <CardDescription>
                            {{ description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-10 py-8">
                        <slot />
                    </CardContent>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-gradient {
    animation: gradient-shift 12s ease infinite;
}

.animate-card-gradient {
    animation: gradient-shift 8s ease infinite;
}

.animate-float-slow {
    animation: float 8s ease-in-out infinite;
}

.animate-float-medium {
    animation: float 6s ease-in-out infinite;
}

.animate-float-delayed {
    animation: float 8s ease-in-out 3s infinite;
}

@keyframes gradient-shift {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0px) scale(1);
    }
    50% {
        transform: translateY(-30px) scale(1.05);
    }
}
</style>

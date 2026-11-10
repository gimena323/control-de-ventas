<script setup lang="ts">
import {
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-start overflow-x-hidden bg-[#050443] px-4 py-4 sm:justify-center"
    >
        <div
            class="fixed inset-0 bg-[radial-gradient(ellipse_at_top_left,#d000d9_0%,transparent_28%),radial-gradient(ellipse_at_bottom_right,#b000dc_0%,transparent_32%),linear-gradient(120deg,#08033e_0%,#060653_55%,#1911a5_100%)]"
        ></div>

        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="fixed -top-24 -right-20 size-72 rounded-full bg-fuchsia-500/30 blur-3xl"
            ></div>
            <div
                class="fixed -bottom-28 -left-16 size-80 rounded-full bg-cyan-500/20 blur-3xl"
            ></div>
            <div
                class="fixed top-1/3 left-[15%] size-44 rounded-full bg-pink-500/20 blur-3xl"
            ></div>
        </div>

        <div
            class="relative w-full max-w-lg rounded-[2rem] bg-gradient-to-r from-fuchsia-500 via-violet-500 to-sky-400 p-[2px] shadow-[0_0_24px_#a000d955]"
        >
            <div
                class="overflow-hidden rounded-[calc(2rem-2px)] bg-[#050443]/95"
            >
                <div class="hidden"></div>
                <CardHeader class="px-6 pt-5 pb-0 text-center sm:px-8">
                    <CardTitle
                        class="text-2xl font-bold tracking-tight text-violet-50"
                    >
                        {{ title }}
                    </CardTitle>
                    <CardDescription
                        class="mt-2 text-sm leading-6 text-indigo-200"
                    >
                        {{ description }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="px-6 pt-3 pb-5 sm:px-8">
                    <slot />
                </CardContent>
            </div>
        </div>
    </div>
</template>

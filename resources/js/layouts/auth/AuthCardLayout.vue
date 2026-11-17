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
        class="relative flex min-h-svh flex-col items-center justify-start overflow-x-hidden bg-[#2b0b2f] px-4 py-4 sm:justify-center"
    >
        <div
            class="fixed inset-0 bg-[radial-gradient(ellipse_at_top_left,#fb7185_0%,transparent_30%),radial-gradient(ellipse_at_bottom_right,#c084fc_0%,transparent_34%),linear-gradient(120deg,#2b0b2f_0%,#5b1647_52%,#7e225f_100%)]"
        ></div>

        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="fixed -top-24 -right-20 size-72 rounded-full bg-fuchsia-400/30 blur-3xl"
            ></div>
            <div
                class="fixed -bottom-28 -left-16 size-80 rounded-full bg-violet-400/30 blur-3xl"
            ></div>
            <div
                class="fixed top-1/3 left-[15%] size-44 rounded-full bg-pink-300/25 blur-3xl"
            ></div>
        </div>

        <div
            class="relative w-full max-w-lg rounded-[2rem] bg-gradient-to-r from-rose-400 via-fuchsia-400 to-violet-400 p-[2px] shadow-[0_0_30px_#f472b677]"
        >
            <div
                class="overflow-hidden rounded-[calc(2rem-2px)] bg-[#3b1238]/95"
            >
                <div class="hidden"></div>
                <CardHeader class="px-6 pt-5 pb-0 text-center sm:px-8">
                    <CardTitle
                        class="text-2xl font-bold tracking-tight text-white"
                    >
                        {{ title }}
                    </CardTitle>
                    <CardDescription
                        class="mt-2 text-sm leading-6 text-pink-100"
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

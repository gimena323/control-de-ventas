<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    name: string;
}>();

const normalizedName = computed(() =>
    props.name
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]/g, ''),
);

type BrandLogo = {
    src: string;
    invert?: boolean;
    contained?: boolean;
};

const brandLogos: Record<string, BrandLogo> = {
    adidas: { src: '/images/brands/adidas.png' },
    calvinklein: { src: '/images/brands/calvin-klein.png' },
    forever21: { src: '/images/brands/forever-21.png' },
    galaxy: { src: '/images/brands/galaxy.png', invert: true },
    levis: { src: '/images/brands/levis.png', contained: true },
    nike: { src: '/images/brands/nike.png', invert: true },
    puma: { src: '/images/brands/puma.png' },
    scrape: { src: '/images/brands/scraper.png', invert: true },
    scraper: { src: '/images/brands/scraper.png', invert: true },
    shein: { src: '/images/brands/shein.png', invert: true },
    tatoo: { src: '/images/brands/tatoo.png', contained: true },
    tattoo: { src: '/images/brands/tatoo.png', contained: true },
    zara: { src: '/images/brands/zara.png', invert: true },
};

const brandLogo = computed(() => brandLogos[normalizedName.value] ?? null);
</script>

<template>
    <div class="relative flex size-full items-center justify-center">
        <img
            v-if="brandLogo"
            :src="brandLogo.src"
            :alt="`Logotipo de ${name}`"
            class="max-h-[72%] max-w-[78%] object-contain drop-shadow-[0_4px_12px_rgba(255,255,255,0.22)]"
            :class="{
                invert: brandLogo.invert,
                'rounded-lg': brandLogo.contained,
            }"
            loading="lazy"
        />
        <div
            v-else
            class="size-[68%] rounded-full border border-white/15 bg-white/[0.03]"
            :title="`${name} todavía no tiene un logotipo disponible`"
        ></div>
    </div>
</template>

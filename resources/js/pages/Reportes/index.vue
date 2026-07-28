<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { index as comprasIndex } from '@/routes/reportes';
import { onMounted } from 'vue';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Reportes', href: comprasIndex() }],
    },
});

onMounted(() => {
    router.visit(comprasIndex());
});
</script>

<template>
    <div class="flex h-full items-center justify-center">
        <p class="text-muted-foreground text-sm">Redirigiendo a Reportes de Compras...</p>
    </div>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Download, ExternalLink, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineProps<{
    titulo: string;
    pdfUrl: string;
    downloadUrl: string;
    returnUrl: string;
    filename: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Vista previa del reporte', href: '#' }],
    },
});
</script>

<template>
    <Head :title="titulo" />

    <div
        class="flex h-[calc(100vh-7rem)] min-h-[42rem] flex-1 flex-col gap-3 rounded-xl p-4"
    >
        <div
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-pink-100 bg-white px-5 py-4 shadow-sm"
        >
            <div class="flex flex-wrap items-center gap-3">
                <Button
                    as-child
                    class="bg-blue-600 text-white hover:bg-blue-700"
                >
                    <a :href="pdfUrl" target="_blank" rel="noopener noreferrer">
                        <ExternalLink class="mr-2 h-4 w-4" />Abrir nueva pestaña
                    </a>
                </Button>
                <Button
                    as-child
                    class="bg-emerald-600 text-white hover:bg-emerald-700"
                >
                    <a :href="downloadUrl"
                        ><Download class="mr-2 h-4 w-4" />Guardar en PDF</a
                    >
                </Button>
            </div>

            <div class="flex min-w-0 items-center gap-2 text-sm text-slate-600">
                <span class="max-w-72 truncate">{{ filename }}</span>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Cerrar vista previa"
                    @click="router.visit(returnUrl)"
                >
                    <X class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <section
            class="min-h-0 flex-1 overflow-hidden rounded-2xl border border-pink-100 bg-slate-900 shadow-lg"
        >
            <div
                class="border-b border-white/10 bg-slate-800 px-5 py-3 font-semibold text-white"
            >
                {{ titulo }}
            </div>
            <iframe
                :src="pdfUrl"
                :title="titulo"
                class="h-[calc(100%-3rem)] w-full bg-slate-800"
            />
        </section>
    </div>
</template>

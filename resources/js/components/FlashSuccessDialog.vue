<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import type { FlashToast } from '@/types/ui';

const isOpen = ref(false);
const message = ref('La operación se realizó correctamente.');
const title = ref('¡Cambios guardados!');

function successDialogContent(toastMessage: string): {
    title: string;
    message: string;
} | null {
    if (toastMessage === 'Prenda creada exitosamente.') {
        return {
            title: '¡Prenda registrada!',
            message: toastMessage,
        };
    }

    if (toastMessage === 'Prenda actualizada exitosamente.') {
        return {
            title: '¡Cambios guardados!',
            message: toastMessage,
        };
    }

    if (toastMessage === 'Categoría creada exitosamente.') {
        return {
            title: '¡Categoría creada!',
            message: 'La categoría se registró correctamente.',
        };
    }

    if (toastMessage === 'Categoría actualizada exitosamente.') {
        return {
            title: '¡Categoría actualizada!',
            message: 'Los cambios se guardaron correctamente.',
        };
    }

    const catalogMessages: Record<string, { title: string; message: string }> =
        {
            'Marca creada exitosamente.': {
                title: '¡Marca creada!',
                message: 'La marca se registró correctamente.',
            },
            'Marca actualizada exitosamente.': {
                title: '¡Marca actualizada!',
                message: 'Los cambios se guardaron correctamente.',
            },
            'Talla creada exitosamente.': {
                title: '¡Talla creada!',
                message: 'La talla se registró correctamente.',
            },
            'Talla actualizada exitosamente.': {
                title: '¡Talla actualizada!',
                message: 'Los cambios se guardaron correctamente.',
            },
            'Color creado exitosamente.': {
                title: '¡Color creado!',
                message: 'El color se registró correctamente.',
            },
            'Color actualizado exitosamente.': {
                title: '¡Color actualizado!',
                message: 'Los cambios se guardaron correctamente.',
            },
        };

    if (catalogMessages[toastMessage]) {
        return catalogMessages[toastMessage];
    }

    if (/^Usuario .+ creado exitosamente\.$/.test(toastMessage)) {
        return {
            title: '¡Usuario creado!',
            message: 'Usuario registrado correctamente.',
        };
    }

    if (/^Usuario .+ actualizado exitosamente\.$/.test(toastMessage)) {
        return {
            title: '¡Usuario actualizado!',
            message: 'Los datos del usuario se actualizaron correctamente.',
        };
    }

    return null;
}

const removeFlashListener = router.on('flash', (event) => {
    const flash = (event as CustomEvent).detail?.flash;
    const toast = flash?.toast as FlashToast | undefined;

    if (toast?.type !== 'success') {
        return;
    }

    const content = successDialogContent(toast.message);

    if (!content) {
        return;
    }

    title.value = content.title;
    message.value = content.message;
    isOpen.value = true;
});

onUnmounted(() => removeFlashListener());
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent
            :show-close-button="false"
            class="max-w-md gap-0 border-0 px-8 py-10 text-center shadow-2xl"
        >
            <div
                class="mx-auto flex size-28 items-center justify-center rounded-full border-4 border-emerald-100 text-emerald-500"
            >
                <Check class="size-16 stroke-[1.75]" />
            </div>

            <DialogTitle class="mt-8 text-3xl font-bold text-neutral-700">
                {{ title }}
            </DialogTitle>
            <DialogDescription class="mt-4 text-lg text-neutral-600">
                {{ message }}
            </DialogDescription>

            <Button
                type="button"
                class="mx-auto mt-8 bg-emerald-500 px-7 text-white hover:bg-emerald-600"
                @click="isOpen = false"
            >
                Entendido
            </Button>
        </DialogContent>
    </Dialog>
</template>

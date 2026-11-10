import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        if (
            data.type === 'success' &&
            ([
                'Prenda creada exitosamente.',
                'Prenda actualizada exitosamente.',
                'Categoría creada exitosamente.',
                'Categoría actualizada exitosamente.',
                'Marca creada exitosamente.',
                'Marca actualizada exitosamente.',
                'Talla creada exitosamente.',
                'Talla actualizada exitosamente.',
                'Color creado exitosamente.',
                'Color actualizado exitosamente.',
            ].includes(data.message) ||
                /^Usuario .+ (creado|actualizado) exitosamente\.$/.test(
                    data.message,
                ))
        ) {
            return;
        }

        toast[data.type](data.message);
    });
}

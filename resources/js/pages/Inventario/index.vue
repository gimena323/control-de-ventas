<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Eye, Package2, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import ManagementHero from '@/components/ManagementHero.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { usePermissions } from '@/composables/usePermissions';
import { index } from '@/routes/inventario';
import { update as updateDisponibilidad } from '@/routes/inventario/disponibilidad';
import { todos as todosLosMovimientos } from '@/routes/inventario/movimientos';
import type { Inventario, InventarioMovimiento } from '@/types';

type Filters = {
    search?: string;
};

const props = defineProps<{
    inventario: Inventario[];
    filters: Filters;
}>();

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Inventario de Ropa', href: index() }],
    },
});

const currentFilters = ref<Filters>({ ...props.filters });
const movimientosDialogOpen = ref(false);
const movimientosList = ref<InventarioMovimiento[]>([]);
const loadingMovimientos = ref(false);
let filterTimer: ReturnType<typeof setTimeout> | undefined;

watch(
    currentFilters,
    (filters) => {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(() => {
            router.get(index().url, compactFilters(filters), {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 350);
    },
    { deep: true },
);

function compactFilters(filters: Filters): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([, value]) =>
                value !== undefined && value !== '' && value !== 'todos',
        ),
    ) as Record<string, string>;
}

function toggleDisponibleItem(item: Inventario): void {
    router.patch(
        updateDisponibilidad(item.id).url,
        { disponible: !item.disponible },
        { preserveScroll: true },
    );
}

async function openTodosLosMovimientos(): Promise<void> {
    movimientosDialogOpen.value = true;
    loadingMovimientos.value = true;
    movimientosList.value = [];

    try {
        const response = await fetch(todosLosMovimientos().url, {
            headers: { Accept: 'application/json' },
        });
        const data = await response.json();
        movimientosList.value = data.movimientos ?? [];
    } finally {
        loadingMovimientos.value = false;
    }
}

function imageUrl(image: string | null | undefined): string | null {
    if (!image) {
        return null;
    }

    return image.startsWith('http') ? image : `/storage/${image}`;
}

function productSizes(item: Inventario): string[] {
    const sizes = new Set<string>();

    item.producto?.variantes?.forEach((variant) => {
        const sizeName = variant.talla?.nombre;

        if (sizeName) {
            sizes.add(sizeName);
        }
    });

    if (sizes.size === 0 && item.producto?.talla?.nombre) {
        sizes.add(item.producto.talla.nombre);
    }

    return Array.from(sizes).sort((first, second) => {
        const order = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Talla Única'];
        const firstPosition = order.indexOf(first);
        const secondPosition = order.indexOf(second);

        if (firstPosition === -1 || secondPosition === -1) {
            return first.localeCompare(second, 'es');
        }

        return firstPosition - secondPosition;
    });
}

function sizeStock(item: Inventario, sizeName: string): number {
    const normalizedSize = sizeName.trim().toLocaleLowerCase('es');
    const matchingVariants = (item.producto?.variantes ?? []).filter(
        (variant) =>
            variant.talla?.nombre?.trim().toLocaleLowerCase('es') ===
            normalizedSize,
    );

    if (matchingVariants.length > 0) {
        return matchingVariants.reduce(
            (total, variant) => total + Number(variant.stock ?? 0),
            0,
        );
    }

    const fallbackSize = item.producto?.talla?.nombre
        ?.trim()
        .toLocaleLowerCase('es');

    return fallbackSize === normalizedSize ? item.stock_actual : 0;
}
</script>

<template>
    <Head title="Inventario de Ropa" />

    <div
        class="flex h-full flex-1 flex-col gap-5 rounded-xl bg-gradient-to-br from-violet-100 via-fuchsia-50 to-pink-100 px-4 pt-4 pb-3"
    >
        <ManagementHero
            title="Inventario de Ropa"
            description="Control automático de existencias, entradas por compras y salidas por ventas."
        >
            <template #icon><Package2 class="h-7 w-7" /></template>
            <div class="flex flex-wrap items-center gap-2">
                <Button
                    variant="outline"
                    class="border-amber-200 text-amber-700 hover:bg-amber-50"
                    :disabled="inventario.length === 0"
                    @click="openTodosLosMovimientos"
                >
                    <Eye class="mr-2 h-4 w-4" />
                    Ver movimientos
                </Button>
            </div>
        </ManagementHero>

        <div
            class="rounded-xl border border-fuchsia-200 bg-gradient-to-r from-pink-100/95 via-fuchsia-50 to-violet-100/95 p-4 shadow-md"
        >
            <div class="relative max-w-2xl">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="currentFilters.search"
                    placeholder="Buscar producto..."
                    class="pl-10"
                />
            </div>
        </div>

        <div
            class="overflow-x-auto rounded-xl border border-fuchsia-200 bg-gradient-to-br from-pink-100/95 via-fuchsia-50/95 to-violet-100/95 shadow-md"
        >
            <table class="w-full min-w-[1180px] text-sm">
                <thead>
                    <tr class="border-b bg-muted/50 text-left">
                        <th class="px-4 py-3 font-medium">Imagen</th>
                        <th class="px-4 py-3 font-medium">Producto</th>
                        <th class="px-4 py-3 font-medium">Categoría</th>
                        <th class="px-4 py-3 font-medium">Marca</th>
                        <th class="px-4 py-3 font-medium">Género</th>
                        <th class="px-4 py-3 font-medium">Talla</th>
                        <th class="px-4 py-3 text-center font-medium">Stock</th>
                        <th class="px-4 py-3 font-medium">Color</th>
                        <th class="px-4 py-3 text-center font-medium">
                            Entradas
                        </th>
                        <th class="px-4 py-3 text-center font-medium">
                            Salidas
                        </th>
                        <th class="px-4 py-3 text-center font-medium">
                            Stock total
                        </th>
                        <th class="px-4 py-3 text-center font-medium">
                            Estado
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in inventario"
                        :key="item.id"
                        class="border-b transition-colors last:border-0 hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">
                            <div
                                class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-lg bg-muted"
                            >
                                <img
                                    v-if="imageUrl(item.producto?.imagen)"
                                    :src="
                                        imageUrl(item.producto?.imagen) ??
                                        undefined
                                    "
                                    :alt="item.producto?.nombre"
                                    class="h-full w-full object-cover"
                                />
                                <Package2
                                    v-else
                                    class="h-5 w-5 text-muted-foreground"
                                />
                            </div>
                        </td>
                        <td class="max-w-48 px-4 py-3 font-medium">
                            {{ item.producto?.nombre ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge variant="secondary">{{
                                item.producto?.categoria?.nombre ?? '—'
                            }}</Badge>
                        </td>
                        <td class="px-4 py-3">
                            {{ item.producto?.marca?.nombre ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge variant="outline" class="text-xs">{{
                                item.producto?.genero === 'femenino'
                                    ? 'Femenino'
                                    : item.producto?.genero === 'masculino'
                                      ? 'Masculino'
                                      : (item.producto?.genero ?? '—')
                            }}</Badge>
                        </td>
                        <td class="px-4 py-3">
                            <div
                                v-if="productSizes(item).length"
                                class="flex flex-col items-start gap-1.5"
                            >
                                <Badge
                                    v-for="size in productSizes(item)"
                                    :key="size"
                                    variant="outline"
                                    class="min-w-9 justify-center border-fuchsia-200 bg-fuchsia-50 whitespace-nowrap text-fuchsia-700"
                                >
                                    {{ size }}
                                </Badge>
                            </div>
                            <span v-else>—</span>
                        </td>
                        <td class="px-4 py-3">
                            <div
                                v-if="productSizes(item).length"
                                class="flex flex-col items-center gap-1.5"
                            >
                                <Badge
                                    v-for="size in productSizes(item)"
                                    :key="size"
                                    variant="outline"
                                    class="min-w-9 justify-center border-violet-200 bg-violet-100 whitespace-nowrap text-violet-800"
                                >
                                    {{ sizeStock(item, size) }}
                                </Badge>
                            </div>
                            <span v-else>—</span>
                        </td>
                        <td class="px-4 py-3">
                            <div
                                v-if="item.producto?.color"
                                class="flex items-center gap-2"
                            >
                                <span
                                    class="h-4 w-4 rounded-full border"
                                    :style="{
                                        backgroundColor:
                                            item.producto.color.codigo_hex,
                                    }"
                                />
                                <span>{{ item.producto.color.nombre }}</span>
                            </div>
                            <span v-else>—</span>
                        </td>
                        <td
                            class="px-4 py-3 text-center font-mono font-semibold text-emerald-600"
                        >
                            {{ item.total_entradas }}
                        </td>
                        <td
                            class="px-4 py-3 text-center font-mono font-semibold text-red-600"
                        >
                            {{ item.total_salidas }}
                        </td>
                        <td
                            class="px-4 py-3 text-center font-mono text-base font-bold"
                        >
                            {{ item.stock_actual }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button
                                v-if="
                                    can('inventario.gestion') &&
                                    item.stock_actual > 0
                                "
                                type="button"
                                class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold transition-colors"
                                :class="
                                    item.disponible
                                        ? 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100'
                                        : 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'
                                "
                                @click="toggleDisponibleItem(item)"
                            >
                                {{
                                    item.disponible
                                        ? '✅ Disponible'
                                        : '❌ No disponible'
                                }}
                            </button>
                            <span
                                v-else
                                class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold"
                                :class="
                                    item.stock_actual > 0 && item.disponible
                                        ? 'border-green-200 bg-green-50 text-green-700'
                                        : 'border-red-200 bg-red-50 text-red-700'
                                "
                            >
                                {{
                                    item.stock_actual > 0 && item.disponible
                                        ? '✅ Disponible'
                                        : '❌ No disponible'
                                }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="inventario.length === 0">
                        <td
                            colspan="12"
                            class="py-12 text-center text-muted-foreground"
                        >
                            <Package2 class="mx-auto mb-2 h-9 w-9 opacity-40" />
                            No se encontraron productos con estos filtros.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Dialog v-model:open="movimientosDialogOpen">
        <DialogContent class="sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>Movimientos de Inventario</DialogTitle>
                <DialogDescription>
                    Entradas y salidas registradas en el inventario.
                </DialogDescription>
            </DialogHeader>
            <div class="max-h-[28rem] overflow-auto py-3">
                <p
                    v-if="loadingMovimientos"
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    Cargando movimientos...
                </p>
                <div
                    v-else-if="movimientosList.length === 0"
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    <Package2 class="mx-auto mb-2 h-8 w-8 opacity-40" />
                    No hay movimientos registrados.
                </div>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50 text-left">
                            <th class="px-3 py-2">Fecha</th>
                            <th class="px-3 py-2">Producto</th>
                            <th class="px-3 py-2">Tipo</th>
                            <th class="px-3 py-2">Cantidad</th>
                            <th class="px-3 py-2">Origen</th>
                            <th class="px-3 py-2">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="movement in movimientosList"
                            :key="movement.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ movement.created_at }}
                            </td>
                            <td class="px-3 py-2 font-medium">
                                {{ movement.producto }}
                            </td>
                            <td class="px-3 py-2">
                                <Badge
                                    :variant="
                                        movement.tipo === 'entrada'
                                            ? 'secondary'
                                            : 'outline'
                                    "
                                    >{{
                                        movement.tipo === 'entrada'
                                            ? 'Entrada'
                                            : 'Salida'
                                    }}</Badge
                                >
                            </td>
                            <td
                                class="px-3 py-2 font-mono font-bold"
                                :class="
                                    movement.tipo === 'entrada'
                                        ? 'text-emerald-600'
                                        : 'text-red-600'
                                "
                            >
                                {{ movement.tipo === 'entrada' ? '+' : '-'
                                }}{{ movement.cantidad }}
                            </td>
                            <td class="px-3 py-2">{{ movement.origen }}</td>
                            <td class="px-3 py-2 text-muted-foreground">
                                {{ movement.motivo ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <DialogFooter
                ><DialogClose as-child
                    ><Button variant="outline">Cerrar</Button></DialogClose
                ></DialogFooter
            >
        </DialogContent>
    </Dialog>
</template>

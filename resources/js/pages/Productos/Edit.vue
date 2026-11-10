<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    FileText,
    FolderOpen,
    Image,
    ImagePlus,
    Package,
    Palette,
    Ruler,
    Save,
    ShoppingBag,
    Tag,
    X,
} from 'lucide-vue-next';
import type { AcceptableValue } from 'reka-ui';
import { computed, ref } from 'vue';
import {
    index,
    update,
} from '@/actions/App/Http/Controllers/ProductosController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import MultiSelectDropdown from '@/components/MultiSelectDropdown.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    detectDominantHex,
    detectDominantHexFromUrl,
    findClosestColorId,
} from '@/lib/colorDetection';
import type { Categoria, Color, Marca, Producto, Talla } from '@/types';

type Props = {
    producto: Producto;
    categorias: Categoria[];
    marcas: Marca[];
    tallas: Talla[];
    colores: Color[];
};

const props = defineProps<Props>();

const tallasIds = computed(() => {
    if (props.producto.variantes && props.producto.variantes.length > 0) {
        const ids = props.producto.variantes.map((v) => v.talla_id);

        return [...new Set(ids)];
    }

    return props.producto.talla_id ? [props.producto.talla_id] : [];
});

const coloresIds = computed(() => {
    if (props.producto.variantes && props.producto.variantes.length > 0) {
        const ids = props.producto.variantes.map((v) => v.color_id);

        return [...new Set(ids)];
    }

    return props.producto.color_id ? [props.producto.color_id] : [];
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Productos', href: index() },
            { title: 'Editar Prenda', href: '#' },
        ],
    },
});

const form = useForm({
    categoria_id: String(props.producto.categoria_id),
    marca_id: String(props.producto.marca_id),
    tallas: tallasIds.value as number[],
    colores: coloresIds.value as number[],
    nombre: props.producto.nombre,
    descripcion: props.producto.descripcion ?? '',
    precio_venta: props.producto.precio_venta,
    imagenes: [] as File[],
    imagenes_colores: [] as (number | null)[],
    imagenes_existentes_colores: [] as { id: number; color_id: number }[],
    imagenes_eliminadas: [] as number[],
    estado: props.producto.estado ?? 'activo',
    genero: props.producto.genero ?? 'femenino',
});

const existingImages = ref(
    (props.producto.imagenes ?? []).map((imagen) => ({
        id: imagen.id,
        color_id: imagen.color_id,
        url: `/storage/${imagen.ruta}`,
    })),
);
const imagePreviews = ref<{ name: string; url: string }[]>([]);
const selectedName = <T extends { id: number; nombre: string }>(
    items: T[],
    id: string,
) => items.find((item) => String(item.id) === id)?.nombre;

function handleImageUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    const available = Math.max(
        0,
        10 - existingImages.value.length - form.imagenes.length,
    );
    const files = Array.from(target.files ?? []).slice(0, available);

    for (const file of files) {
        form.imagenes.push(file);
        imagePreviews.value.push({
            name: file.name,
            url: URL.createObjectURL(file),
        });
    }

    target.value = '';
}

function removeExistingImage(index: number) {
    const [image] = existingImages.value.splice(index, 1);
    form.imagenes_eliminadas.push(image.id);
}

function removeNewImage(index: number) {
    URL.revokeObjectURL(imagePreviews.value[index].url);
    form.imagenes.splice(index, 1);
    imagePreviews.value.splice(index, 1);
}

const productoError = computed(
    () => (form.errors as Record<string, string | undefined>).producto,
);

function onMarcaChange(value: AcceptableValue) {
    form.marca_id = typeof value === 'string' ? value : '';
}

async function submit() {
    const coloresDisponibles = props.colores.filter((color) =>
        form.colores.includes(color.id),
    );

    const coloresExistentes = await Promise.all(
        existingImages.value.map(async (image) => {
            if (image.color_id !== null) {
                return null;
            }

            const hex = await detectDominantHexFromUrl(image.url);

            if (!hex) {
                return null;
            }

            const colorId = findClosestColorId(hex, coloresDisponibles);

            return colorId ? { id: image.id, color_id: colorId } : null;
        }),
    );

    form.imagenes_existentes_colores = coloresExistentes.filter(
        (item): item is { id: number; color_id: number } => item !== null,
    );

    form.imagenes_colores = await Promise.all(
        form.imagenes.map(async (file) => {
            const hex = await detectDominantHex(file);

            if (!hex) {
                return null;
            }

            return findClosestColorId(hex, coloresDisponibles);
        }),
    );

    form.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post(update(props.producto.id).url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Editar Prenda" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-start justify-between">
            <Heading
                title="Editar Prenda"
                description="Actualiza los datos de la prenda seleccionada."
            />
            <Link :href="index()">
                <Button variant="outline">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="bg-glass overflow-hidden rounded-xl border">
                <div
                    class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <div class="grid min-w-0 gap-2">
                        <Label for="categoria_id"
                            ><FolderOpen
                                class="mr-1 inline-block h-3.5 w-3.5"
                            />Categoría</Label
                        >
                        <Select v-model="form.categoria_id">
                            <SelectTrigger>
                                <SelectValue
                                    placeholder="Seleccionar categoría"
                                    >{{
                                        selectedName(
                                            categorias,
                                            form.categoria_id,
                                        )
                                    }}</SelectValue
                                >
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="cat in categorias"
                                    :key="cat.id"
                                    :value="String(cat.id)"
                                >
                                    {{ cat.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.categoria_id" />
                    </div>

                    <div class="grid min-w-0 gap-2">
                        <Label for="marca_id"
                            ><Tag
                                class="mr-1 inline-block h-3.5 w-3.5"
                            />Marca</Label
                        >
                        <Select
                            :model-value="form.marca_id"
                            @update:model-value="onMarcaChange"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar marca">{{
                                    selectedName(marcas, form.marca_id)
                                }}</SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="marca in marcas"
                                    :key="marca.id"
                                    :value="String(marca.id)"
                                >
                                    {{ marca.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.marca_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label
                            ><Ruler
                                class="mr-1 inline-block h-3.5 w-3.5"
                            />Tallas</Label
                        >
                        <MultiSelectDropdown
                            v-model="form.tallas"
                            :items="tallas"
                            placeholder="Seleccionar tallas"
                        />
                        <InputError :message="form.errors.tallas" />
                    </div>

                    <div class="grid gap-2">
                        <Label
                            ><Palette
                                class="mr-1 inline-block h-3.5 w-3.5"
                            />Colores</Label
                        >
                        <MultiSelectDropdown
                            v-model="form.colores"
                            :items="colores"
                            placeholder="Seleccionar colores"
                            show-color-dot
                        />
                        <InputError :message="form.errors.colores" />
                    </div>
                </div>

                <div class="border-t px-6 py-6">
                    <div class="grid gap-2">
                        <Label for="descripcion">
                            <FileText class="mr-1 inline-block h-3.5 w-3.5" />
                            Descripción de la Prenda
                        </Label>
                        <textarea
                            id="descripcion"
                            v-model="form.descripcion"
                            maxlength="1000"
                            rows="4"
                            class="min-h-24 w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Ej.: Polera de algodón, cuello redondo y corte cómodo."
                        ></textarea>
                        <InputError :message="form.errors.descripcion" />
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 gap-6 border-t px-6 py-6 md:grid-cols-3"
                >
                    <div class="grid gap-2">
                        <Label for="nombre"
                            ><ShoppingBag
                                class="mr-1 inline-block h-3.5 w-3.5"
                            />Nombre de la Prenda *</Label
                        >
                        <Input
                            id="nombre"
                            v-model="form.nombre"
                            placeholder="Ej: Jean Skinny Mujer, Polera Oversize"
                        />
                        <InputError :message="form.errors.nombre" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="precio_venta">Precio de Venta (Bs) *</Label>
                        <Input
                            id="precio_venta"
                            v-model="form.precio_venta"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                        />
                        <InputError :message="form.errors.precio_venta" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="estado"
                            ><Package
                                class="mr-1 inline-block h-3.5 w-3.5"
                            />Estado *</Label
                        >
                        <Select v-model="form.estado">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="activo">Activo</SelectItem>
                                <SelectItem value="inactivo"
                                    >Inactivo</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.estado" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="genero">Género *</Label>
                        <Select v-model="form.genero">
                            <SelectTrigger id="genero"
                                ><SelectValue placeholder="Seleccionar género"
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="femenino"
                                    >Femenino</SelectItem
                                >
                                <SelectItem value="masculino"
                                    >Masculino</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.genero" />
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 gap-6 border-t px-6 py-6 md:grid-cols-2"
                >
                    <div class="grid gap-2">
                        <Label
                            ><Image
                                class="mr-1 inline-block h-3.5 w-3.5"
                            />Imagen de la Prenda</Label
                        >
                        <div class="flex items-center gap-3">
                            <label
                                class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-sm transition-colors hover:bg-muted"
                            >
                                <ImagePlus class="h-4 w-4" />
                                <span>Agregar imágenes</span>
                                <input
                                    type="file"
                                    multiple
                                    accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                    class="hidden"
                                    @change="handleImageUpload"
                                />
                            </label>
                        </div>
                        <div
                            v-if="existingImages.length || imagePreviews.length"
                            class="mt-2 grid grid-cols-2 gap-3 rounded-lg border p-3 sm:grid-cols-3 lg:grid-cols-4"
                        >
                            <div
                                v-for="(image, index) in existingImages"
                                :key="image.id"
                                class="group relative aspect-square overflow-hidden rounded-lg border bg-muted"
                            >
                                <img
                                    :src="image.url"
                                    :alt="`Imagen guardada ${index + 1}`"
                                    class="h-full w-full object-cover"
                                />
                                <span
                                    v-if="index === 0"
                                    class="absolute bottom-2 left-2 rounded-full bg-fuchsia-600 px-2 py-1 text-[10px] font-semibold text-white"
                                    >Principal</span
                                >
                                <button
                                    type="button"
                                    class="absolute top-2 right-2 flex size-7 items-center justify-center rounded-full bg-white/95 text-red-500 shadow-sm hover:bg-red-50"
                                    :aria-label="`Eliminar imagen guardada ${index + 1}`"
                                    @click="removeExistingImage(index)"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                            <div
                                v-for="(preview, index) in imagePreviews"
                                :key="preview.url"
                                class="group relative aspect-square overflow-hidden rounded-lg border bg-muted"
                            >
                                <img
                                    :src="preview.url"
                                    :alt="`Nueva imagen ${index + 1}`"
                                    class="h-full w-full object-cover"
                                />
                                <span
                                    v-if="
                                        existingImages.length === 0 &&
                                        index === 0
                                    "
                                    class="absolute bottom-2 left-2 rounded-full bg-fuchsia-600 px-2 py-1 text-[10px] font-semibold text-white"
                                    >Principal</span
                                >
                                <button
                                    type="button"
                                    class="absolute top-2 right-2 flex size-7 items-center justify-center rounded-full bg-white/95 text-red-500 shadow-sm hover:bg-red-50"
                                    :aria-label="`Eliminar imagen nueva ${index + 1}`"
                                    @click="removeNewImage(index)"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Puedes conservar, eliminar o agregar imágenes. La
                            primera se mostrará como principal.
                        </p>
                        <InputError :message="form.errors.imagenes" />
                    </div>
                </div>
            </div>

            <div
                v-if="productoError"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3"
            >
                <InputError :message="productoError" />
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link :href="index()">
                    <Button type="button" variant="outline">
                        <X class="mr-2 h-4 w-4" />
                        Cancelar
                    </Button>
                </Link>
                <Button type="submit" :disabled="form.processing">
                    <Save class="mr-2 h-4 w-4" />
                    Guardar Cambios
                </Button>
            </div>
        </form>
    </div>
</template>

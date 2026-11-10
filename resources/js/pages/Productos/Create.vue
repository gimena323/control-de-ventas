<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CircleDollarSign,
    FileText,
    FolderOpen,
    Image,
    ImagePlus,
    Package,
    Palette,
    Ruler,
    Save,
    ShoppingBag,
    Shirt,
    Sparkles,
    Tag,
    UserRound,
    X,
} from 'lucide-vue-next';
import type { AcceptableValue } from 'reka-ui';
import { computed, ref } from 'vue';
import {
    index,
    store,
} from '@/actions/App/Http/Controllers/ProductosController';
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
import { detectDominantHex, findClosestColorId } from '@/lib/colorDetection';
import type { Categoria, Color, Marca, Talla } from '@/types';

type Props = {
    categorias: Categoria[];
    marcas: Marca[];
    tallas: Talla[];
    colores: Color[];
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Productos', href: index() },
            { title: 'Crear Prenda', href: '#' },
        ],
    },
});

const form = useForm({
    categoria_id: '',
    marca_id: '',
    tallas: [] as number[],
    colores: [] as number[],
    nombre: '',
    descripcion: '',
    precio_venta: '',
    imagenes: [] as File[],
    imagenes_colores: [] as (number | null)[],
    estado: 'activo',
    genero: 'femenino',
});

const imagePreviews = ref<{ name: string; url: string }[]>([]);
const selectedName = <T extends { id: number; nombre: string }>(
    items: T[],
    id: string,
) => items.find((item) => String(item.id) === id)?.nombre;

function handleImageUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    const files = Array.from(target.files ?? []).slice(
        0,
        Math.max(0, 10 - form.imagenes.length),
    );

    for (const file of files) {
        form.imagenes.push(file);
        imagePreviews.value.push({
            name: file.name,
            url: URL.createObjectURL(file),
        });
    }

    target.value = '';
}

function removeImage(index: number) {
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

    form.imagenes_colores = await Promise.all(
        form.imagenes.map(async (file) => {
            const hex = await detectDominantHex(file);

            if (!hex) {
                return null;
            }

            return findClosestColorId(hex, coloresDisponibles);
        }),
    );

    form.post(store().url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            imagePreviews.value.forEach((preview) =>
                URL.revokeObjectURL(preview.url),
            );
            imagePreviews.value = [];
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Crear Nueva Prenda" />

    <div
        class="relative flex h-full flex-1 flex-col gap-5 overflow-hidden rounded-3xl bg-[linear-gradient(135deg,#fff8f7_0%,#fff2f6_42%,#f7f1ff_100%)] p-3 sm:p-5"
    >
        <div
            class="pointer-events-none absolute -top-20 -left-20 size-64 rounded-full bg-pink-200/35 blur-3xl"
        ></div>
        <div
            class="pointer-events-none absolute right-0 bottom-0 size-72 rounded-full bg-violet-200/30 blur-3xl"
        ></div>

        <section
            class="relative overflow-hidden rounded-[2rem] border border-white/80 bg-white/75 shadow-[0_18px_50px_-28px_rgba(126,34,206,0.45)] backdrop-blur"
        >
            <div
                class="absolute inset-y-0 right-0 hidden w-[48%] bg-[url('/images/dashboard-boutique-hero.png')] bg-cover bg-right lg:block"
            ></div>
            <div
                class="absolute inset-0 bg-gradient-to-r from-white via-white/95 to-white/15"
            ></div>
            <div
                class="relative flex min-h-40 items-center justify-between gap-5 px-5 py-6 sm:px-8 lg:min-h-48 lg:max-w-[68%]"
            >
                <div class="flex items-center gap-4 sm:gap-5">
                    <span
                        class="flex size-16 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-fuchsia-700 text-white shadow-xl shadow-pink-200 sm:size-20"
                    >
                        <Shirt class="size-8 sm:size-10" />
                    </span>
                    <div>
                        <span
                            class="mb-2 inline-flex items-center gap-1 rounded-full bg-pink-50 px-3 py-1 text-[11px] font-bold tracking-wider text-pink-600 uppercase"
                        >
                            <Sparkles class="size-3.5" /> Nueva colección
                        </span>
                        <h1
                            class="text-2xl font-black tracking-tight text-slate-900 sm:text-4xl"
                        >
                            Crear Nueva Prenda
                        </h1>
                        <p class="mt-1 text-sm text-slate-600 sm:text-base">
                            Agrega una nueva prenda al inventario.
                        </p>
                    </div>
                </div>
                <Link :href="index()" class="self-start lg:hidden">
                    <Button
                        variant="outline"
                        size="sm"
                        class="rounded-xl bg-white/90"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span class="hidden sm:inline">Volver</span>
                    </Button>
                </Link>
            </div>
            <Link
                :href="index()"
                class="absolute top-5 right-5 hidden lg:block"
            >
                <Button
                    variant="outline"
                    class="rounded-xl border-white/70 bg-white/85 shadow-sm backdrop-blur"
                >
                    <ArrowLeft class="mr-2 h-4 w-4" /> Volver
                </Button>
            </Link>
        </section>

        <form @submit.prevent="submit" class="relative space-y-5">
            <div
                class="overflow-hidden rounded-[2rem] border border-white/90 bg-white/80 p-3 shadow-[0_24px_70px_-35px_rgba(190,24,93,0.45)] backdrop-blur-xl sm:p-5"
            >
                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <div
                        class="grid min-w-0 gap-2 rounded-2xl border border-pink-100 bg-gradient-to-br from-pink-50 to-rose-50/70 p-4 shadow-sm"
                    >
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
                    <div
                        class="grid min-w-0 gap-2 rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50 to-purple-50/70 p-4 shadow-sm"
                    >
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

                    <div
                        class="grid gap-2 rounded-2xl border border-cyan-100 bg-gradient-to-br from-cyan-50 to-teal-50/70 p-4 shadow-sm"
                    >
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

                    <div
                        class="grid gap-2 rounded-2xl border border-orange-100 bg-gradient-to-br from-orange-50 to-amber-50/70 p-4 shadow-sm"
                    >
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

                <div
                    class="mt-4 rounded-2xl border border-violet-100 bg-gradient-to-r from-violet-50/80 to-fuchsia-50/60 p-4"
                >
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
                            class="min-h-28 w-full resize-y rounded-xl border border-violet-100 bg-white/90 px-4 py-3 text-sm shadow-sm outline-none placeholder:text-muted-foreground focus-visible:border-fuchsia-300 focus-visible:ring-[3px] focus-visible:ring-fuchsia-200/60"
                            placeholder="Ej.: Polera de algodón, cuello redondo y corte cómodo."
                        ></textarea>
                        <InputError :message="form.errors.descripcion" />
                    </div>
                </div>

                <div
                    class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"
                >
                    <div
                        class="grid gap-2 rounded-2xl border border-rose-100 bg-rose-50/65 p-4"
                    >
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

                    <div
                        class="grid gap-2 rounded-2xl border border-blue-100 bg-blue-50/65 p-4"
                    >
                        <Label for="precio_venta"
                            ><CircleDollarSign
                                class="mr-1 inline-block h-3.5 w-3.5"
                            />Precio de Venta (Bs) *</Label
                        >
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

                    <div
                        class="grid gap-2 rounded-2xl border border-fuchsia-100 bg-fuchsia-50/65 p-4"
                    >
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
                    <div
                        class="grid gap-2 rounded-2xl border border-amber-100 bg-amber-50/65 p-4"
                    >
                        <Label for="genero"
                            ><UserRound
                                class="mr-1 inline-block h-3.5 w-3.5"
                            />Género *</Label
                        >
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

                <div class="mt-4 grid grid-cols-1 gap-4">
                    <div
                        class="grid gap-3 rounded-2xl border border-dashed border-fuchsia-200 bg-gradient-to-r from-pink-50/80 via-white to-violet-50/80 p-5"
                    >
                        <Label
                            ><Image
                                class="mr-1 inline-block h-3.5 w-3.5"
                            />Imagen de la Prenda</Label
                        >
                        <div class="flex items-center gap-3">
                            <label
                                class="flex cursor-pointer items-center gap-2 rounded-xl border border-fuchsia-200 bg-gradient-to-r from-pink-600 to-fuchsia-700 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-pink-200 transition-all hover:-translate-y-0.5 hover:shadow-xl"
                            >
                                <ImagePlus class="h-4 w-4" />
                                <span>Seleccionar imágenes</span>
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
                            v-if="imagePreviews.length"
                            class="mt-2 grid grid-cols-2 gap-3 rounded-2xl border border-pink-100 bg-white/80 p-3 sm:grid-cols-3 lg:grid-cols-5"
                        >
                            <div
                                v-for="(preview, index) in imagePreviews"
                                :key="preview.url"
                                class="group relative aspect-square overflow-hidden rounded-lg border bg-muted"
                            >
                                <img
                                    :src="preview.url"
                                    :alt="`Vista previa ${index + 1}`"
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
                                    :aria-label="`Eliminar imagen ${index + 1}`"
                                    @click="removeImage(index)"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
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

            <div
                class="flex flex-col-reverse items-stretch justify-end gap-3 sm:flex-row sm:items-center"
            >
                <Link :href="index()">
                    <Button
                        type="button"
                        variant="outline"
                        class="w-full rounded-xl bg-white/90 sm:w-auto"
                    >
                        <X class="mr-2 h-4 w-4" />
                        Cancelar
                    </Button>
                </Link>
                <Button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-xl bg-gradient-to-r from-pink-600 to-fuchsia-700 px-7 shadow-lg shadow-pink-200 hover:from-pink-700 hover:to-fuchsia-800"
                >
                    <Save class="mr-2 h-4 w-4" />
                    Guardar Prenda
                </Button>
            </div>
        </form>
    </div>
</template>

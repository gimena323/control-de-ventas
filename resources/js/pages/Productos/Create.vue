<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, DollarSign, FileText, FolderOpen, Image, ImagePlus, Palette, Plus, Ruler, Save, Shirt, ShoppingBag, Tag, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store } from '@/actions/App/Http/Controllers/ProductosController';
import type { Categoria, Color, Marca, Modelo, Talla } from '@/types';

type Props = {
    categorias: Categoria[];
    marcas: Marca[];
    modelos: Modelo[];
    tallas: Talla[];
    colores: Color[];
};

const props = defineProps<Props>();

const modelosFiltrados = computed(() =>
    props.modelos.filter((m) => String(m.marca_id) === form.marca_id),
);

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
    modelo_id: '',
    talla_id: '',
    color_id: '',
    nombre: '',
    descripcion: '',
    precio_venta: '',
    imagen: null as File | null,
    estado: 'activo',
    variantes: [] as Array<{ color_id: string; talla_id: string; stock: number; disponible: boolean }>,
});

const imagePreview = ref<string | null>(null);

function handleImageUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.imagen = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    form.imagen = null;
    imagePreview.value = null;
}

function onMarcaChange(value: string) {
    form.marca_id = value;
    form.modelo_id = '';
}

function submit() {
    form.post(store().url, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function addVariante() {
    form.variantes.push({ color_id: '', talla_id: '', stock: 0, disponible: true });
}

function removeVariante(index: number) {
    form.variantes.splice(index, 1);
}
</script>

<template>
    <Head title="Crear Nueva Prenda" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-start justify-between">
            <Heading
                title="Crear Nueva Prenda"
                description="Agrega una nueva prenda al inventario."
            />
            <Link :href="index()">
                <Button variant="outline">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="overflow-hidden rounded-xl border bg-glass">
                <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-5">
                    <div class="grid gap-2">
                        <Label for="categoria_id"><FolderOpen class="mr-1 inline-block h-3.5 w-3.5" />Categoría</Label>
                        <Select v-model="form.categoria_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar categoría" />
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

                    <div class="grid gap-2">
                        <Label for="marca_id"><Tag class="mr-1 inline-block h-3.5 w-3.5" />Marca</Label>
                        <Select :model-value="form.marca_id" @update:model-value="onMarcaChange">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar marca" />
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
                        <Label for="modelo_id"><Shirt class="mr-1 inline-block h-3.5 w-3.5" />Modelo de Prenda</Label>
                        <Select v-model="form.modelo_id" :disabled="!form.marca_id">
                            <SelectTrigger>
                                <SelectValue :placeholder="form.marca_id ? 'Selecciona el modelo de la prenda' : 'Primero selecciona una marca'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="modelo in modelosFiltrados"
                                    :key="modelo.id"
                                    :value="String(modelo.id)"
                                >
                                    {{ modelo.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.modelo_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="talla_id"><Ruler class="mr-1 inline-block h-3.5 w-3.5" />Talla</Label>
                        <Select v-model="form.talla_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar talla" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="talla in tallas"
                                    :key="talla.id"
                                    :value="String(talla.id)"
                                >
                                    {{ talla.nombre }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.talla_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="color_id"><Palette class="mr-1 inline-block h-3.5 w-3.5" />Color</Label>
                        <Select v-model="form.color_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar color" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="color in colores"
                                    :key="color.id"
                                    :value="String(color.id)"
                                >
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="inline-block h-4 w-4 rounded-full border shadow-sm"
                                            :style="{ backgroundColor: color.codigo_hex }"
                                        />
                                        {{ color.nombre }}
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.color_id" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 border-t px-6 py-6 md:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="nombre"><ShoppingBag class="mr-1 inline-block h-3.5 w-3.5" />Nombre de la Prenda *</Label>
                        <Input
                            id="nombre"
                            v-model="form.nombre"
                            placeholder="Ej: Jean Skinny Mujer, Polera Oversize"
                        />
                        <InputError :message="form.errors.nombre" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="precio_venta"><DollarSign class="mr-1 inline-block h-3.5 w-3.5" />Precio de Venta (Bs) *</Label>
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
                        <Label for="estado"><Package class="mr-1 inline-block h-3.5 w-3.5" />Estado *</Label>
                        <Select v-model="form.estado">
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="activo">Activo</SelectItem>
                                <SelectItem value="inactivo">Inactivo</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.estado" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 border-t px-6 py-6 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label><Image class="mr-1 inline-block h-3.5 w-3.5" />Imagen de la Prenda</Label>
                        <div class="flex items-center gap-3">
                            <label
                                class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-sm transition-colors hover:bg-muted"
                            >
                                <ImagePlus class="h-4 w-4" />
                                <span>Seleccionar archivo</span>
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                    class="hidden"
                                    @change="handleImageUpload"
                                />
                            </label>
                            <button
                                v-if="imagePreview"
                                type="button"
                                class="text-red-500 hover:text-red-600"
                                @click="removeImage"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <div
                            v-if="imagePreview"
                            class="mt-2 overflow-hidden rounded-lg border"
                        >
                            <img
                                :src="imagePreview"
                                alt="Preview"
                                class="h-24 w-24 object-cover"
                            />
                        </div>
                        <InputError :message="form.errors.imagen" />
                    </div>
                </div>

                <div class="border-t px-6 py-4">
                    <div class="grid gap-2">
                        <Label for="descripcion"><FileText class="mr-1 inline-block h-3.5 w-3.5" />Descripción de la Prenda</Label>
                        <textarea
                            id="descripcion"
                            v-model="form.descripcion"
                            class="flex min-h-[80px] w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Ej: Jean azul talla M, Polera blanca cuello redondo..."
                        />
                        <InputError :message="form.errors.descripcion" />
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border bg-glass">
                <div class="flex items-center justify-between border-b px-6 py-4">
                    <div>
                        <h3 class="text-sm font-semibold">Variantes del producto</h3>
                        <p class="text-muted-foreground text-xs">Combina colores y tallas con stock individual. Opcional.</p>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addVariante">
                        <Plus class="mr-1 h-3.5 w-3.5" />
                        Agregar variante
                    </Button>
                </div>

                <div v-if="form.variantes.length > 0" class="divide-y">
                    <div
                        v-for="(variante, index) in form.variantes"
                        :key="index"
                        class="grid grid-cols-1 gap-4 px-6 py-4 md:grid-cols-12"
                    >
                        <div class="grid gap-2 md:col-span-3">
                            <Label class="text-xs">Color *</Label>
                            <Select v-model="variante.color_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Color" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="color in colores"
                                        :key="color.id"
                                        :value="String(color.id)"
                                    >
                                        <span class="flex items-center gap-2">
                                            <span
                                                class="inline-block h-3 w-3 rounded-full border shadow-sm"
                                                :style="{ backgroundColor: color.codigo_hex }"
                                            />
                                            {{ color.nombre }}
                                        </span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="grid gap-2 md:col-span-3">
                            <Label class="text-xs">Talla *</Label>
                            <Select v-model="variante.talla_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Talla" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="talla in tallas"
                                        :key="talla.id"
                                        :value="String(talla.id)"
                                    >
                                        {{ talla.nombre }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label class="text-xs">Stock *</Label>
                            <Input
                                v-model.number="variante.stock"
                                type="number"
                                min="0"
                                placeholder="0"
                            />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label class="text-xs">Estado *</Label>
                            <Select v-model="variante.disponible">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="true">Disponible</SelectItem>
                                    <SelectItem :value="false">No disponible</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-end md:col-span-2">
                            <Button type="button" variant="destructive" size="icon-sm" @click="removeVariante(index)">
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                </div>

                <div v-else class="px-6 py-8 text-center">
                    <p class="text-muted-foreground text-xs">No hay variantes agregadas. Haz clic en "Agregar variante" para combinar colores y tallas.</p>
                </div>
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
                    Guardar Prenda
                </Button>
            </div>
        </form>
    </div>
</template>

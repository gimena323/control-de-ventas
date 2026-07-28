<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Box,
    FolderOpen,
    Package,
    Palette,
    Pencil,
    Plus,
    Ruler,
    Search,
    Tag,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import {
    index,
    storeCategoria,
    updateCategoria,
    destroyCategoria,
    storeMarca,
    updateMarca,
    destroyMarca,
    storeModelo,
    updateModelo,
    destroyModelo,
    storeTalla,
    updateTalla,
    destroyTalla,
    storeColor,
    updateColor,
    destroyColor,
    destroy as destroyProduct,
} from '@/actions/App/Http/Controllers/ProductosController';
import type { Categoria, Color, Marca, Modelo, PaginatedData, Producto, Talla } from '@/types';

const { can } = usePermissions();

type Props = {
    productos: PaginatedData<Producto>;
    categorias: Categoria[];
    marcas: Marca[];
    modelos: Modelo[];
    tallas: Talla[];
    colores: Color[];
    filters: { search?: string };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Productos',
                href: index(),
            },
        ],
    },
});

const activeTab = ref<'categorias' | 'marcas' | 'modelos' | 'tallas' | 'colores' | 'productos'>('productos');

const search = ref(props.filters.search ?? '');

watch(
    search,
    (value) => {
        const url = new URL(window.location.href);
        if (value) {
            url.searchParams.set('search', value);
        } else {
            url.searchParams.delete('search');
        }
        url.searchParams.delete('page');
        setTimeout(() => {
            window.location.href = url.toString();
        }, 400);
    },
);

const tabs = [
    { id: 'categorias' as const, label: 'Categorías', icon: FolderOpen },
    { id: 'marcas' as const, label: 'Marcas', icon: Tag },
    { id: 'modelos' as const, label: 'Modelos', icon: Box },
    { id: 'tallas' as const, label: 'Tallas', icon: Ruler },
    { id: 'colores' as const, label: 'Colores', icon: Palette },
    { id: 'productos' as const, label: 'Productos', icon: Package },
];

// Categoría CRUD
const catForm = useForm({ nombre: '' });
const catEditForm = useForm({ nombre: '' });
const editingCat = ref<Categoria | null>(null);
const catDialogOpen = ref(false);
const catDeleteDialogOpen = ref(false);
const catToDelete = ref<Categoria | null>(null);
const isEditingCat = computed(() => !!editingCat.value);
const catDialogTitle = computed(() => isEditingCat.value ? 'Editar Categoría' : 'Nueva Categoría');
const catFormActive = computed(() => isEditingCat.value ? catEditForm : catForm);

function openCreateCat() {
    editingCat.value = null;
    catForm.reset();
    catDialogOpen.value = true;
}

function openEditCat(cat: Categoria) {
    editingCat.value = cat;
    catEditForm.nombre = cat.nombre;
    catDialogOpen.value = true;
}

function submitCat() {
    if (isEditingCat.value && editingCat.value) {
        catEditForm.patch(updateCategoria(editingCat.value.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                catDialogOpen.value = false;
                editingCat.value = null;
                catEditForm.reset();
            },
        });
    } else {
        catForm.post(storeCategoria().url, {
            preserveScroll: true,
            onSuccess: () => {
                catDialogOpen.value = false;
                catForm.reset();
            },
        });
    }
}

function confirmDeleteCat(cat: Categoria) {
    catToDelete.value = cat;
    catDeleteDialogOpen.value = true;
}

function submitDeleteCat() {
    if (!catToDelete.value) return;
    const id = catToDelete.value.id;
    catDeleteDialogOpen.value = false;
    router.delete(destroyCategoria(id).url, {
        preserveScroll: true,
        onSuccess: () => {
            catToDelete.value = null;
        },
    });
}

// Marca CRUD
const marcaForm = useForm({ nombre: '' });
const marcaEditForm = useForm({ nombre: '' });
const editingMarca = ref<Marca | null>(null);
const marcaDialogOpen = ref(false);
const marcaDeleteDialogOpen = ref(false);
const marcaToDelete = ref<Marca | null>(null);
const isEditingMarca = computed(() => !!editingMarca.value);
const marcaDialogTitle = computed(() => isEditingMarca.value ? 'Editar Marca' : 'Nueva Marca');
const marcaFormActive = computed(() => isEditingMarca.value ? marcaEditForm : marcaForm);

function openCreateMarca() {
    editingMarca.value = null;
    marcaForm.reset();
    marcaDialogOpen.value = true;
}

function openEditMarca(marca: Marca) {
    editingMarca.value = marca;
    marcaEditForm.nombre = marca.nombre;
    marcaDialogOpen.value = true;
}

function submitMarca() {
    if (isEditingMarca.value && editingMarca.value) {
        marcaEditForm.patch(updateMarca(editingMarca.value.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                marcaDialogOpen.value = false;
                editingMarca.value = null;
                marcaEditForm.reset();
            },
        });
    } else {
        marcaForm.post(storeMarca().url, {
            preserveScroll: true,
            onSuccess: () => {
                marcaDialogOpen.value = false;
                marcaForm.reset();
            },
        });
    }
}

function confirmDeleteMarca(marca: Marca) {
    marcaToDelete.value = marca;
    marcaDeleteDialogOpen.value = true;
}

function submitDeleteMarca() {
    if (!marcaToDelete.value) return;
    const id = marcaToDelete.value.id;
    marcaDeleteDialogOpen.value = false;
    router.delete(destroyMarca(id).url, {
        preserveScroll: true,
        onSuccess: () => {
            marcaToDelete.value = null;
        },
    });
}

// Modelo CRUD
const modeloForm = useForm({ marca_id: '', nombre: '' });
const modeloEditForm = useForm({ marca_id: '', nombre: '' });
const editingModelo = ref<Modelo | null>(null);
const modeloDialogOpen = ref(false);
const modeloDeleteDialogOpen = ref(false);
const modeloToDelete = ref<Modelo | null>(null);
const isEditingModelo = computed(() => !!editingModelo.value);
const modeloDialogTitle = computed(() => isEditingModelo.value ? 'Editar Modelo de Prenda' : 'Nuevo Modelo de Prenda');
const modeloFormActive = computed(() => isEditingModelo.value ? modeloEditForm : modeloForm);

function openCreateModelo() {
    editingModelo.value = null;
    modeloForm.reset();
    modeloDialogOpen.value = true;
}

function openEditModelo(modelo: Modelo) {
    editingModelo.value = modelo;
    modeloEditForm.marca_id = String(modelo.marca_id);
    modeloEditForm.nombre = modelo.nombre;
    modeloDialogOpen.value = true;
}

function submitModelo() {
    if (isEditingModelo.value && editingModelo.value) {
        modeloEditForm.patch(updateModelo(editingModelo.value.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                modeloDialogOpen.value = false;
                editingModelo.value = null;
                modeloEditForm.reset();
            },
        });
    } else {
        modeloForm.post(storeModelo().url, {
            preserveScroll: true,
            onSuccess: () => {
                modeloDialogOpen.value = false;
                modeloForm.reset();
            },
        });
    }
}

const modeloMarcaNombre = (modelo: Modelo): string =>
    modelo.marca?.nombre ?? marcas.find((m) => String(m.id) === String(modelo.marca_id))?.nombre ?? '—';

function confirmDeleteModelo(modelo: Modelo) {
    modeloToDelete.value = modelo;
    modeloDeleteDialogOpen.value = true;
}

function submitDeleteModelo() {
    if (!modeloToDelete.value) return;
    const id = modeloToDelete.value.id;
    modeloDeleteDialogOpen.value = false;
    router.delete(destroyModelo(id).url, {
        preserveScroll: true,
        onSuccess: () => {
            modeloToDelete.value = null;
        },
    });
}

// Talla CRUD
const tallaForm = useForm({ nombre: '' });
const tallaEditForm = useForm({ nombre: '' });
const editingTalla = ref<Talla | null>(null);
const tallaDialogOpen = ref(false);
const tallaDeleteDialogOpen = ref(false);
const tallaToDelete = ref<Talla | null>(null);
const isEditingTalla = computed(() => !!editingTalla.value);
const tallaDialogTitle = computed(() => isEditingTalla.value ? 'Editar Talla' : 'Nueva Talla');
const tallaFormActive = computed(() => isEditingTalla.value ? tallaEditForm : tallaForm);

function openCreateTalla() {
    editingTalla.value = null;
    tallaForm.reset();
    tallaDialogOpen.value = true;
}

function openEditTalla(talla: Talla) {
    editingTalla.value = talla;
    tallaEditForm.nombre = talla.nombre;
    tallaDialogOpen.value = true;
}

function submitTalla() {
    if (isEditingTalla.value && editingTalla.value) {
        tallaEditForm.patch(updateTalla(editingTalla.value.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                tallaDialogOpen.value = false;
                editingTalla.value = null;
                tallaEditForm.reset();
            },
        });
    } else {
        tallaForm.post(storeTalla().url, {
            preserveScroll: true,
            onSuccess: () => {
                tallaDialogOpen.value = false;
                tallaForm.reset();
            },
        });
    }
}

function confirmDeleteTalla(talla: Talla) {
    tallaToDelete.value = talla;
    tallaDeleteDialogOpen.value = true;
}

function submitDeleteTalla() {
    if (!tallaToDelete.value) return;
    const id = tallaToDelete.value.id;
    tallaDeleteDialogOpen.value = false;
    router.delete(destroyTalla(id).url, {
        preserveScroll: true,
        onSuccess: () => {
            tallaToDelete.value = null;
        },
    });
}

// Color CRUD
const colorForm = useForm({ nombre: '', codigo_hex: '' });
const colorEditForm = useForm({ nombre: '', codigo_hex: '' });
const editingColor = ref<Color | null>(null);
const colorDialogOpen = ref(false);
const colorDeleteDialogOpen = ref(false);
const colorToDelete = ref<Color | null>(null);
const isEditingColor = computed(() => !!editingColor.value);
const colorDialogTitle = computed(() => isEditingColor.value ? 'Editar Color' : 'Nuevo Color');
const colorFormActive = computed(() => isEditingColor.value ? colorEditForm : colorForm);

function openCreateColor() {
    editingColor.value = null;
    colorForm.reset();
    colorDialogOpen.value = true;
}

function openEditColor(color: Color) {
    editingColor.value = color;
    colorEditForm.nombre = color.nombre;
    colorEditForm.codigo_hex = color.codigo_hex;
    colorDialogOpen.value = true;
}

function submitColor() {
    if (isEditingColor.value && editingColor.value) {
        colorEditForm.patch(updateColor(editingColor.value.id).url, {
            preserveScroll: true,
            onSuccess: () => {
                colorDialogOpen.value = false;
                editingColor.value = null;
                colorEditForm.reset();
            },
        });
    } else {
        colorForm.post(storeColor().url, {
            preserveScroll: true,
            onSuccess: () => {
                colorDialogOpen.value = false;
                colorForm.reset();
            },
        });
    }
}

function confirmDeleteColor(color: Color) {
    colorToDelete.value = color;
    colorDeleteDialogOpen.value = true;
}

function submitDeleteColor() {
    if (!colorToDelete.value) return;
    const id = colorToDelete.value.id;
    colorDeleteDialogOpen.value = false;
    router.delete(destroyColor(id).url, {
        preserveScroll: true,
        onSuccess: () => {
            colorToDelete.value = null;
        },
    });
}

// Product delete
const deleteDialogOpen = ref(false);
const productoToDelete = ref<Producto | null>(null);

function confirmDeleteProduct(producto: Producto) {
    productoToDelete.value = producto;
    deleteDialogOpen.value = true;
}

function submitDeleteProduct() {
    if (!productoToDelete.value) return;
    const id = productoToDelete.value.id;
    deleteDialogOpen.value = false;
    router.delete(destroyProduct(id).url, {
        preserveScroll: true,
        onSuccess: () => {
            productoToDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Gestión de Productos" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading
            title="Gestión de Productos"
            description="Administra categorías, marcas y modelos de prendas."
        />

        <div class="flex flex-wrap items-center gap-1 rounded-xl border bg-glass p-1">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                    'flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-200',
                    activeTab === tab.id
                        ? 'bg-gradient-to-r from-rose-500 to-pink-500 text-white shadow-md'
                        : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
                ]"
            >
                <component :is="tab.icon" class="h-4 w-4" />
                {{ tab.label }}
            </button>
        </div>

        <!-- Categorías Tab -->
        <div v-if="activeTab === 'categorias'" class="flex h-full flex-col gap-4">
            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    {{ categorias.length }} categoría(s) registrada(s)
                </p>
                <Button v-if="can('productos.crear')" @click="openCreateCat">
                    <Plus class="mr-2 h-4 w-4" />
                    Nueva Categoría
                </Button>
            </div>

            <div class="overflow-hidden rounded-xl border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-muted/50 border-b text-left">
                            <th class="px-4 py-3 font-medium">#</th>
                            <th class="px-4 py-3 font-medium">Nombre</th>
                            <th class="px-4 py-3 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(cat, index) in categorias"
                            :key="cat.id"
                            class="hover:bg-muted/30 border-b transition-colors last:border-0"
                        >
                            <td class="px-4 py-3 text-xs text-muted-foreground">{{ index + 1 }}</td>
                            <td class="px-4 py-3 font-medium">{{ cat.nombre }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="icon" class="h-8 w-8" @click="openEditCat(cat)">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-red-500 hover:text-red-600" @click="confirmDeleteCat(cat)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="categorias.length === 0">
                            <td colspan="3" class="py-12 text-center text-sm text-muted-foreground">
                                <FolderOpen class="mx-auto mb-2 h-8 w-8 opacity-40" />
                                No hay categorías registradas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Marcas Tab -->
        <div v-if="activeTab === 'marcas'" class="flex h-full flex-col gap-4">
            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    {{ marcas.length }} marca(s) registrada(s)
                </p>
                <Button v-if="can('productos.crear')" @click="openCreateMarca">
                    <Plus class="mr-2 h-4 w-4" />
                    Nueva Marca
                </Button>
            </div>

            <div class="overflow-hidden rounded-xl border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-muted/50 border-b text-left">
                            <th class="px-4 py-3 font-medium">#</th>
                            <th class="px-4 py-3 font-medium">Nombre</th>
                            <th class="px-4 py-3 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(marca, index) in marcas"
                            :key="marca.id"
                            class="hover:bg-muted/30 border-b transition-colors last:border-0"
                        >
                            <td class="px-4 py-3 text-xs text-muted-foreground">{{ index + 1 }}</td>
                            <td class="px-4 py-3 font-medium">{{ marca.nombre }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="icon" class="h-8 w-8" @click="openEditMarca(marca)">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-red-500 hover:text-red-600" @click="confirmDeleteMarca(marca)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="marcas.length === 0">
                            <td colspan="3" class="py-12 text-center text-sm text-muted-foreground">
                                <Tag class="mx-auto mb-2 h-8 w-8 opacity-40" />
                                No hay marcas registradas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modelos Tab -->
        <div v-if="activeTab === 'modelos'" class="flex h-full flex-col gap-4">
            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    {{ modelos.length }} modelo(s) registrado(s)
                </p>
                <Button v-if="can('productos.crear')" @click="openCreateModelo">
                    <Plus class="mr-2 h-4 w-4" />
                    Nuevo Modelo
                </Button>
            </div>

            <div class="overflow-hidden rounded-xl border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-muted/50 border-b text-left">
                            <th class="px-4 py-3 font-medium">#</th>
                            <th class="px-4 py-3 font-medium">Marca</th>
                            <th class="px-4 py-3 font-medium">Nombre</th>
                            <th class="px-4 py-3 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(modelo, index) in modelos"
                            :key="modelo.id"
                            class="hover:bg-muted/30 border-b transition-colors last:border-0"
                        >
                            <td class="px-4 py-3 text-xs text-muted-foreground">{{ index + 1 }}</td>
                            <td class="px-4 py-3 text-xs">{{ modeloMarcaNombre(modelo) }}</td>
                            <td class="px-4 py-3 font-medium">{{ modelo.nombre }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="icon" class="h-8 w-8" @click="openEditModelo(modelo)">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-red-500 hover:text-red-600" @click="confirmDeleteModelo(modelo)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="modelos.length === 0">
                            <td colspan="4" class="py-12 text-center text-sm text-muted-foreground">
                                <Box class="mx-auto mb-2 h-8 w-8 opacity-40" />
                                No hay modelos registrados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tallas Tab -->
        <div v-if="activeTab === 'tallas'" class="flex h-full flex-col gap-4">
            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    {{ tallas.length }} talla(s) registrada(s)
                </p>
                <Button v-if="can('productos.crear')" @click="openCreateTalla">
                    <Plus class="mr-2 h-4 w-4" />
                    Nueva Talla
                </Button>
            </div>

            <div class="overflow-hidden rounded-xl border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-muted/50 border-b text-left">
                            <th class="px-4 py-3 font-medium">#</th>
                            <th class="px-4 py-3 font-medium">Nombre</th>
                            <th class="px-4 py-3 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(talla, index) in tallas"
                            :key="talla.id"
                            class="hover:bg-muted/30 border-b transition-colors last:border-0"
                        >
                            <td class="px-4 py-3 text-xs text-muted-foreground">{{ index + 1 }}</td>
                            <td class="px-4 py-3 font-medium">{{ talla.nombre }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="icon" class="h-8 w-8" @click="openEditTalla(talla)">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-red-500 hover:text-red-600" @click="confirmDeleteTalla(talla)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="tallas.length === 0">
                            <td colspan="3" class="py-12 text-center text-sm text-muted-foreground">
                                <Ruler class="mx-auto mb-2 h-8 w-8 opacity-40" />
                                No hay tallas registradas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Colores Tab -->
        <div v-if="activeTab === 'colores'" class="flex h-full flex-col gap-4">
            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    {{ colores.length }} color(es) registrado(s)
                </p>
                <Button v-if="can('productos.crear')" @click="openCreateColor">
                    <Plus class="mr-2 h-4 w-4" />
                    Nuevo Color
                </Button>
            </div>

            <div class="overflow-hidden rounded-xl border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-muted/50 border-b text-left">
                            <th class="px-4 py-3 font-medium">#</th>
                            <th class="px-4 py-3 font-medium">Color</th>
                            <th class="px-4 py-3 font-medium">Nombre</th>
                            <th class="px-4 py-3 font-medium">Código</th>
                            <th class="px-4 py-3 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(color, index) in colores"
                            :key="color.id"
                            class="hover:bg-muted/30 border-b transition-colors last:border-0"
                        >
                            <td class="px-4 py-3 text-xs text-muted-foreground">{{ index + 1 }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-block h-5 w-5 rounded-full border shadow-sm"
                                    :style="{ backgroundColor: color.codigo_hex }"
                                />
                            </td>
                            <td class="px-4 py-3 font-medium">{{ color.nombre }}</td>
                            <td class="px-4 py-3 text-xs text-muted-foreground font-mono">{{ color.codigo_hex }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="icon" class="h-8 w-8" @click="openEditColor(color)">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-red-500 hover:text-red-600" @click="confirmDeleteColor(color)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="colores.length === 0">
                            <td colspan="5" class="py-12 text-center text-sm text-muted-foreground">
                                <Palette class="mx-auto mb-2 h-8 w-8 opacity-40" />
                                No hay colores registrados.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Productos Tab -->
        <div v-if="activeTab === 'productos'" class="flex h-full flex-col gap-4">
            <div class="flex items-center gap-4">
                <div class="relative max-w-sm flex-1">
                    <Search class="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                    <Input v-model="search" placeholder="Buscar por nombre de la prenda, categoría, marca o modelo..." class="pl-10" />
                </div>
                <Link :href="'/productos/crear'">
                    <Button class="bg-gradient-to-r from-rose-500 to-pink-500 text-white shadow-md hover:from-rose-600 hover:to-pink-600">
                        <Plus class="mr-2 h-4 w-4" />
                        Nueva Prenda
                    </Button>
                </Link>
            </div>

            <div class="overflow-hidden rounded-xl border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-muted/50 border-b text-left">
                            <th class="px-4 py-3 font-medium">Imagen</th>
                            <th class="px-4 py-3 font-medium">Nombre</th>
                            <th class="px-4 py-3 font-medium">Categoría</th>
                            <th class="px-4 py-3 font-medium">Marca</th>
                            <th class="px-4 py-3 font-medium">Modelo</th>
                            <th class="px-4 py-3 font-medium">Descripción</th>
                            <th class="px-4 py-3 font-medium">Precio</th>
                            <th class="px-4 py-3 font-medium">Stock</th>
                            <th class="px-4 py-3 font-medium">Estado</th>
                            <th class="px-4 py-3 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="producto in productos.data"
                            :key="producto.id"
                            class="hover:bg-muted/30 border-b transition-colors last:border-0"
                        >
                            <td class="px-4 py-3">
                                <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-lg bg-primary/10">
                                    <img
                                        v-if="producto.imagen"
                                        :src="'/storage/' + producto.imagen"
                                        :alt="producto.nombre"
                                        class="h-full w-full object-cover"
                                    />
                                    <Package v-else class="text-primary h-4 w-4" />
                                </div>
                            </td>
                            <td class="px-4 py-3 font-medium">{{ producto.nombre }}</td>
                            <td class="px-4 py-3">
                                <Badge variant="outline" class="text-xs">
                                    {{ producto.categoria?.nombre }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-xs">{{ producto.marca?.nombre }}</td>
                            <td class="px-4 py-3 text-xs">{{ producto.modelo?.nombre }}</td>
                            <td class="px-4 py-3">
                                <p v-if="producto.descripcion" class="line-clamp-1 max-w-[150px] text-xs text-muted-foreground">
                                    {{ producto.descripcion }}
                                </p>
                                <span v-else class="text-xs text-muted-foreground">—</span>
                            </td>
                            <td class="px-4 py-3 font-medium">Bs {{ parseFloat(producto.precio_venta).toFixed(2) }}</td>
                            <td class="px-4 py-3 text-xs">{{ producto.inventario?.stock_actual ?? 0 }}</td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="producto.estado === 'activo' ? 'default' : 'secondary'"
                                    class="text-xs"
                                >
                                    {{ producto.estado === 'activo' ? 'Activo' : 'Inactivo' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="'/productos/' + producto.id + '/editar'">
                                        <Button variant="ghost" size="icon" class="h-8 w-8">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-red-500 hover:text-red-600"
                                        @click="confirmDeleteProduct(producto)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="productos.data.length === 0">
                            <td colspan="10" class="py-16 text-center">
                                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                                    <Package class="h-8 w-8 text-muted-foreground opacity-40" />
                                </div>
                                <p class="mb-1 text-base font-medium">No hay prendas registradas</p>
                                <p class="mb-4 text-sm text-muted-foreground">
                                    Registra la primera prenda para comenzar a administrar el inventario de Emma Fashion.
                                </p>
                                <Link :href="'/productos/crear'">
                                    <Button>
                                        <Plus class="mr-2 h-4 w-4" />
                                        Registrar primera prenda
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="productos.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Mostrando {{ productos.from }}–{{ productos.to }} de {{ productos.total }} prendas
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        :href="productos.prev_page_url ?? '#'"
                        :class="['rounded-lg border px-3 py-1.5 text-sm transition-colors', productos.prev_page_url ? 'hover:bg-muted' : 'pointer-events-none opacity-40']"
                    >
                        Anterior
                    </Link>
                    <Link
                        :href="productos.next_page_url ?? '#'"
                        :class="['rounded-lg border px-3 py-1.5 text-sm transition-colors', productos.next_page_url ? 'hover:bg-muted' : 'pointer-events-none opacity-40']"
                    >
                        Siguiente
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <!-- Categoría Dialog -->
    <Dialog v-model:open="catDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ catDialogTitle }}</DialogTitle>
                <DialogDescription>
                    {{ isEditingCat ? 'Actualiza el nombre de la categoría.' : 'Agrega una nueva categoría de prendas (ej: Poleras, Jeans, Vestidos, Chaquetas).' }}
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitCat">
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="cat_nombre">Nombre de la Categoría *</Label>
                        <Input
                            id="cat_nombre"
                            v-model="catFormActive.nombre"
                            placeholder="Ej: Poleras, Jeans, Vestidos, Chaquetas"
                        />
                        <InputError :message="catFormActive.errors.nombre" />
                    </div>
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="catFormActive.processing">
                        {{ isEditingCat ? 'Guardar Cambios' : 'Crear Categoría' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="catDeleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Eliminar Categoría</DialogTitle>
                <DialogDescription>
                    ¿Estás seguro de eliminar <strong>{{ catToDelete?.nombre }}</strong>? Esta acción no se puede deshacer.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cancelar</Button>
                </DialogClose>
                <Button variant="destructive" @click="submitDeleteCat">Eliminar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Marca Dialog -->
    <Dialog v-model:open="marcaDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ marcaDialogTitle }}</DialogTitle>
                <DialogDescription>
                    {{ isEditingMarca ? 'Actualiza el nombre de la marca.' : 'Agrega una nueva marca de ropa (ej: Zara, Nike, Adidas, Levi\'s).' }}
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitMarca">
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="marca_nombre">Nombre de la Marca *</Label>
                        <Input
                            id="marca_nombre"
                            v-model="marcaFormActive.nombre"
                            placeholder="Ej: Zara, Nike, Adidas, Levi's"
                        />
                        <InputError :message="marcaFormActive.errors.nombre" />
                    </div>
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="marcaFormActive.processing">
                        {{ isEditingMarca ? 'Guardar Cambios' : 'Crear Marca' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="marcaDeleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Eliminar Marca</DialogTitle>
                <DialogDescription>
                    ¿Estás seguro de eliminar <strong>{{ marcaToDelete?.nombre }}</strong>? Esta acción no se puede deshacer.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cancelar</Button>
                </DialogClose>
                <Button variant="destructive" @click="submitDeleteMarca">Eliminar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Modelo Dialog -->
    <Dialog v-model:open="modeloDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ modeloDialogTitle }}</DialogTitle>
                <DialogDescription>
                    {{ isEditingModelo ? 'Actualiza el nombre y la marca del modelo de prenda.' : 'Agrega un nuevo modelo de ropa (ej: Jean Skinny Mujer, Polera Oversize, Vestido Floral).' }}
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitModelo">
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="modelo_marca_id">Marca *</Label>
                        <Select v-model="modeloFormActive.marca_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona la marca de la prenda" />
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
                        <InputError :message="modeloFormActive.errors.marca_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="modelo_nombre">Nombre del Modelo *</Label>
                        <Input
                            id="modelo_nombre"
                            v-model="modeloFormActive.nombre"
                            placeholder="Ej: Jean Skinny Mujer, Polera Oversize, Vestido Floral"
                        />
                        <InputError :message="modeloFormActive.errors.nombre" />
                    </div>
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="modeloFormActive.processing">
                        {{ isEditingModelo ? 'Guardar Cambios' : 'Crear Modelo' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="modeloDeleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Eliminar Modelo de Prenda</DialogTitle>
                <DialogDescription>
                    ¿Estás seguro de eliminar <strong>{{ modeloToDelete?.nombre }}</strong>? Esta acción no se puede deshacer.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cancelar</Button>
                </DialogClose>
                <Button variant="destructive" @click="submitDeleteModelo">Eliminar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Talla Dialog -->
    <Dialog v-model:open="tallaDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ tallaDialogTitle }}</DialogTitle>
                <DialogDescription>
                    {{ isEditingTalla ? 'Actualiza el nombre de la talla.' : 'Ingresa el nombre de la nueva talla.' }}
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitTalla">
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="talla_nombre">Nombre *</Label>
                        <Input
                            id="talla_nombre"
                            v-model="tallaFormActive.nombre"
                            placeholder="Nombre de la talla"
                        />
                        <InputError :message="tallaFormActive.errors.nombre" />
                    </div>
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="tallaFormActive.processing">
                        {{ isEditingTalla ? 'Guardar Cambios' : 'Crear Talla' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="tallaDeleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Eliminar Talla</DialogTitle>
                <DialogDescription>
                    ¿Estás seguro de eliminar <strong>{{ tallaToDelete?.nombre }}</strong>? Esta acción no se puede deshacer.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cancelar</Button>
                </DialogClose>
                <Button variant="destructive" @click="submitDeleteTalla">Eliminar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Color Dialog -->
    <Dialog v-model:open="colorDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ colorDialogTitle }}</DialogTitle>
                <DialogDescription>
                    {{ isEditingColor ? 'Actualiza el nombre y código del color.' : 'Ingresa el nombre y código hexadecimal del nuevo color.' }}
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitColor">
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="color_nombre">Nombre *</Label>
                        <Input
                            id="color_nombre"
                            v-model="colorFormActive.nombre"
                            placeholder="Nombre del color"
                        />
                        <InputError :message="colorFormActive.errors.nombre" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="color_codigo">Código Hexadecimal *</Label>
                        <div class="flex items-center gap-3">
                            <Input
                                id="color_codigo"
                                v-model="colorFormActive.codigo_hex"
                                placeholder="#FF0000"
                                class="flex-1 font-mono"
                            />
                            <span
                                class="inline-block h-8 w-8 flex-shrink-0 rounded-full border shadow-sm"
                                :style="{ backgroundColor: colorFormActive.codigo_hex }"
                            />
                        </div>
                        <InputError :message="colorFormActive.errors.codigo_hex" />
                    </div>
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="colorFormActive.processing">
                        {{ isEditingColor ? 'Guardar Cambios' : 'Crear Color' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="colorDeleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Eliminar Color</DialogTitle>
                <DialogDescription>
                    ¿Estás seguro de eliminar <strong>{{ colorToDelete?.nombre }}</strong>? Esta acción no se puede deshacer.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cancelar</Button>
                </DialogClose>
                <Button variant="destructive" @click="submitDeleteColor">Eliminar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Product delete dialog -->
    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Eliminar Prenda</DialogTitle>
                <DialogDescription>
                    ¿Estás seguro de eliminar <strong>{{ productoToDelete?.nombre }}</strong>? Esta acción no se puede deshacer.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cancelar</Button>
                </DialogClose>
                <Button variant="destructive" @click="submitDeleteProduct">Eliminar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

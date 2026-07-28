<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Search, Trash2, Truck } from 'lucide-vue-next';
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
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { usePermissions } from '@/composables/usePermissions';
import { index, store, update, destroy, show } from '@/routes/proveedores';
import type { PaginatedData, Proveedor } from '@/types';

const { can } = usePermissions();

type Props = {
    proveedores: PaginatedData<Proveedor>;
    filters: { search?: string };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Proveedores',
                href: index(),
            },
        ],
    },
});

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

function clearSearch() {
    search.value = '';
}

const createForm = useForm({
    nombre: '',
    ci_nit: '',
    telefono: '',
    direccion: '',
    email: '',
});

const editForm = useForm({
    nombre: '',
    ci_nit: '',
    telefono: '',
    direccion: '',
    email: '',
});

const editingProveedor = ref<Proveedor | null>(null);
const formDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const proveedorToDelete = ref<Proveedor | null>(null);

const isEditing = computed(() => !!editingProveedor.value);
const dialogTitle = computed(() => (isEditing.value ? 'Editar Proveedor' : 'Nuevo Proveedor'));
const dialogDescription = computed(() =>
    isEditing.value
        ? 'Actualiza la información del proveedor seleccionado.'
        : 'Agrega un nuevo proveedor al sistema.',
);

const form = computed(() => (isEditing.value ? editForm : createForm));

function openCreateDialog() {
    editingProveedor.value = null;
    createForm.reset();
    formDialogOpen.value = true;
}

function openShowPage(proveedor: Proveedor) {
    router.visit(show(proveedor.id).url);
}

function openEditDialog(proveedor: Proveedor) {
    editingProveedor.value = proveedor;
    editForm.nombre = proveedor.nombre;
    editForm.ci_nit = proveedor.ci_nit;
    editForm.telefono = proveedor.telefono ?? '';
    editForm.direccion = proveedor.direccion ?? '';
    editForm.email = proveedor.email ?? '';
    formDialogOpen.value = true;
}

function submitCreate() {
    createForm.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            formDialogOpen.value = false;
            createForm.reset();
        },
    });
}

function submitUpdate() {
    if (!editingProveedor.value) return;
    editForm.patch(update(editingProveedor.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            formDialogOpen.value = false;
            editingProveedor.value = null;
            editForm.reset();
        },
    });
}

function confirmDelete(proveedor: Proveedor) {
    proveedorToDelete.value = proveedor;
    deleteDialogOpen.value = true;
}

function submitDelete() {
    if (!proveedorToDelete.value) return;
    const id = proveedorToDelete.value.id;
    deleteDialogOpen.value = false;
    router.delete(destroy(id).url, {
        preserveScroll: true,
        onSuccess: () => {
            proveedorToDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Proveedores" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <Heading title="Gestión de Proveedores" description="Administra los proveedores del sistema." />
            <Button v-if="can('proveedores.gestion')" @click="openCreateDialog">
                <Plus class="mr-2 h-4 w-4" />
                Nuevo Proveedor
            </Button>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative max-w-sm flex-1">
                <Search class="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                <Input v-model="search" placeholder="Buscar proveedores por nombre, CI/NIT o teléfono..." class="pl-10" />
            </div>
            <Button variant="outline" @click="clearSearch">
                Limpiar
            </Button>
        </div>

        <div class="overflow-hidden rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-muted/50 border-b text-left">
                        <th class="px-4 py-3 font-medium">Proveedor</th>
                        <th class="px-4 py-3 font-medium">CI/NIT</th>
                        <th class="px-4 py-3 font-medium">Teléfono</th>
                        <th class="px-4 py-3 font-medium">Compras</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="proveedor in proveedores.data"
                        :key="proveedor.id"
                        class="hover:bg-muted/30 border-b transition-colors last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
                                    <Truck class="text-primary h-4 w-4" />
                                </div>
                                <div>
                                    <p class="font-medium">{{ proveedor.nombre }}</p>
                                    <p v-if="proveedor.email" class="text-muted-foreground text-xs">{{ proveedor.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs">{{ proveedor.ci_nit }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="proveedor.telefono" class="text-xs">{{ proveedor.telefono }}</span>
                            <span v-else class="text-muted-foreground text-xs">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <Badge variant="secondary">{{ proveedor.compras_count }}</Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="icon-sm" class="border-primary/20 text-primary hover:bg-primary/10" @click="openShowPage(proveedor)">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Ver proveedor</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider v-if="can('proveedores.gestion')" :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="icon-sm" class="border-primary/20 text-primary hover:bg-primary/10" @click="openEditDialog(proveedor)">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Editar proveedor</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider v-if="can('proveedores.gestion')" :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="icon-sm" class="border-red-200 text-red-500 hover:bg-red-50" @click="confirmDelete(proveedor)">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Eliminar proveedor</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="proveedores.data.length === 0">
                        <td colspan="5" class="py-12 text-center text-sm text-muted-foreground">
                            <Truck class="mx-auto mb-2 h-8 w-8 opacity-40" />
                            No se encontraron proveedores.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="proveedores.last_page > 1" class="flex items-center justify-between">
            <p class="text-muted-foreground text-sm">
                Mostrando {{ proveedores.from }}–{{ proveedores.to }} de {{ proveedores.total }} proveedores
            </p>
            <div class="flex items-center gap-2">
                <Link
                    :href="proveedores.prev_page_url ?? '#'"
                    :class="['rounded-lg border px-3 py-1.5 text-sm transition-colors', proveedores.prev_page_url ? 'hover:bg-muted' : 'pointer-events-none opacity-40']"
                >
                    Anterior
                </Link>
                <Link
                    :href="proveedores.next_page_url ?? '#'"
                    :class="['rounded-lg border px-3 py-1.5 text-sm transition-colors', proveedores.next_page_url ? 'hover:bg-muted' : 'pointer-events-none opacity-40']"
                >
                    Siguiente
                </Link>
            </div>
        </div>
    </div>

    <Dialog v-model:open="formDialogOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ dialogTitle }}</DialogTitle>
                <DialogDescription>{{ dialogDescription }}</DialogDescription>
            </DialogHeader>
            <form @submit.prevent="isEditing ? submitUpdate() : submitCreate()">
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="nombre">Nombre del Proveedor *</Label>
                        <div class="relative">
                            <Input
                                id="nombre"
                                v-model="form.nombre"
                                placeholder="Nombre completo del proveedor (máximo 60 caracteres)"
                                maxlength="60"
                            />
                            <span class="text-muted-foreground absolute right-3 top-1/2 -translate-y-1/2 text-xs">
                                {{ form.nombre.length }}/60
                            </span>
                        </div>
                        <InputError :message="form.errors.nombre" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="ci_nit">CI/NIT *</Label>
                        <div class="relative">
                            <Input
                                id="ci_nit"
                                v-model="form.ci_nit"
                                placeholder="CI o NIT del proveedor"
                                maxlength="20"
                            />
                            <span class="text-muted-foreground absolute right-3 top-1/2 -translate-y-1/2 text-xs">
                                {{ form.ci_nit.length }}/20
                            </span>
                        </div>
                        <InputError :message="form.errors.ci_nit" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="telefono">Teléfono *</Label>
                        <div class="relative">
                            <Input
                                id="telefono"
                                v-model="form.telefono"
                                placeholder="Número de teléfono"
                                maxlength="20"
                            />
                            <span class="text-muted-foreground absolute right-3 top-1/2 -translate-y-1/2 text-xs">
                                {{ form.telefono.length }}/20
                            </span>
                        </div>
                        <InputError :message="form.errors.telefono" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            v-model="form.email"
                            placeholder="proveedor@email.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="direccion">Dirección</Label>
                        <Input
                            id="direccion"
                            v-model="form.direccion"
                            placeholder="Av. Principal 123"
                        />
                        <InputError :message="form.errors.direccion" />
                    </div>
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEditing ? 'Actualizar Proveedor' : 'Guardar Proveedor' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Eliminar Proveedor</DialogTitle>
                <DialogDescription>
                    ¿Está seguro de eliminar este proveedor? Esta acción no se puede deshacer.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Cancelar</Button>
                </DialogClose>
                <Button variant="destructive" @click="submitDelete">
                    Eliminar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Search, Trash2, User } from 'lucide-vue-next';
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
import { index, store, update, destroy, show } from '@/routes/clientes';
import type { Cliente, PaginatedData } from '@/types';

const { can } = usePermissions();

type Props = {
    clientes: PaginatedData<Cliente>;
    filters: { search?: string };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Clientes',
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
    apellido: '',
    ci_nit: '',
    telefono: '',
    direccion: '',
    email: '',
});

const editForm = useForm({
    nombre: '',
    apellido: '',
    ci_nit: '',
    telefono: '',
    direccion: '',
    email: '',
});

const editingCliente = ref<Cliente | null>(null);
const formDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const clienteToDelete = ref<Cliente | null>(null);

const isEditing = computed(() => !!editingCliente.value);
const dialogTitle = computed(() => (isEditing.value ? 'Editar Cliente' : 'Nuevo Cliente'));
const dialogDescription = computed(() =>
    isEditing.value
        ? 'Actualiza la información del cliente seleccionado.'
        : 'Agrega un nuevo cliente al sistema.',
);

const form = computed(() => (isEditing.value ? editForm : createForm));

function openCreateDialog() {
    editingCliente.value = null;
    createForm.reset();
    formDialogOpen.value = true;
}

function openShowPage(cliente: Cliente) {
    router.visit(show(cliente.id).url);
}

function openEditDialog(cliente: Cliente) {
    editingCliente.value = cliente;
    editForm.nombre = cliente.nombre;
    editForm.apellido = cliente.apellido ?? '';
    editForm.ci_nit = cliente.ci_nit;
    editForm.telefono = cliente.telefono ?? '';
    editForm.direccion = cliente.direccion ?? '';
    editForm.email = cliente.email ?? '';
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
    if (!editingCliente.value) return;
    editForm.patch(update(editingCliente.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            formDialogOpen.value = false;
            editingCliente.value = null;
            editForm.reset();
        },
    });
}

function confirmDelete(cliente: Cliente) {
    clienteToDelete.value = cliente;
    deleteDialogOpen.value = true;
}

function submitDelete() {
    if (!clienteToDelete.value) return;
    const id = clienteToDelete.value.id;
    deleteDialogOpen.value = false;
    router.delete(destroy(id).url, {
        preserveScroll: true,
        onSuccess: () => {
            clienteToDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Clientes" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <Heading title="Gestión de Clientes" description="Administra los clientes registrados en el sistema." />
            <Button v-if="can('clientes.gestion')" @click="openCreateDialog">
                <Plus class="mr-2 h-4 w-4" />
                Nuevo Cliente
            </Button>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative max-w-sm flex-1">
                <Search class="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                <Input v-model="search" placeholder="Buscar por nombre, apellido, CI/NIT o teléfono..." class="pl-10" />
            </div>
            <Button variant="outline" @click="clearSearch">
                Limpiar
            </Button>
        </div>
        <p class="text-muted-foreground -mt-4 text-xs">Busca simultáneamente por nombre, apellido, CI/NIT y teléfono.</p>

        <div class="overflow-hidden rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-muted/50 border-b text-left">
                        <th class="px-4 py-3 font-medium">Cliente</th>
                        <th class="px-4 py-3 font-medium">CI/NIT</th>
                        <th class="px-4 py-3 font-medium">Teléfono</th>
                        <th class="px-4 py-3 font-medium">Ventas</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="cliente in clientes.data"
                        :key="cliente.id"
                        class="hover:bg-muted/30 border-b transition-colors last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
                                    <User class="text-primary h-4 w-4" />
                                </div>
                                <div>
                                    <p class="font-medium">{{ cliente.nombre }}{{ cliente.apellido ? ' ' + cliente.apellido : '' }}</p>
                                    <p v-if="cliente.email" class="text-muted-foreground text-xs">{{ cliente.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs">{{ cliente.ci_nit }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="cliente.telefono" class="text-xs">{{ cliente.telefono }}</span>
                            <span v-else class="text-muted-foreground text-xs">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <Badge variant="secondary">{{ cliente.ventas_count }}</Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="icon-sm" class="border-primary/20 text-primary hover:bg-primary/10" @click="openShowPage(cliente)">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Ver cliente</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider v-if="can('clientes.gestion')" :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="icon-sm" class="border-primary/20 text-primary hover:bg-primary/10" @click="openEditDialog(cliente)">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Editar cliente</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider v-if="can('clientes.gestion')" :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button variant="outline" size="icon-sm" class="border-red-200 text-red-500 hover:bg-red-50" @click="confirmDelete(cliente)">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Eliminar cliente</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="clientes.data.length === 0">
                        <td colspan="5" class="py-12 text-center text-sm text-muted-foreground">
                            <User class="mx-auto mb-2 h-8 w-8 opacity-40" />
                            No se encontraron clientes.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="clientes.last_page > 1" class="flex items-center justify-between">
            <p class="text-muted-foreground text-sm">
                Mostrando {{ clientes.from }}–{{ clientes.to }} de {{ clientes.total }} clientes
            </p>
            <div class="flex items-center gap-2">
                <Link
                    :href="clientes.prev_page_url ?? '#'"
                    :class="['rounded-lg border px-3 py-1.5 text-sm transition-colors', clientes.prev_page_url ? 'hover:bg-muted' : 'pointer-events-none opacity-40']"
                >
                    Anterior
                </Link>
                <Link
                    :href="clientes.next_page_url ?? '#'"
                    :class="['rounded-lg border px-3 py-1.5 text-sm transition-colors', clientes.next_page_url ? 'hover:bg-muted' : 'pointer-events-none opacity-40']"
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
                        <Label for="nombre">Nombre *</Label>
                        <div class="relative">
                            <Input
                                id="nombre"
                                v-model="form.nombre"
                                placeholder="Nombre del cliente"
                                maxlength="60"
                            />
                            <span class="text-muted-foreground absolute right-3 top-1/2 -translate-y-1/2 text-xs">
                                {{ form.nombre.length }}/60
                            </span>
                        </div>
                        <InputError :message="form.errors.nombre" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="apellido">Apellido</Label>
                        <div class="relative">
                            <Input
                                id="apellido"
                                v-model="form.apellido"
                                placeholder="Apellido del cliente"
                                maxlength="100"
                            />
                            <span class="text-muted-foreground absolute right-3 top-1/2 -translate-y-1/2 text-xs">
                                {{ form.apellido.length }}/100
                            </span>
                        </div>
                        <InputError :message="form.errors.apellido" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="ci_nit">CI/NIT *</Label>
                        <div class="relative">
                            <Input
                                id="ci_nit"
                                v-model="form.ci_nit"
                                placeholder="CI o NIT del cliente"
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
                            placeholder="cliente@email.com"
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
                        {{ isEditing ? 'Actualizar Cliente' : 'Guardar Cliente' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Eliminar Cliente</DialogTitle>
                <DialogDescription>
                    ¿Está seguro de eliminar este cliente? Esta acción no se puede deshacer.
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

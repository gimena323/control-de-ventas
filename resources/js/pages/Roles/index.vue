<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Pencil,
    Plus,
    Shield,
    ShieldCheck,
    ShieldPlus,
    Trash2,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import ManagementHero from '@/components/ManagementHero.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { usePermissions } from '@/composables/usePermissions';
import type { PermissionItem, RoleItem } from '@/types/roles';

const { can } = usePermissions();

type Props = {
    roles: RoleItem[];
    permisos: PermissionItem[];
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Roles',
                href: '/roles',
            },
        ],
    },
});

const totalRoles = computed(() => props.roles.length);

const formDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const editingRole = ref<RoleItem | null>(null);

const groupedPermisos = computed(() => {
    const groups: Record<string, PermissionItem[]> = {};

    for (const p of props.permisos) {
        const group = p.grupo ?? 'general';
        if (!groups[group]) {
            groups[group] = [];
        }
        groups[group].push(p);
    }

    return groups;
});

const createForm = useForm({
    nombre: '',
    descripcion: '',
});

const form = useForm({
    nombre: '',
    descripcion: '',
    permisos: [] as string[],
});

const isEditing = computed(() => !!editingRole.value);
const dialogTitle = computed(() =>
    isEditing.value ? 'Editar Rol' : 'Nuevo Rol',
);
const dialogDescription = computed(() =>
    isEditing.value
        ? 'Actualiza el nombre, descripción y permisos del rol.'
        : 'Crea un nuevo rol con permisos específicos para la tienda.',
);

function submitCreate() {
    createForm.post('/roles', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
        },
    });
}

function openEditDialog(role: RoleItem) {
    editingRole.value = role;
    form.nombre = role.nombre;
    form.descripcion = role.descripcion ?? '';
    form.permisos = role.permissions.map((p) => p.nombre);
    formDialogOpen.value = true;
}

function togglePermiso(permiso: string) {
    const idx = form.permisos.indexOf(permiso);
    if (idx === -1) {
        form.permisos.push(permiso);
    } else {
        form.permisos.splice(idx, 1);
    }
}

function submitSave() {
    if (isEditing.value && editingRole.value) {
        form.put(`/roles/${editingRole.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                formDialogOpen.value = false;
                editingRole.value = null;
            },
        });
    } else {
        form.post('/roles', {
            preserveScroll: true,
            onSuccess: () => {
                formDialogOpen.value = false;
                form.reset();
                form.permisos = [];
            },
        });
    }
}

const roleToDelete = ref<RoleItem | null>(null);

function confirmDelete(role: RoleItem) {
    roleToDelete.value = role;
    deleteDialogOpen.value = true;
}

function submitDelete() {
    if (!roleToDelete.value) return;
    const id = roleToDelete.value.id;
    deleteDialogOpen.value = false;
    router.delete(`/roles/${id}`, {
        preserveScroll: true,
        onSuccess: () => {
            roleToDelete.value = null;
        },
    });
}

const grupoLabels: Record<string, string> = {
    panel: 'Panel',
    productos: 'Productos',
    ventas: 'Ventas',
    compras: 'Compras',
    inventario: 'Inventario',
    clientes: 'Clientes',
    proveedores: 'Proveedores',
    facturas: 'Facturas',
    usuarios: 'Usuarios',
    configuraciones: 'Configuraciones',
};

const grupoIcons: Record<string, string> = {
    panel: '📊',
    productos: '👗',
    ventas: '🛍️',
    compras: '📦',
    inventario: '📋',
    clientes: '👤',
    proveedores: '🚚',
    facturas: '🧾',
    usuarios: '🔐',
    configuraciones: '⚙️',
};

const roleBadgeColors: Record<string, string> = {
    admin: 'bg-rose-100 text-rose-700 border-rose-200',
    vendedor: 'bg-sky-100 text-sky-700 border-sky-200',
    encargado_inventario: 'bg-amber-100 text-amber-700 border-amber-200',
};

function getBadgeStyle(nombre: string): string {
    return (
        roleBadgeColors[nombre] ?? 'bg-pink-100 text-pink-700 border-pink-200'
    );
}
</script>

<template>
    <Head title="Gestión de Roles" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-gradient-to-br from-violet-100 via-fuchsia-50 to-pink-100 p-6"
    >
        <ManagementHero
            title="Roles y Permisos"
            description="Administra los roles del sistema y asigna permisos específicos para cada área de la tienda."
        >
            <template #icon><ShieldCheck class="h-7 w-7" /></template>
            <div
                class="flex shrink-0 items-center gap-3 rounded-xl border border-fuchsia-200 bg-gradient-to-br from-pink-100/95 to-violet-100/95 px-5 py-4 shadow-sm"
            >
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100"
                >
                    <ShieldCheck class="h-6 w-6 text-purple-600" />
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold tracking-tight text-gray-800">
                        {{ totalRoles }}
                    </p>
                    <p class="text-xs font-medium text-gray-500">
                        Total de Roles
                    </p>
                </div>
            </div>
        </ManagementHero>

        <div
            class="rounded-xl border border-fuchsia-200 bg-gradient-to-br from-pink-100/95 via-fuchsia-50 to-violet-100/95 p-6 shadow-sm transition-all duration-300 hover:shadow-md"
        >
            <div class="mb-5 flex items-center gap-2">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-100"
                >
                    <ShieldPlus class="h-5 w-5 text-rose-600" />
                </div>
                <h3 class="text-lg font-semibold text-gray-800">
                    Crear nuevo Rol
                </h3>
            </div>

            <form
                @submit.prevent="submitCreate"
                class="flex flex-col gap-4 sm:flex-row sm:items-end"
            >
                <div class="flex-1">
                    <Label
                        for="create-nombre"
                        class="mb-1.5 block text-sm font-medium text-gray-700"
                        >Nombre del Rol</Label
                    >
                    <Input
                        id="create-nombre"
                        v-model="createForm.nombre"
                        placeholder="ej: admin, vendedor, inventario"
                        class="border-pink-200/50 focus:border-rose-300 focus:ring-rose-200"
                    />
                    <InputError :message="createForm.errors.nombre" />
                </div>
                <div class="flex-1">
                    <Label
                        for="create-descripcion"
                        class="mb-1.5 block text-sm font-medium text-gray-700"
                        >Descripción del Rol</Label
                    >
                    <Input
                        id="create-descripcion"
                        v-model="createForm.descripcion"
                        placeholder="Breve descripción del rol en la tienda"
                        class="border-pink-200/50 focus:border-rose-300 focus:ring-rose-200"
                    />
                    <InputError :message="createForm.errors.descripcion" />
                </div>
                <Button
                    type="submit"
                    :disabled="createForm.processing"
                    class="h-10 shrink-0 bg-gradient-to-r from-rose-500 to-pink-500 text-white shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
                >
                    <Plus class="mr-1.5 h-4 w-4" />
                    Crear Rol
                </Button>
            </form>
        </div>

        <div
            class="rounded-xl border border-fuchsia-200 bg-gradient-to-br from-pink-100/95 via-fuchsia-50 to-violet-100/95 p-6 shadow-sm transition-all duration-300 hover:shadow-md"
        >
            <div class="mb-5 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100"
                    >
                        <Shield class="h-5 w-5 text-purple-600" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">
                        Lista de Roles
                    </h3>
                </div>
                <Badge
                    class="border-purple-200 bg-purple-100/80 px-3 py-1 text-xs font-semibold text-purple-700"
                >
                    {{ totalRoles }}
                    {{ totalRoles === 1 ? 'registrado' : 'registrados' }}
                </Badge>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-pink-100">
                            <th
                                class="px-4 py-3.5 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >
                                Nº
                            </th>
                            <th
                                class="px-4 py-3.5 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >
                                Nombre del Rol
                            </th>
                            <th
                                class="px-4 py-3.5 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >
                                Descripción
                            </th>
                            <th
                                class="px-4 py-3.5 text-left text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >
                                Usuarios
                            </th>
                            <th
                                class="px-4 py-3.5 text-right text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(role, index) in roles"
                            :key="role.id"
                            class="border-b border-pink-50 transition-colors duration-150 hover:bg-pink-50/40"
                        >
                            <td
                                class="px-4 py-4 text-sm font-medium whitespace-nowrap text-gray-400"
                            >
                                {{ index + 1 }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-white shadow-sm"
                                        :class="'text-rose-500'"
                                    >
                                        <ShieldCheck class="h-4 w-4" />
                                    </div>
                                    <span
                                        class="text-sm font-semibold text-gray-800 capitalize"
                                    >
                                        {{ role.nombre.replace(/_/g, ' ') }}
                                    </span>
                                    <span
                                        class="rounded-full border px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase"
                                        :class="getBadgeStyle(role.nombre)"
                                    >
                                        {{ role.nombre.replace(/_/g, ' ') }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">
                                {{ role.descripcion || '—' }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div
                                    class="flex items-center gap-1.5 text-sm text-gray-500"
                                >
                                    <Users class="h-4 w-4 text-gray-400" />
                                    <span
                                        >{{ role.users_count ?? 0 }}
                                        {{
                                            (role.users_count ?? 0) === 1
                                                ? 'usuario'
                                                : 'usuarios'
                                        }}</span
                                    >
                                </div>
                            </td>
                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <Button
                                        v-if="can('usuarios.gestion')"
                                        variant="ghost"
                                        size="icon"
                                        class="h-9 w-9 rounded-lg text-gray-400 transition-all duration-200 hover:bg-rose-50 hover:text-rose-600"
                                        @click="openEditDialog(role)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        v-if="can('usuarios.gestion')"
                                        variant="ghost"
                                        size="icon"
                                        class="h-9 w-9 rounded-lg text-gray-400 transition-all duration-200 hover:bg-red-50 hover:text-red-500"
                                        @click="confirmDelete(role)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="roles.length === 0">
                            <td colspan="5" class="px-4 py-12 text-center">
                                <div
                                    class="flex flex-col items-center justify-center"
                                >
                                    <div
                                        class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-purple-100"
                                    >
                                        <Shield
                                            class="h-8 w-8 text-purple-400"
                                        />
                                    </div>
                                    <p
                                        class="text-sm font-medium text-gray-600"
                                    >
                                        No hay roles registrados
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        Crea el primer rol usando el formulario
                                        de arriba
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <Dialog v-model:open="formDialogOpen">
        <DialogContent
            class="flex max-h-[90vh] flex-col sm:max-w-3xl lg:max-w-4xl"
        >
            <DialogHeader class="shrink-0">
                <DialogTitle class="text-xl">
                    <Shield class="mr-2 inline-block h-5 w-5 text-rose-400" />
                    {{ dialogTitle }}
                </DialogTitle>
                <DialogDescription>{{ dialogDescription }}</DialogDescription>
            </DialogHeader>
            <form
                @submit.prevent="submitSave"
                class="flex flex-col overflow-hidden"
            >
                <div class="-mx-6 flex-1 overflow-y-auto px-6 py-4">
                    <div class="grid gap-5">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label
                                    for="nombre"
                                    class="text-sm font-medium text-gray-700"
                                    >Nombre del rol *</Label
                                >
                                <Input
                                    id="nombre"
                                    v-model="form.nombre"
                                    placeholder="ej: admin, vendedor, inventario"
                                    class="border-pink-200/50 focus:border-rose-300 focus:ring-rose-200"
                                />
                                <InputError :message="form.errors.nombre" />
                            </div>
                            <div class="grid gap-2">
                                <Label
                                    for="descripcion"
                                    class="text-sm font-medium text-gray-700"
                                    >Descripción</Label
                                >
                                <Input
                                    id="descripcion"
                                    v-model="form.descripcion"
                                    placeholder="Breve descripción del rol en la tienda"
                                    class="border-pink-200/50 focus:border-rose-300 focus:ring-rose-200"
                                />
                                <InputError
                                    :message="form.errors.descripcion"
                                />
                            </div>
                        </div>

                        <div class="grid gap-3">
                            <Label class="text-sm font-medium text-gray-700"
                                >Permisos</Label
                            >
                            <div
                                class="max-h-[40vh] overflow-y-auto rounded-xl border border-pink-100 bg-pink-50/30 p-5"
                            >
                                <div
                                    v-for="(perms, group) in groupedPermisos"
                                    :key="group"
                                    class="mb-5 last:mb-0"
                                >
                                    <div class="mb-3 flex items-center gap-2">
                                        <span class="text-sm">{{
                                            grupoIcons[group] ?? '📌'
                                        }}</span>
                                        <h4
                                            class="text-xs font-bold tracking-wider text-gray-500 uppercase"
                                        >
                                            {{ grupoLabels[group] ?? group }}
                                        </h4>
                                        <div
                                            class="flex-1 border-t border-pink-100"
                                        />
                                    </div>
                                    <div
                                        class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                                    >
                                        <label
                                            v-for="permiso in perms"
                                            :key="permiso.id"
                                            class="flex cursor-pointer items-center gap-3 rounded-lg border border-transparent px-3 py-2.5 text-sm transition-all duration-200 hover:border-pink-200 hover:bg-white/80"
                                            :class="{
                                                'border-rose-200 bg-white shadow-sm':
                                                    form.permisos.includes(
                                                        permiso.nombre,
                                                    ),
                                            }"
                                        >
                                            <Checkbox
                                                :checked="
                                                    form.permisos.includes(
                                                        permiso.nombre,
                                                    )
                                                "
                                                @update:model-value="
                                                    togglePermiso(
                                                        permiso.nombre,
                                                    )
                                                "
                                                class="data-[state=checked]:border-rose-400 data-[state=checked]:bg-rose-400"
                                            />
                                            <span class="text-gray-700">{{
                                                permiso.descripcion ??
                                                permiso.nombre
                                            }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <InputError :message="form.errors.permisos" />
                        </div>
                    </div>
                </div>
                <DialogFooter
                    class="shrink-0 gap-2 border-t border-pink-100 px-0 pt-4"
                >
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="outline"
                            class="border-pink-200 text-gray-600 hover:bg-pink-50 hover:text-rose-600"
                        >
                            Cancelar
                        </Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-gradient-to-r from-rose-400 via-pink-400 to-purple-400 text-white shadow-lg transition-all duration-300 hover:from-rose-500 hover:via-pink-500 hover:to-purple-500"
                    >
                        <ShieldCheck class="mr-1.5 h-4 w-4" />
                        {{ isEditing ? 'Guardar Cambios' : 'Crear Rol' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="text-xl text-red-500"
                    >Eliminar Rol</DialogTitle
                >
                <DialogDescription class="text-gray-600">
                    ¿Estás seguro de eliminar el rol
                    <strong class="text-gray-800">{{
                        roleToDelete?.nombre
                    }}</strong
                    >? Esta acción no se puede deshacer.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button
                        type="button"
                        variant="outline"
                        class="border-pink-200 text-gray-600 hover:bg-pink-50"
                    >
                        Cancelar
                    </Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    @click="submitDelete"
                    class="bg-red-400 shadow-lg shadow-red-200/50 hover:bg-red-500"
                >
                    <Trash2 class="mr-1.5 h-4 w-4" />
                    Eliminar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

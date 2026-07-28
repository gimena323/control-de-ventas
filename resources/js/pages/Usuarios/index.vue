<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CalendarDays,
    Pencil,
    Plus,
    Search,
    Trash2,
    User as UserIcon,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePickerInput } from '@/components/ui/date-picker';
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
import type { PaginatedData } from '@/types';

const { can } = usePermissions();

type RoleOption = {
    id: number;
    nombre: string;
    descripcion: string | null;
};

type UserItem = {
    id: number;
    name: string;
    nombres: string | null;
    apellido_paterno: string | null;
    apellido_materno: string | null;
    telefono: string | null;
    fecha_ingreso: string | null;
    fecha_fin: string | null;
    email: string;
    email_verified_at: string | null;
    status: string;
    created_at: string;
    roles: { id: number; nombre: string }[];
};

type Props = {
    users: PaginatedData<UserItem>;
    roles: RoleOption[];
    filters: { search?: string };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Usuarios',
                href: '/usuarios',
            },
        ],
    },
});

const search = ref(props.filters.search ?? '');

watch(search, (value) => {
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
});

const formDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const editingUser = ref<UserItem | null>(null);

const form = useForm({
    nombres: '',
    apellido_paterno: '',
    apellido_materno: '',
    telefono: '',
    fecha_ingreso: '',
    fecha_fin: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
    status: 'activo',
});

const isEditing = computed(() => !!editingUser.value);
const dialogTitle = computed(() =>
    isEditing.value ? 'Editar Usuario' : 'Nuevo Usuario',
);
const dialogDescription = computed(() =>
    isEditing.value
        ? 'Actualiza los datos y el rol del usuario.'
        : 'Crea un nuevo usuario con rol asignado.',
);

function openCreateDialog() {
    editingUser.value = null;
    form.reset();
    form.status = 'activo';
    formDialogOpen.value = true;
}

function openEditDialog(user: UserItem) {
    editingUser.value = user;
    form.nombres = user.nombres ?? '';
    form.apellido_paterno = user.apellido_paterno ?? '';
    form.apellido_materno = user.apellido_materno ?? '';
    form.telefono = user.telefono ?? '';
    form.fecha_ingreso = user.fecha_ingreso ?? '';
    form.fecha_fin = user.fecha_fin ?? '';
    form.email = user.email;
    form.password = '';
    form.password_confirmation = '';
    form.role = user.roles[0]?.nombre ?? '';
    form.status = user.status;
    formDialogOpen.value = true;
}

function submitSave() {
    if (isEditing.value && editingUser.value) {
        form.patch(`/usuarios/${editingUser.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                formDialogOpen.value = false;
                editingUser.value = null;
            },
        });
    } else {
        form.post('/usuarios', {
            preserveScroll: true,
            onSuccess: () => {
                formDialogOpen.value = false;
                form.reset();
            },
        });
    }
}

const userToDelete = ref<UserItem | null>(null);

function confirmDelete(user: UserItem) {
    userToDelete.value = user;
    deleteDialogOpen.value = true;
}

function submitDelete() {
    if (!userToDelete.value) return;
    const id = userToDelete.value.id;
    deleteDialogOpen.value = false;
    router.delete(`/usuarios/${id}`, {
        preserveScroll: true,
        onSuccess: () => {
            userToDelete.value = null;
        },
    });
}

function toggleStatus(user: UserItem) {
    router.patch(`/usuarios/${user.id}/status`, {
        preserveScroll: true,
    });
}

const roleBadgeVariant: Record<string, string> = {
    admin: 'default',
    cajera: 'secondary',
    operador: 'outline',
};

function userRoleBadge(roleName: string): string {
    return roleBadgeVariant[roleName] ?? 'secondary';
}
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <Heading
                title="Usuarios"
                description="Administra los usuarios del sistema y asigna roles."
            />
            <Button v-if="can('usuarios.gestion')" @click="openCreateDialog">
                <Plus class="mr-2 h-4 w-4" />
                Nuevo Usuario
            </Button>
        </div>

        <div class="relative max-w-sm">
            <Search
                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="search"
                placeholder="Buscar usuarios..."
                class="pl-10"
            />
        </div>

        <div class="overflow-hidden rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50 text-left">
                        <th class="px-4 py-3 font-medium">Usuario</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Rol</th>
                        <th class="px-4 py-3 font-medium">Verificado</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        class="border-b transition-colors last:border-0 hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="avatar-initial flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary"
                                >
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </div>
                                <span class="font-medium">{{ user.name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ user.email }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge
                                v-for="role in user.roles"
                                :key="role.id"
                                :variant="userRoleBadge(role.nombre)"
                                class="text-xs"
                            >
                                {{ role.nombre }}
                            </Badge>
                            <span
                                v-if="user.roles.length === 0"
                                class="text-xs text-muted-foreground"
                                >—</span
                            >
                        </td>
                        <td class="px-4 py-3">
                            <Badge
                                :variant="
                                    user.email_verified_at
                                        ? 'default'
                                        : 'secondary'
                                "
                                class="text-xs"
                            >
                                {{ user.email_verified_at ? 'Sí' : 'No' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    :class="[
                                        'relative inline-flex h-6 w-11 cursor-pointer items-center rounded-full transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-offset-2 focus:outline-none',
                                        user.status === 'activo'
                                            ? 'bg-emerald-500 focus:ring-emerald-400'
                                            : 'bg-gray-300 focus:ring-gray-400',
                                    ]"
                                    role="switch"
                                    :aria-checked="user.status === 'activo'"
                                    @click="toggleStatus(user)"
                                >
                                    <span
                                        :class="[
                                            'inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition-transform duration-200 ease-in-out',
                                            user.status === 'activo'
                                                ? 'translate-x-[22px]'
                                                : 'translate-x-[2px]',
                                        ]"
                                    />
                                </button>
                                <span
                                    class="text-xs font-medium"
                                    :class="
                                        user.status === 'activo'
                                            ? 'text-emerald-600'
                                            : 'text-gray-400'
                                    "
                                >
                                    {{
                                        user.status === 'activo'
                                            ? 'Activo'
                                            : 'Desactivado'
                                    }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div
                                v-if="can('usuarios.gestion')"
                                class="flex items-center justify-end gap-1"
                            >
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8"
                                    @click="openEditDialog(user)"
                                >
                                    <Pencil class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-red-500 hover:text-red-600"
                                    @click="confirmDelete(user)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td
                            colspan="6"
                            class="py-12 text-center text-sm text-muted-foreground"
                        >
                            <UserIcon class="mx-auto mb-2 h-8 w-8 opacity-40" />
                            No se encontraron usuarios.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="users.last_page > 1"
            class="flex items-center justify-between"
        >
            <p class="text-sm text-muted-foreground">
                Mostrando {{ users.from }}–{{ users.to }} de
                {{ users.total }} usuarios
            </p>
            <div class="flex items-center gap-2">
                <Link
                    :href="users.prev_page_url ?? '#'"
                    :class="[
                        'rounded-lg border px-3 py-1.5 text-sm transition-colors',
                        users.prev_page_url
                            ? 'hover:bg-muted'
                            : 'pointer-events-none opacity-40',
                    ]"
                >
                    Anterior
                </Link>
                <Link
                    :href="users.next_page_url ?? '#'"
                    :class="[
                        'rounded-lg border px-3 py-1.5 text-sm transition-colors',
                        users.next_page_url
                            ? 'hover:bg-muted'
                            : 'pointer-events-none opacity-40',
                    ]"
                >
                    Siguiente
                </Link>
            </div>
        </div>
    </div>

    <Dialog v-model:open="formDialogOpen">
        <DialogContent class="flex flex-col sm:max-w-[1000px] max-h-[90vh]">
            <DialogHeader class="shrink-0">
                <DialogTitle>{{ dialogTitle }}</DialogTitle>
                <DialogDescription>{{ dialogDescription }}</DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitSave" class="flex flex-col overflow-hidden">
                <div class="overflow-y-auto flex-1 -mx-6 px-6 py-4">
                    <div class="grid gap-6">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="nombres">Nombre(s) *</Label>
                                <Input
                                    id="nombres"
                                    v-model="form.nombres"
                                    placeholder="Nombre(s)"
                                />
                                <InputError :message="form.errors.nombres" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="apellido_paterno">Apellido paterno *</Label>
                                <Input
                                    id="apellido_paterno"
                                    v-model="form.apellido_paterno"
                                    placeholder="Apellido paterno"
                                />
                                <InputError :message="form.errors.apellido_paterno" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="apellido_materno">Apellido materno *</Label>
                                <Input
                                    id="apellido_materno"
                                    v-model="form.apellido_materno"
                                    placeholder="Apellido materno"
                                />
                                <InputError :message="form.errors.apellido_materno" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="telefono">Teléfono</Label>
                                <Input
                                    id="telefono"
                                    v-model="form.telefono"
                                    placeholder="+591 71234567"
                                />
                                <InputError :message="form.errors.telefono" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="fecha_ingreso">Fecha de ingreso</Label>
                                <DatePickerInput
                                    id="fecha_ingreso"
                                    v-model="form.fecha_ingreso"
                                />
                                <InputError :message="form.errors.fecha_ingreso" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="fecha_fin">Fecha de fin</Label>
                                <DatePickerInput
                                    id="fecha_fin"
                                    v-model="form.fecha_fin"
                                />
                                <InputError :message="form.errors.fecha_fin" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="email">Correo electrónico *</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    autocomplete="off"
                                    placeholder="ejemplo@gmail.com"
                                />
                                <InputError :message="form.errors.email" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="password">
                                    {{
                                        isEditing
                                            ? 'Nueva contraseña (dejar vacío para mantener)'
                                            : 'Contraseña *'
                                    }}
                                </Label>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    autocomplete="new-password"
                                    placeholder="Ingresa tu contraseña"
                                />
                                <InputError :message="form.errors.password" />
                            </div>
                            <div v-if="!isEditing" class="grid gap-2">
                                <Label for="password_confirmation"
                                    >Confirmar contraseña *</Label
                                >
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    placeholder="Repite tu contraseña"
                                />
                                <InputError
                                    :message="form.errors.password_confirmation"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="role">Rol *</Label>
                                <Select v-model="form.role">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar rol" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="r in roles"
                                            :key="r.id"
                                            :value="r.nombre"
                                        >
                                            {{ r.nombre }} — {{ r.descripcion ?? '' }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.role" />
                            </div>
                            <div class="flex flex-col justify-end gap-2">
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="form.status === 'activo'"
                                        :class="[
                                            'relative inline-flex h-6 w-11 cursor-pointer items-center rounded-full transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-offset-2 focus:outline-none',
                                            form.status === 'activo'
                                                ? 'bg-emerald-500 focus:ring-emerald-400'
                                                : 'bg-gray-300 focus:ring-gray-400',
                                        ]"
                                        @click="
                                            form.status =
                                                form.status === 'activo'
                                                    ? 'desactivado'
                                                    : 'activo'
                                        "
                                    >
                                        <span
                                            :class="[
                                                'inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition-transform duration-200 ease-in-out',
                                                form.status === 'activo'
                                                    ? 'translate-x-[22px]'
                                                    : 'translate-x-[2px]',
                                            ]"
                                        />
                                    </button>
                                    <Label class="cursor-pointer font-medium" @click="form.status = form.status === 'activo' ? 'desactivado' : 'activo'">
                                        Cuenta activa
                                    </Label>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Si está desactivada el usuario no podrá iniciar sesión.
                                </p>
                                <InputError :message="form.errors.status" />
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter class="shrink-0 border-t border-pink-100 pt-4 px-0">
                    <DialogClose as-child>
                        <Button type="button" variant="outline"
                            >Cancelar</Button
                        >
                    </DialogClose>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEditing ? 'Guardar Cambios' : 'Crear Usuario' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="deleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Eliminar Usuario</DialogTitle>
                <DialogDescription>
                    ¿Estás seguro de eliminar a
                    <strong>{{ userToDelete?.name }}</strong
                    >? Esta acción no se puede deshacer.
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

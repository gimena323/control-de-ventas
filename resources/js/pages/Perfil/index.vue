<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import {
    LockKeyhole,
    Mail,
    Phone,
    ShoppingBag,
    UserRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
import PerfilController from '@/actions/App/Http/Controllers/PerfilController';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { pedidos as pedidosRoute } from '@/routes';
import type { Cliente } from '@/types';

const page = usePage();
const props = defineProps<{ cliente: Cliente | null }>();
const user = computed(() => page.props.auth.user ?? null);

const defaults = computed(() => ({
    nombres: user.value?.nombres ?? '',
    apellido_paterno: user.value?.apellido_paterno ?? '',
    apellido_materno: user.value?.apellido_materno ?? '',
    telefono: user.value?.telefono ?? '',
    email: user.value?.email ?? '',
    ci_nit: props.cliente?.ci_nit ?? '',
    direccion: props.cliente?.direccion ?? '',
}));
</script>

<template>
    <Head title="Mi Perfil" />

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 sm:py-12">
        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-pink-200/70 bg-white/70 px-4 py-1.5 text-xs font-medium text-pink-600 shadow-sm"
                >
                    <UserRound class="h-3 w-3" />
                    Área del cliente
                </span>
                <h1 class="mt-4 text-2xl font-bold text-slate-900 sm:text-3xl">
                    Mi Perfil
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    Actualiza tus datos personales y la contraseña de tu cuenta.
                </p>
            </div>

            <Link
                :href="pedidosRoute().url"
                class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all hover:scale-[1.02] hover:shadow-lg"
            >
                <ShoppingBag class="h-4 w-4" />
                Ver todas mis compras
            </Link>
        </div>

        <div class="space-y-6">
            <section
                class="rounded-2xl border border-purple-100/60 bg-white/80 p-6 backdrop-blur"
            >
                <h2
                    class="mb-5 flex items-center gap-2 text-lg font-bold text-slate-800"
                >
                    <UserRound class="h-5 w-5 text-purple-500" />
                    Datos personales
                </h2>

                <Form
                    v-bind="PerfilController.update.form()"
                    preserve-scroll
                    class="grid gap-5 sm:grid-cols-2"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="nombres">Nombres</Label>
                        <Input
                            id="nombres"
                            name="nombres"
                            type="text"
                            :default-value="defaults.nombres"
                            class="border-purple-100"
                        />
                        <InputError :message="errors.nombres" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="apellido_paterno">Apellido paterno</Label>
                        <Input
                            id="apellido_paterno"
                            name="apellido_paterno"
                            type="text"
                            :default-value="defaults.apellido_paterno"
                            class="border-purple-100"
                        />
                        <InputError :message="errors.apellido_paterno" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="apellido_materno">Apellido materno</Label>
                        <Input
                            id="apellido_materno"
                            name="apellido_materno"
                            type="text"
                            :default-value="defaults.apellido_materno"
                            class="border-purple-100"
                        />
                        <InputError :message="errors.apellido_materno" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="telefono" class="flex items-center gap-1.5">
                            <Phone class="h-3.5 w-3.5 text-slate-400" />
                            Teléfono
                        </Label>
                        <Input
                            id="telefono"
                            name="telefono"
                            type="tel"
                            :default-value="defaults.telefono"
                            class="border-purple-100"
                        />
                        <InputError :message="errors.telefono" />
                    </div>

                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="email" class="flex items-center gap-1.5">
                            <Mail class="h-3.5 w-3.5 text-slate-400" />
                            Correo electrónico
                        </Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            :default-value="defaults.email"
                            class="border-purple-100"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ci_nit">CI/NIT</Label>
                        <Input
                            id="ci_nit"
                            name="ci_nit"
                            type="text"
                            :default-value="defaults.ci_nit"
                            class="border-purple-100"
                        />
                        <InputError :message="errors.ci_nit" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="direccion">Dirección</Label>
                        <Input
                            id="direccion"
                            name="direccion"
                            type="text"
                            :default-value="defaults.direccion"
                            class="border-purple-100"
                        />
                        <InputError :message="errors.direccion" />
                    </div>

                    <div class="sm:col-span-2">
                        <Button
                            :disabled="processing"
                            class="bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-md"
                        >
                            Guardar cambios
                        </Button>
                    </div>
                </Form>
            </section>

            <section
                class="rounded-2xl border border-purple-100/60 bg-white/80 p-6 backdrop-blur"
            >
                <h2
                    class="mb-5 flex items-center gap-2 text-lg font-bold text-slate-800"
                >
                    <LockKeyhole class="h-5 w-5 text-purple-500" />
                    Cambiar contraseña
                </h2>

                <Form
                    v-bind="SecurityController.update.form()"
                    preserve-scroll
                    reset-on-success
                    :reset-on-error="[
                        'current_password',
                        'password',
                        'password_confirmation',
                    ]"
                    class="grid gap-5"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="current_password">Contraseña actual</Label>
                        <PasswordInput
                            id="current_password"
                            name="current_password"
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <InputError :message="errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Nueva contraseña</Label>
                        <PasswordInput
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation"
                            >Confirmar nueva contraseña</Label
                        >
                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <div>
                        <Button
                            :disabled="processing"
                            variant="outline"
                            class="border-purple-200 text-purple-700 hover:bg-purple-50"
                        >
                            Actualizar contraseña
                        </Button>
                    </div>
                </Form>
            </section>
        </div>
    </div>
</template>

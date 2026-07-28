<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { LockKeyhole, Mail, Heart, Sparkles } from 'lucide-vue-next';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Iniciar sesión en tu cuenta',
        description: 'Ingresa tu correo electrónico y contraseña para iniciar sesión',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="Iniciar sesión" />

    <div
        v-if="status"
        class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400"
    >
        {{ status }}
    </div>

    <div class="mb-2 flex items-center justify-center gap-2">
        <Heart class="size-5 fill-rose-500 text-rose-500" />
        <span class="text-lg font-semibold bg-gradient-to-r from-rose-600 via-pink-500 to-purple-600 bg-clip-text text-transparent">
            Emma Fashion
        </span>
        <Sparkles class="size-4 text-purple-400" />
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="rounded-2xl border border-neutral-200 bg-white/60 p-6 shadow-sm backdrop-blur-sm dark:border-neutral-700 dark:bg-neutral-800/60">
            <div class="grid gap-5">
                <div class="grid gap-2">
                    <Label for="email" class="flex items-center gap-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                        <Mail class="size-4 text-rose-500" />
                        Correo electrónico
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="correo@ejemplo.com"
                        class="border-neutral-300 bg-white/80 transition-all duration-200 focus:border-rose-400 focus:ring-rose-400/30 dark:border-neutral-600 dark:bg-neutral-700/80"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="flex items-center gap-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                            <LockKeyhole class="size-4 text-rose-500" />
                            Contraseña
                        </Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm text-rose-500 decoration-rose-400 hover:text-rose-600"
                            :tabindex="5"
                        >
                            ¿Olvidaste tu contraseña?
                        </TextLink>
                    </div>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="border-neutral-300 bg-white/80 transition-all duration-200 focus:border-rose-400 focus:ring-rose-400/30 dark:border-neutral-600 dark:bg-neutral-700/80"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox
                            id="remember"
                            name="remember"
                            :tabindex="3"
                            class="border-neutral-400 text-rose-500 focus:ring-rose-400/30 data-[state=checked]:bg-rose-500 data-[state=checked]:border-rose-500"
                        />
                        <span class="text-sm text-neutral-600 dark:text-neutral-400">Recordarme</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full bg-gradient-to-r from-rose-500 via-pink-500 to-purple-500 text-white shadow-lg shadow-rose-500/25 transition-all duration-300 hover:shadow-xl hover:shadow-rose-500/30 hover:scale-[1.02] active:scale-[0.98]"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    <Heart v-else class="size-4 mr-2 fill-white" />
                    Iniciar sesión
                </Button>
            </div>
        </div>

        <div
            class="text-center text-sm text-neutral-500 dark:text-neutral-400"
            v-if="canRegister"
        >
            ¿No tienes una cuenta?
            <TextLink
                :href="register()"
                :tabindex="5"
                class="text-rose-500 decoration-rose-400 hover:text-rose-600"
            >
                Regístrate
            </TextLink>
        </div>
    </Form>
</template>
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { LockKeyhole, Mail, Sparkles } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { redirect as googleRedirect } from '@/routes/auth/google';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Iniciar sesión en tu cuenta',
        description:
            'Ingresa tu correo electrónico y contraseña para iniciar sesión',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
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

    <InputError
        :message="$page.props.errors?.google"
        class="mb-4 text-center"
    />

    <div class="login-brand mb-3 flex flex-col items-center gap-2">
        <div
            class="brand-photo size-36 rounded-full border-4 border-fuchsia-500 shadow-[0_0_24px_#d000e988]"
            role="img"
            aria-label="Logo Emma Fashion"
        ></div>
        <div class="flex items-center gap-2">
            <span
                class="bg-gradient-to-r from-white via-pink-300 to-fuchsia-500 bg-clip-text text-2xl font-bold text-transparent"
            >
                Emma Fashion
            </span>
            <Sparkles class="size-4 text-fuchsia-500" />
        </div>
    </div>

    <Form
        v-bind="store()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="login-form flex flex-col gap-3"
    >
        <div class="rounded-2xl border border-indigo-500/70 bg-[#06064d] p-4">
            <div class="grid gap-3">
                <div class="grid gap-2">
                    <Label
                        for="email"
                        class="flex items-center gap-2 text-sm font-semibold text-violet-100"
                    >
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
                        class="h-11 border-violet-400/40 bg-indigo-900/80 text-violet-50 shadow-sm placeholder:text-indigo-300 focus:border-fuchsia-400 focus:ring-fuchsia-400/30"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label
                            for="password"
                            class="flex items-center gap-2 text-sm font-semibold text-violet-100"
                        >
                            <LockKeyhole class="size-4 text-rose-500" />
                            Contraseña
                        </Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm text-pink-300 decoration-pink-400 hover:text-fuchsia-200"
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
                        class="h-11 border-violet-400/40 bg-indigo-900/80 text-violet-50 shadow-sm placeholder:text-indigo-300 focus:border-fuchsia-400 focus:ring-fuchsia-400/30"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox
                            id="remember"
                            name="remember"
                            :tabindex="3"
                            class="border-violet-300 text-fuchsia-300 focus:ring-fuchsia-400/30 data-[state=checked]:border-fuchsia-400 data-[state=checked]:bg-fuchsia-500"
                        />
                        <span class="text-sm text-indigo-200">Recordarme</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-3 h-11 w-full rounded-xl bg-gradient-to-r from-rose-500 via-fuchsia-500 to-violet-600 font-semibold text-white shadow-lg shadow-pink-300/50 transition-all duration-200 hover:-translate-y-0.5 hover:from-rose-600 hover:to-violet-700 hover:shadow-xl active:translate-y-0"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    <Sparkles v-else class="mr-2 size-4" />
                    Iniciar sesión
                </Button>
            </div>
        </div>
    </Form>

    <div class="my-3 flex items-center gap-3" aria-hidden="true">
        <span class="h-px flex-1 bg-violet-400/30"></span>
        <span class="text-xs font-medium text-indigo-300">o continúa con</span>
        <span class="h-px flex-1 bg-violet-400/30"></span>
    </div>

    <a
        :href="googleRedirect().url"
        class="flex h-11 w-full items-center justify-center gap-3 rounded-xl border border-violet-400/40 bg-indigo-900/80 px-4 text-sm font-semibold text-violet-100 shadow-sm transition-all hover:-translate-y-0.5 hover:border-fuchsia-400 hover:bg-violet-900 hover:shadow-lg"
        data-test="google-login-button"
    >
        <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true">
            <path
                fill="#4285F4"
                d="M21.6 12.23c0-.71-.06-1.4-.18-2.07H12v3.92h5.38a4.6 4.6 0 0 1-2 3.02v2.54h3.24c1.9-1.75 2.98-4.33 2.98-7.41Z"
            />
            <path
                fill="#34A853"
                d="M12 22c2.7 0 4.98-.9 6.64-2.36l-3.24-2.54c-.9.6-2.05.96-3.4.96-2.61 0-4.82-1.76-5.61-4.13H3.04v2.62A10 10 0 0 0 12 22Z"
            />
            <path
                fill="#FBBC05"
                d="M6.39 13.93A6.02 6.02 0 0 1 6.07 12c0-.67.12-1.32.32-1.93V7.45H3.04A10 10 0 0 0 2 12c0 1.64.39 3.19 1.04 4.55l3.35-2.62Z"
            />
            <path
                fill="#EA4335"
                d="M12 5.94c1.47 0 2.79.5 3.83 1.5l2.88-2.88A9.65 9.65 0 0 0 12 2a10 10 0 0 0-8.96 5.45l3.35 2.62C7.18 7.7 9.39 5.94 12 5.94Z"
            />
        </svg>
        Continuar con Google
    </a>
</template>

<style scoped>
.brand-photo {
    background-image: url('/images/login-brand-reference.jpeg');
    background-size: 1152px auto;
    background-position: center -122px;
}

.login-form :deep(input:not([type='checkbox'])) {
    background-color: #141477;
    color: #f3e8ff;
    border-color: #5741d5;
    border-radius: 999px;
}

.login-form :deep(input:-webkit-autofill) {
    -webkit-text-fill-color: #f3e8ff;
    -webkit-box-shadow: 0 0 0 1000px #141477 inset;
}

.login-form :deep(button[type='submit']) {
    border-radius: 999px;
    background: linear-gradient(90deg, #ff2295, #da00ed, #5423ff);
    box-shadow: 0 0 16px #ce00df55;
}

@media (max-height: 760px) {
    .brand-photo {
        width: 112px;
        height: 112px;
        background-size: 896px auto;
        background-position: center -95px;
    }
}
</style>

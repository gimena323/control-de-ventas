<?php

namespace App\Actions\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as GoogleUser;

class AuthenticateGoogleUser
{
    public function __construct(private SyncGoogleClientProfile $syncClientProfile) {}

    public function execute(GoogleUser $googleUser): User
    {
        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();
        $emailVerified = (bool) ($googleUser->user['email_verified'] ?? $googleUser->user['verified_email'] ?? false);

        if (! $email || ! $googleId || ! $emailVerified) {
            throw ValidationException::withMessages([
                'google' => 'Google no proporcionó un correo electrónico verificado.',
            ]);
        }

        return DB::transaction(function () use ($googleUser, $email, $googleId): User {
            $fullName = trim((string) ($googleUser->getName() ?: Str::before($email, '@')));
            $nameParts = preg_split('/\s+/', $fullName) ?: [];
            $nombres = $nameParts[0] ?? $fullName;
            $apellidoPaterno = $nameParts[1] ?? null;
            $apellidoMaterno = $nameParts[2] ?? null;

            $user = User::query()->where('email', $email)->lockForUpdate()->first();

            if ($user) {
                $this->validateExistingUser($user, $googleId);
                $user->forceFill([
                    'google_id' => $googleId,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'name' => $user->name ?: $fullName,
                    'nombres' => $user->nombres ?: $nombres,
                    'apellido_paterno' => $user->apellido_paterno ?: $apellidoPaterno,
                    'apellido_materno' => $user->apellido_materno ?: $apellidoMaterno,
                ])->save();

                $this->syncClientProfile->execute($user);

                return $user;
            }

            $user = User::query()->create([
                'name' => $fullName,
                'nombres' => $nombres,
                'apellido_paterno' => $apellidoPaterno,
                'apellido_materno' => $apellidoMaterno,
                'email' => $email,
                'google_id' => $googleId,
                'password' => Hash::make(Str::random(40)),
                'role' => Role::CLIENTE,
                'status' => 'activo',
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $clientRole = Role::query()->firstOrCreate(
                ['nombre' => Role::CLIENTE],
                ['descripcion' => 'Cliente de la tienda online'],
            );
            $user->roles()->sync([$clientRole->id]);
            $this->syncClientProfile->execute($user);

            return $user;
        });
    }

    private function validateExistingUser(User $user, string $googleId): void
    {
        $isClient = $user->role === Role::CLIENTE || $user->hasRole(Role::CLIENTE);

        if (! $isClient) {
            throw ValidationException::withMessages([
                'google' => 'Este correo pertenece al personal. Inicia sesión con tu contraseña.',
            ]);
        }

        if (! $user->isActivo()) {
            throw ValidationException::withMessages([
                'google' => 'Esta cuenta se encuentra desactivada.',
            ]);
        }

        if ($user->google_id && $user->google_id !== $googleId) {
            throw ValidationException::withMessages([
                'google' => 'Esta cuenta ya está vinculada con otra cuenta de Google.',
            ]);
        }
    }
}

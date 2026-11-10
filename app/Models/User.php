<?php

namespace App\Models;

use App\Enums\Permission as PermissionEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'nombres', 'apellido_paterno', 'apellido_materno', 'email', 'google_id', 'password', 'role', 'status', 'telefono', 'fecha_ingreso', 'fecha_fin'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'fecha_ingreso' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function getNameAttribute(?string $value): string
    {
        if ($value) {
            return $value;
        }

        $parts = array_filter([$this->nombres, $this->apellido_paterno, $this->apellido_materno]);

        return implode(' ', $parts) ?: 'Sin nombre';
    }

    public function isActivo(): bool
    {
        return $this->status === 'activo';
    }

    public function scopeActivos($query)
    {
        return $query->where('status', 'activo');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class);
    }

    public function assignRole(string $roleName): void
    {
        $role = Role::where('nombre', $roleName)->first();

        if ($role) {
            $this->roles()->sync([$role->id]);
            $this->role = $roleName;
            $this->save();
        }
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('nombre', $role)->exists();
    }

    public function hasAnyRole(array|string $roles): bool
    {
        return $this->roles()->whereIn('nombre', (array) $roles)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function rolesNombres(): array
    {
        return $this->roles->pluck('nombre')->toArray();
    }

    public function permissionsNombres(): array
    {
        if ($this->isAdmin()) {
            return PermissionEnum::valuesForAdministrador();
        }

        if (! $this->relationLoaded('roles')) {
            $this->load('roles.permissions');
        }

        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions->pluck('nombre'))
            ->unique()
            ->values()
            ->toArray();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissionsNombres(), true);
    }

    public function canAccessPanel(): bool
    {
        return $this->hasPermission(PermissionEnum::PanelVer->value);
    }

    public function syncRolesFromLegacyColumn(): void
    {
        if ($this->role && $this->roles()->doesntExist()) {
            $this->assignRole($this->role);
        }
    }

    public function ensureRolesHavePermissions(): void
    {
        $this->loadMissing('roles');

        foreach ($this->roles as $role) {
            $role->ensureDefaultPermissions();
        }
    }
}

<?php

namespace App\Models;

use App\Enums\Permission as PermissionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public const ADMIN = 'admin';

    public const VENDEDOR = 'vendedor';

    public const ENCARGADO_INVENTARIO = 'encargado_inventario';

    protected $fillable = [
        'nombre',
        'descripcion',
        'user_id',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    public function syncPermissionsByName(array $names): void
    {
        $permissionIds = Permission::whereIn('nombre', $names)->pluck('id');

        $this->permissions()->sync($permissionIds);
    }

    public function ensureDefaultPermissions(): void
    {
        if ($this->permissions()->exists()) {
            return;
        }

        $names = match ($this->nombre) {
            self::ADMIN => PermissionEnum::valuesForAdministrador(),
            self::VENDEDOR => PermissionEnum::valuesForVendedor(),
            self::ENCARGADO_INVENTARIO => PermissionEnum::valuesForEncargadoInventario(),
            default => [],
        };

        if (! empty($names)) {
            $this->syncPermissionsByName($names);
        }
    }
}

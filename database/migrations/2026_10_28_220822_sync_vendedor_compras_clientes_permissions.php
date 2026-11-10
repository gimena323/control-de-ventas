<?php

use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $nombres = [
            PermissionEnum::ComprasVer->value,
            PermissionEnum::ComprasCrear->value,
            PermissionEnum::ClientesGestion->value,
        ];

        foreach (PermissionEnum::cases() as $permiso) {
            Permission::updateOrCreate(
                ['nombre' => $permiso->value],
                ['descripcion' => $permiso->label(), 'grupo' => $permiso->group()]
            );
        }

        $vendedor = Role::where('nombre', Role::VENDEDOR)->first();

        if ($vendedor) {
            $ids = Permission::whereIn('nombre', $nombres)->pluck('id');
            $vendedor->permissions()->syncWithoutDetaching($ids);
        }
    }

    public function down(): void
    {
        $vendedor = Role::where('nombre', Role::VENDEDOR)->first();

        if ($vendedor) {
            $ids = Permission::whereIn('nombre', [
                PermissionEnum::ComprasVer->value,
                PermissionEnum::ComprasCrear->value,
                PermissionEnum::ClientesGestion->value,
            ])->pluck('id');
            $vendedor->permissions()->detach($ids);
        }
    }
};

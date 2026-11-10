<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $currentNames = array_map(fn (PermissionEnum $p) => $p->value, PermissionEnum::cases());

        Permission::whereNotIn('nombre', $currentNames)->delete();

        Role::whereNotIn('nombre', [Role::ADMIN, Role::VENDEDOR, Role::ENCARGADO_INVENTARIO, Role::CLIENTE])->each(function (Role $role) {
            $role->permissions()->detach();
            $role->users()->detach();
            $role->delete();
        });

        foreach (PermissionEnum::cases() as $permiso) {
            Permission::updateOrCreate(
                ['nombre' => $permiso->value],
                [
                    'descripcion' => $permiso->label(),
                    'grupo' => $permiso->group(),
                ]
            );
        }

        $admin = Role::firstOrCreate(
            ['nombre' => Role::ADMIN],
            ['descripcion' => 'Administrador del sistema con acceso completo']
        );
        $admin->syncPermissionsByName(PermissionEnum::valuesForAdministrador());

        $vendedor = Role::firstOrCreate(
            ['nombre' => Role::VENDEDOR],
            ['descripcion' => 'Vendedor con permisos de ventas y clientes']
        );
        $vendedor->syncPermissionsByName(PermissionEnum::valuesForVendedor());

        $encargadoInventario = Role::firstOrCreate(
            ['nombre' => Role::ENCARGADO_INVENTARIO],
            ['descripcion' => 'Encargado de inventario, compras y proveedores']
        );
        $encargadoInventario->syncPermissionsByName(PermissionEnum::valuesForEncargadoInventario());

        Role::firstOrCreate(
            ['nombre' => Role::CLIENTE],
            ['descripcion' => 'Cliente de la tienda online']
        );
    }
}

<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'pedidos.ver' => ['descripcion' => 'Ver pedidos', 'grupo' => 'pedidos'],
            'pedidos.gestion' => ['descripcion' => 'Gestionar pedidos', 'grupo' => 'pedidos'],
        ];

        foreach ($permissions as $nombre => $data) {
            Permission::updateOrCreate(
                ['nombre' => $nombre],
                ['descripcion' => $data['descripcion'], 'grupo' => $data['grupo']]
            );
        }

        $vendedor = Role::where('nombre', Role::VENDEDOR)->first();

        if ($vendedor) {
            $ids = Permission::whereIn('nombre', array_keys($permissions))->pluck('id');
            $vendedor->permissions()->syncWithoutDetaching($ids);
        }
    }

    public function down(): void
    {
        Permission::whereIn('nombre', ['pedidos.ver', 'pedidos.gestion'])->delete();
    }
};

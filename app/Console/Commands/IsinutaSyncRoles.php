<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Console\Command;

class IsinutaSyncRoles extends Command
{
    protected $signature = 'isinuta:sync-roles';

    protected $description = 'Sincroniza roles y permisos: ejecuta PermissionsSeeder y sincroniza users.role legacy a la tabla pivote role_user';

    public function handle(): void
    {
        $this->call(PermissionsSeeder::class);

        $users = User::whereNotNull('role')->get();

        foreach ($users as $user) {
            $user->syncRolesFromLegacyColumn();
            $user->ensureRolesHavePermissions();
        }

        $this->info("Roles y permisos sincronizados para {$users->count()} usuarios.");
    }
}

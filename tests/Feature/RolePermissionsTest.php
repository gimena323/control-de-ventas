<?php

use App\Enums\Permission;
use App\Models\Cliente;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;

beforeEach(fn () => $this->seed(PermissionsSeeder::class));

function userWithRole(string $roleName): User
{
    $user = User::factory()->create(['role' => $roleName]);
    $role = Role::where('nombre', $roleName)->firstOrFail();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function userWithPermissions(array $permissions): User
{
    $role = Role::create([
        'nombre' => 'consulta_'.str()->random(6),
        'descripcion' => 'Rol de solo consulta',
    ]);
    $role->syncPermissionsByName($permissions);

    $user = User::factory()->create(['role' => $role->nombre]);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

test('admin accede a clientes.index', function () {
    $user = userWithRole(Role::ADMIN);

    $response = $this->actingAs($user)->get(route('clientes.index'));

    $response->assertOk();
});

test('admin accede a crear clientes via POST', function () {
    $user = userWithRole(Role::ADMIN);

    $response = $this->actingAs($user)->post(route('clientes.store'), [
        'nombre' => 'Test Cliente',
        'ci_nit' => '12345678',
        'telefono' => '70000000',
    ]);

    $response->assertSessionDoesntHaveErrors();
});

test('vendedor accede a clientes.index', function () {
    $user = userWithRole(Role::VENDEDOR);

    $response = $this->actingAs($user)->get(route('clientes.index'));

    $response->assertOk();
});

test('usuario sin clientes.gestion NO puede crear clientes (403)', function () {
    $user = userWithPermissions([Permission::ClientesVer->value]);

    $response = $this->actingAs($user)->post(route('clientes.store'), [
        'nombre' => 'Test Cliente',
        'ci_nit' => '12345678',
        'telefono' => '70000000',
    ]);

    $response->assertForbidden();
});

test('usuario sin clientes.gestion NO puede editar clientes (403)', function () {
    $user = userWithPermissions([Permission::ClientesVer->value]);
    $cliente = Cliente::factory()->create();

    $response = $this->actingAs($user)->patch(route('clientes.update', $cliente->id), [
        'nombre' => 'Updated',
    ]);

    $response->assertForbidden();
});

test('usuario sin clientes.gestion NO puede cambiar el estado de un cliente (403)', function () {
    $user = userWithPermissions([Permission::ClientesVer->value]);
    $cliente = Cliente::factory()->create();

    $response = $this->actingAs($user)->patch(route('clientes.estado', $cliente->id));

    $response->assertForbidden();
});

test('vendedor tiene permiso panel.ver', function () {
    $user = userWithRole(Role::VENDEDOR);

    expect($user->hasPermission(Permission::PanelVer->value))->toBeTrue();
});

test('vendedor NO tiene permiso usuarios.gestionar', function () {
    $user = userWithRole(Role::VENDEDOR);

    expect($user->hasPermission(Permission::UsuariosGestion->value))->toBeFalse();
});

test('vendedor NO tiene permiso configuraciones.gestionar', function () {
    $user = userWithRole(Role::VENDEDOR);

    expect($user->hasPermission(Permission::ConfiguracionesGestion->value))->toBeFalse();
});

test('admin tiene todos los permisos via hasPermission', function () {
    $user = userWithRole(Role::ADMIN);

    expect($user->hasPermission(Permission::UsuariosGestion->value))->toBeTrue();
    expect($user->hasPermission(Permission::ConfiguracionesGestion->value))->toBeTrue();
    expect($user->hasPermission(Permission::ReportesVentasVer->value))->toBeTrue();
    expect($user->hasPermission(Permission::ReportesComprasVer->value))->toBeTrue();
});

test('isAdmin funciona correctamente', function () {
    $admin = userWithRole(Role::ADMIN);
    $vendedor = userWithRole(Role::VENDEDOR);

    expect($admin->isAdmin())->toBeTrue();
    expect($vendedor->isAdmin())->toBeFalse();
});

test('assignRole asigna correctamente el rol', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::ADMIN);

    expect($user->hasRole(Role::ADMIN))->toBeTrue();
    expect($user->fresh()->role)->toBe(Role::ADMIN);
});

test('syncRolesFromLegacyColumn sincroniza desde columna legacy', function () {
    $user = User::factory()->create(['role' => Role::ADMIN]);

    $user->syncRolesFromLegacyColumn();

    expect($user->hasRole(Role::ADMIN))->toBeTrue();
});

test('ensureRolesHavePermissions asigna permisos por defecto', function () {
    $role = Role::where('nombre', Role::ADMIN)->firstOrFail();
    $role->permissions()->sync([]);
    $role->ensureDefaultPermissions();

    expect($role->permissions()->count())->toBeGreaterThan(0);
});

test('admin accede a roles.index', function () {
    $user = userWithRole(Role::ADMIN);

    $response = $this->actingAs($user)->get(route('roles.index'));

    $response->assertOk();
});

test('vendedor NO puede acceder a roles.index (403)', function () {
    $user = userWithRole(Role::VENDEDOR);

    $response = $this->actingAs($user)->get(route('roles.index'));

    $response->assertForbidden();
});

test('admin puede crear un nuevo rol', function () {
    $user = userWithRole(Role::ADMIN);

    $response = $this->actingAs($user)->post(route('roles.store'), [
        'nombre' => 'supervisor',
        'descripcion' => 'Rol de supervisor',
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect(Role::where('nombre', 'supervisor')->exists())->toBeTrue();
});

test('admin puede actualizar permisos de un rol', function () {
    $user = userWithRole(Role::ADMIN);
    $role = Role::where('nombre', Role::VENDEDOR)->firstOrFail();

    $response = $this->actingAs($user)->put(route('roles.update', $role->id), [
        'nombre' => Role::VENDEDOR,
        'permisos' => [Permission::PanelVer->value, Permission::VentasVer->value, Permission::VentasCrear->value],
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect($role->fresh()->permissions()->count())->toBe(3);
});

test('admin no puede eliminar un rol con usuarios asignados', function () {
    $user = userWithRole(Role::ADMIN);
    userWithRole(Role::VENDEDOR);
    $role = Role::where('nombre', Role::VENDEDOR)->firstOrFail();

    $response = $this->actingAs($user)->delete(route('roles.destroy', $role->id));

    expect(Role::where('nombre', Role::VENDEDOR)->exists())->toBeTrue();
});

test('admin accede a usuarios.index', function () {
    $user = userWithRole(Role::ADMIN);

    $response = $this->actingAs($user)->get(route('usuarios.index'));

    $response->assertOk();
});

test('vendedor NO puede acceder a usuarios.index (403)', function () {
    $user = userWithRole(Role::VENDEDOR);

    $response = $this->actingAs($user)->get(route('usuarios.index'));

    $response->assertForbidden();
});

test('admin puede crear un nuevo usuario', function () {
    $user = userWithRole(Role::ADMIN);

    $response = $this->actingAs($user)->post(route('usuarios.store'), [
        'nombres' => 'Test',
        'apellido_paterno' => 'User',
        'apellido_materno' => 'Apellido',
        'email' => 'testuser@example.com',
        'password' => 'password123',
        'role' => Role::VENDEDOR,
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect(User::where('email', 'testuser@example.com')->exists())->toBeTrue();
});

test('admin puede actualizar rol de un usuario', function () {
    $admin = userWithRole(Role::ADMIN);
    $target = userWithRole(Role::VENDEDOR);

    $response = $this->actingAs($admin)->patch(route('usuarios.update', $target->id), [
        'nombres' => $target->nombres ?? 'Test',
        'apellido_paterno' => $target->apellido_paterno ?? 'User',
        'apellido_materno' => $target->apellido_materno ?? 'Apellido',
        'email' => $target->email,
        'role' => Role::ADMIN,
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect($target->fresh()->hasRole(Role::ADMIN))->toBeTrue();
});

test('admin no puede eliminarse a si mismo', function () {
    $user = userWithRole(Role::ADMIN);

    $response = $this->actingAs($user)->delete(route('usuarios.destroy', $user->id));

    expect(User::where('id', $user->id)->exists())->toBeTrue();
});

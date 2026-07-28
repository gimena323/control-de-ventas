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

test('admin accede a clientes.index', function () {
    $user = userWithRole('admin');

    $response = $this->actingAs($user)->get(route('clientes.index'));

    $response->assertOk();
});

test('admin accede a crear clientes via POST', function () {
    $user = userWithRole('admin');

    $response = $this->actingAs($user)->post(route('clientes.store'), [
        'nombre' => 'Test Cliente',
    ]);

    $response->assertSessionDoesntHaveErrors();
});

test('cajera accede a clientes.index', function () {
    $user = userWithRole('cajera');

    $response = $this->actingAs($user)->get(route('clientes.index'));

    $response->assertOk();
});

test('cajera NO puede crear clientes (403)', function () {
    $user = userWithRole('cajera');

    $response = $this->actingAs($user)->post(route('clientes.store'), [
        'nombre' => 'Test Cliente',
    ]);

    $response->assertForbidden();
});

test('cajera NO puede editar clientes (403)', function () {
    $user = userWithRole('cajera');
    $cliente = Cliente::factory()->create();

    $response = $this->actingAs($user)->patch(route('clientes.update', $cliente->id), [
        'nombre' => 'Updated',
    ]);

    $response->assertForbidden();
});

test('cajera NO puede eliminar clientes (403)', function () {
    $user = userWithRole('cajera');
    $cliente = Cliente::factory()->create();

    $response = $this->actingAs($user)->delete(route('clientes.destroy', $cliente->id));

    $response->assertForbidden();
});

test('cajera tiene permiso panel.ver', function () {
    $user = userWithRole('cajera');

    expect($user->hasPermission(Permission::PanelVer->value))->toBeTrue();
});

test('cajera NO tiene permiso afiliados.gestionar', function () {
    $user = userWithRole('cajera');

    expect($user->hasPermission(Permission::AfiliadosGestionar->value))->toBeFalse();
});

test('cajera NO tiene permiso tramites.aprobar', function () {
    $user = userWithRole('cajera');

    expect($user->hasPermission(Permission::TramitesAprobar->value))->toBeFalse();
});

test('cajera NO tiene permiso reportes.deudas', function () {
    $user = userWithRole('cajera');

    expect($user->hasPermission(Permission::ReportesDeudas->value))->toBeFalse();
});

test('cajera NO tiene permiso usuarios.gestionar', function () {
    $user = userWithRole('cajera');

    expect($user->hasPermission(Permission::UsuariosGestionar->value))->toBeFalse();
});

test('admin tiene todos los permisos via hasPermission', function () {
    $user = userWithRole('admin');

    expect($user->hasPermission(Permission::AfiliadosGestionar->value))->toBeTrue();
    expect($user->hasPermission(Permission::TramitesAprobar->value))->toBeTrue();
    expect($user->hasPermission(Permission::ReportesDeudas->value))->toBeTrue();
    expect($user->hasPermission(Permission::UsuariosGestionar->value))->toBeTrue();
});

test('isAdmin funciona correctamente', function () {
    $admin = userWithRole('admin');
    $cajera = userWithRole('cajera');

    expect($admin->isAdmin())->toBeTrue();
    expect($cajera->isAdmin())->toBeFalse();
});

test('assignRole asigna correctamente el rol', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->fresh()->role)->toBe('admin');
});

test('syncRolesFromLegacyColumn sincroniza desde columna legacy', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $user->syncRolesFromLegacyColumn();

    expect($user->hasRole('admin'))->toBeTrue();
});

test('ensureRolesHavePermissions asigna permisos por defecto', function () {
    $role = Role::where('nombre', 'admin')->firstOrFail();
    $role->permissions()->sync([]);
    $role->ensureDefaultPermissions();

    expect($role->permissions()->count())->toBeGreaterThan(0);
});

test('admin accede a roles.index', function () {
    $user = userWithRole('admin');

    $response = $this->actingAs($user)->get(route('roles.index'));

    $response->assertOk();
});

test('cajera NO puede acceder a roles.index (403)', function () {
    $user = userWithRole('cajera');

    $response = $this->actingAs($user)->get(route('roles.index'));

    $response->assertForbidden();
});

test('admin puede crear un nuevo rol', function () {
    $user = userWithRole('admin');

    $response = $this->actingAs($user)->post(route('roles.store'), [
        'nombre' => 'supervisor',
        'descripcion' => 'Rol de supervisor',
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect(Role::where('nombre', 'supervisor')->exists())->toBeTrue();
});

test('admin puede actualizar permisos de un rol', function () {
    $user = userWithRole('admin');
    $role = Role::where('nombre', 'cajera')->firstOrFail();

    $response = $this->actingAs($user)->put(route('roles.update', $role->id), [
        'nombre' => 'cajera',
        'permisos' => ['panel.ver', 'pagos.ver', 'pagos.gestionar'],
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect($role->fresh()->permissions()->count())->toBe(3);
});

test('admin no puede eliminar un rol con usuarios asignados', function () {
    $user = userWithRole('admin');
    userWithRole('cajera');
    $role = Role::where('nombre', 'cajera')->firstOrFail();

    $response = $this->actingAs($user)->delete(route('roles.destroy', $role->id));

    expect(Role::where('nombre', 'cajera')->exists())->toBeTrue();
});

test('admin accede a usuarios.index', function () {
    $user = userWithRole('admin');

    $response = $this->actingAs($user)->get(route('usuarios.index'));

    $response->assertOk();
});

test('cajera NO puede acceder a usuarios.index (403)', function () {
    $user = userWithRole('cajera');

    $response = $this->actingAs($user)->get(route('usuarios.index'));

    $response->assertForbidden();
});

test('admin puede crear un nuevo usuario', function () {
    $user = userWithRole('admin');

    $response = $this->actingAs($user)->post(route('usuarios.store'), [
        'nombres' => 'Test',
        'apellido_paterno' => 'User',
        'apellido_materno' => 'Apellido',
        'email' => 'testuser@example.com',
        'password' => 'password123',
        'role' => 'cajera',
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect(User::where('email', 'testuser@example.com')->exists())->toBeTrue();
});

test('admin puede actualizar rol de un usuario', function () {
    $admin = userWithRole('admin');
    $target = userWithRole('cajera');

    $response = $this->actingAs($admin)->patch(route('usuarios.update', $target->id), [
        'nombres' => $target->nombres ?? 'Test',
        'apellido_paterno' => $target->apellido_paterno ?? 'User',
        'apellido_materno' => $target->apellido_materno ?? 'Apellido',
        'email' => $target->email,
        'role' => 'admin',
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect($target->fresh()->hasRole('admin'))->toBeTrue();
});

test('admin no puede eliminarse a si mismo', function () {
    $user = userWithRole('admin');

    $response = $this->actingAs($user)->delete(route('usuarios.destroy', $user->id));

    expect(User::where('id', $user->id)->exists())->toBeTrue();
});

<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;

beforeEach(fn () => $this->seed(PermissionsSeeder::class));

function dataEntryAdmin(): User
{
    $user = User::factory()->create(['role' => Role::ADMIN]);
    $role = Role::where('nombre', Role::ADMIN)->firstOrFail();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

test('proveedor rechaza letras en ci nit y telefono', function () {
    $this->actingAs(dataEntryAdmin())->post(route('proveedores.store'), [
        'nombre' => 'Proveedor inválido',
        'ci_nit' => '78898887ABC',
        'telefono' => '73828289XYZ',
        'email' => 'proveedor@example.com',
    ])->assertSessionHasErrors(['ci_nit', 'telefono']);
});

test('proveedor acepta ci nit y telefono formados solo por numeros', function () {
    $this->actingAs(dataEntryAdmin())->post(route('proveedores.store'), [
        'nombre' => 'Proveedor válido',
        'ci_nit' => '78898887',
        'telefono' => '73828289',
        'email' => 'proveedor@example.com',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('proveedores', [
        'ci_nit' => '78898887',
        'telefono' => '73828289',
    ]);
});

test('fecha de ingreso del usuario debe ser la fecha actual', function (string $fecha) {
    $this->actingAs(dataEntryAdmin())->post(route('usuarios.store'), [
        'nombres' => 'María',
        'apellido_paterno' => 'López',
        'apellido_materno' => 'Rojas',
        'email' => "maria{$fecha}@example.com",
        'password' => 'password123',
        'role' => Role::VENDEDOR,
        'telefono' => '71764552',
        'fecha_ingreso' => $fecha,
        'status' => 'activo',
    ])->assertSessionHasErrors('fecha_ingreso');
})->with([
    'fecha pasada' => fn () => now()->subDay()->toDateString(),
    'fecha futura' => fn () => now()->addDay()->toDateString(),
]);

test('usuario acepta la fecha actual como fecha de ingreso', function () {
    $this->actingAs(dataEntryAdmin())->post(route('usuarios.store'), [
        'nombres' => 'María',
        'apellido_paterno' => 'López',
        'apellido_materno' => 'Rojas',
        'email' => 'maria.actual@example.com',
        'password' => 'password123',
        'role' => Role::VENDEDOR,
        'telefono' => '71764552',
        'fecha_ingreso' => now()->toDateString(),
        'status' => 'activo',
    ])->assertSessionHasNoErrors();
});

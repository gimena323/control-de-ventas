<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;

beforeEach(fn () => $this->seed(PermissionsSeeder::class));

function usuarioConfiguracionesConRol(string $nombreRol): User
{
    $usuario = User::factory()->create(['role' => $nombreRol]);
    $rol = Role::where('nombre', $nombreRol)->firstOrFail();
    $usuario->roles()->sync([$rol->id]);

    return $usuario->fresh();
}

test('la administradora puede abrir configuraciones', function () {
    $administradora = usuarioConfiguracionesConRol('admin');

    $response = $this->actingAs($administradora)->get(route('configuraciones.index'));

    $response->assertRedirect(route('profile.edit'));
});

test('una persona sin autenticar no puede abrir configuraciones', function () {
    $response = $this->get(route('configuraciones.index'));

    $response->assertRedirect(route('login'));
});

test('la vendedora no puede abrir configuraciones administrativas', function () {
    $vendedora = usuarioConfiguracionesConRol('vendedor');

    $response = $this->actingAs($vendedora)->get(route('configuraciones.index'));

    $response->assertForbidden();
});

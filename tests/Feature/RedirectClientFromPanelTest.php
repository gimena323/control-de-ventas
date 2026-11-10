<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;

beforeEach(fn () => $this->seed(PermissionsSeeder::class));

function userCliente(): User
{
    $role = Role::firstOrCreate(
        ['nombre' => Role::CLIENTE],
        ['descripcion' => 'Cliente de la tienda']
    );
    $user = User::factory()->create(['role' => Role::CLIENTE]);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

test('cliente autenticado en / es redirigido a /catalogo', function () {
    $user = userCliente();

    $this->actingAs($user)->get('/')->assertRedirect(route('catalogo'));
});

test('cliente autenticado en /dashboard es redirigido a /catalogo', function () {
    $user = userCliente();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('catalogo'));
});

test('invitado en / no es redirigido', function () {
    $this->get('/')->assertOk();
});

test('admin en /dashboard no es redirigido', function () {
    $user = User::factory()->create(['role' => Role::ADMIN]);
    $role = Role::where('nombre', Role::ADMIN)->firstOrFail();
    $user->roles()->sync([$role->id]);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

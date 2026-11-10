<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;

beforeEach(fn () => $this->seed(PermissionsSeeder::class));

test('el cliente puede actualizar su cuenta y sus datos comerciales', function () {
    $user = User::factory()->create([
        'role' => Role::CLIENTE,
        'nombres' => 'Gimena',
        'apellido_paterno' => 'Quinaya',
        'apellido_materno' => 'Colque',
        'telefono' => '70000000',
    ]);
    $user->roles()->sync([Role::where('nombre', Role::CLIENTE)->firstOrFail()->id]);

    $response = $this->actingAs($user)->patch(route('perfil.update'), [
        'nombres' => 'Gimena',
        'apellido_paterno' => 'Quinaya',
        'apellido_materno' => 'Colque',
        'telefono' => '76543210',
        'email' => 'gimena.cliente@example.com',
        'ci_nit' => 'CI-123456',
        'direccion' => 'Eterazama, Cochabamba',
    ]);

    $response->assertRedirect(route('perfil'))->assertSessionHasNoErrors();

    $cliente = $user->fresh()->cliente;

    expect($user->fresh()->telefono)->toBe('76543210')
        ->and($cliente)->not->toBeNull()
        ->and($cliente->nombre)->toBe('Gimena Quinaya Colque')
        ->and($cliente->ci_nit)->toBe('CI-123456')
        ->and($cliente->direccion)->toBe('Eterazama, Cochabamba')
        ->and($cliente->estado)->toBe('activo');
});

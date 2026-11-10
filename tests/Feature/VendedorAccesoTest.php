<?php

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Color;
use App\Models\Compra;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\Talla;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;

beforeEach(fn () => $this->seed(PermissionsSeeder::class));

function usuarioConRolVendedor(): User
{
    $user = User::factory()->create(['role' => Role::VENDEDOR]);
    $role = Role::where('nombre', Role::VENDEDOR)->firstOrFail();
    $role->ensureDefaultPermissions();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function usuarioConRolAdministrador(): User
{
    $user = User::factory()->create(['role' => Role::ADMIN]);
    $role = Role::where('nombre', Role::ADMIN)->firstOrFail();
    $role->ensureDefaultPermissions();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function productoParaCompraDeVendedor(): Producto
{
    $categoria = Categoria::firstOrCreate(['nombre' => 'Categoria vendedor']);
    $marca = Marca::firstOrCreate(['nombre' => 'Marca vendedor']);
    $talla = Talla::firstOrCreate(['nombre' => 'XL']);
    $color = Color::firstOrCreate(['nombre' => 'Azul vendedor'], ['codigo_hex' => '#0000FF']);

    return Producto::create([
        'categoria_id' => $categoria->id,
        'marca_id' => $marca->id,
        'talla_id' => $talla->id,
        'color_id' => $color->id,
        'nombre' => 'Producto vendedor',
        'precio_venta' => 100,
        'estado' => 'activo',
    ]);
}

test('el rol vendedor recibe permisos de compras y gestion de clientes', function () {
    $vendedor = usuarioConRolVendedor();

    expect($vendedor->hasPermission('compras.ver'))->toBeTrue()
        ->and($vendedor->hasPermission('compras.crear'))->toBeTrue()
        ->and($vendedor->hasPermission('clientes.ver'))->toBeTrue()
        ->and($vendedor->hasPermission('clientes.gestion'))->toBeTrue()
        ->and($vendedor->hasPermission('usuarios.gestion'))->toBeFalse();
});

test('la vendedora puede registrar un cliente y queda identificada como quien lo registro', function () {
    $vendedor = usuarioConRolVendedor();

    $this->actingAs($vendedor)->get(route('clientes.index'))->assertOk();

    $this->actingAs($vendedor)->post(route('clientes.store'), [
        'nombre' => 'Cliente atendido por vendedora',
        'apellido' => 'Perez',
        'ci_nit' => 'VEN-001',
        'telefono' => '70000111',
    ])->assertRedirect(route('clientes.index'))->assertSessionHasNoErrors();

    $cliente = Cliente::where('ci_nit', 'VEN-001')->sole();

    expect($cliente->registrado_por_user_id)->toBe($vendedor->id)
        ->and($cliente->registradoPor->is($vendedor))->toBeTrue();
});

test('la administradora ve en el listado quien registro cada cliente', function () {
    $vendedor = usuarioConRolVendedor();
    $admin = usuarioConRolAdministrador();

    $this->actingAs($vendedor)->post(route('clientes.store'), [
        'nombre' => 'Cliente visible para admin',
        'ci_nit' => 'VEN-002',
        'telefono' => '70000222',
    ])->assertSessionHasNoErrors();

    $this->actingAs($admin)->get(route('clientes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('clientes.data.0.registrado_por.id', $vendedor->id)
            ->where('clientes.data.0.registrado_por_user_id', $vendedor->id));
});

test('la vendedora puede registrar una compra y la administradora ve quien la hizo', function () {
    $vendedor = usuarioConRolVendedor();
    $admin = usuarioConRolAdministrador();
    $proveedor = Proveedor::create([
        'nombre' => 'Proveedor vendedor',
        'ci_nit' => 'PROV-VEN-001',
        'telefono' => '70000333',
    ]);
    $producto = productoParaCompraDeVendedor();

    $this->actingAs($vendedor)->get(route('compras.index'))->assertOk();

    $this->actingAs($vendedor)->post(route('compras.store'), [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-10-28',
        'detalles' => [[
            'producto_id' => $producto->id,
            'cantidad' => 3,
            'precio_unitario' => 50,
        ]],
    ])->assertRedirect(route('compras.index'))->assertSessionHasNoErrors();

    $compra = Compra::sole();

    expect($compra->user_id)->toBe($vendedor->id);

    $this->actingAs($admin)->get(route('compras.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('compras.data.0.user.id', $vendedor->id)
            ->where('compras.data.0.user_id', $vendedor->id));
});

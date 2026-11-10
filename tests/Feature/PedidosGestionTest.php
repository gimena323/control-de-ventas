<?php

use App\Enums\Permission as PermissionEnum;
use App\Models\Categoria;
use App\Models\Color;
use App\Models\Inventario;
use App\Models\InventarioMovimiento;
use App\Models\Marca;
use App\Models\Pedido;
use App\Models\Permission;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Role;
use App\Models\Talla;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;

beforeEach(fn () => $this->seed(PermissionsSeeder::class));

function usuarioGestion(string $roleName): User
{
    $user = User::factory()->create(['role' => $roleName]);
    $role = Role::where('nombre', $roleName)->firstOrFail();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function crearPedido(): Pedido
{
    $cliente = User::factory()->create(['role' => Role::CLIENTE]);
    $cliente->roles()->sync([Role::where('nombre', Role::CLIENTE)->firstOrFail()->id]);

    $categoria = Categoria::create(['nombre' => 'Categoria '.fake()->unique()->word()]);
    $marca = Marca::create(['nombre' => 'Marca '.fake()->unique()->word()]);
    $talla = Talla::create(['nombre' => 'Talla '.fake()->unique()->word()]);
    $color = Color::create(['nombre' => 'Color '.fake()->unique()->word(), 'codigo_hex' => '#000000']);
    $producto = Producto::create([
        'categoria_id' => $categoria->id,
        'marca_id' => $marca->id,
        'talla_id' => $talla->id,
        'color_id' => $color->id,
        'nombre' => 'Blusa Test',
        'precio_venta' => 89.90,
    ]);

    Inventario::create([
        'producto_id' => $producto->id,
        'stock_actual' => 10,
        'disponible' => true,
    ]);

    $pedido = Pedido::create([
        'numero' => 'PED-GEST-0001',
        'user_id' => $cliente->id,
        'total' => 89.90,
        'estado' => Pedido::ESTADO_PENDIENTE,
        'metodo_pago' => 'Pago por QR / WhatsApp',
    ]);
    $pedido->detalles()->create([
        'producto_id' => $producto->id,
        'producto' => $producto->nombre,
        'cantidad' => 1,
        'precio' => 89.90,
        'subtotal' => 89.90,
    ]);

    return $pedido;
}

test('vendedor con permiso pedidos.ver accede a /gestion/pedidos', function () {
    $vendedor = usuarioGestion(Role::VENDEDOR);

    $this->actingAs($vendedor)->get(route('gestion.pedidos.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('PedidosAdmin/index'));
});

test('admin accede a /gestion/pedidos', function () {
    $admin = usuarioGestion(Role::ADMIN);

    $this->actingAs($admin)->get(route('gestion.pedidos.index'))->assertOk();
});

test('usuario sin permiso pedidos.ver no accede a gestion de pedidos (403)', function () {
    $encargado = usuarioGestion(Role::ENCARGADO_INVENTARIO);

    $this->actingAs($encargado)->get(route('gestion.pedidos.index'))->assertForbidden();
});

test('vendedor puede ver el detalle de un pedido', function () {
    $vendedor = usuarioGestion(Role::VENDEDOR);
    $pedido = crearPedido();

    $this->actingAs($vendedor)->get(route('gestion.pedidos.show', $pedido))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('PedidosAdmin/show')
            ->where('pedido.numero', $pedido->numero));
});

test('admin puede cambiar el estado de un pedido', function () {
    $admin = usuarioGestion(Role::ADMIN);
    $pedido = crearPedido();

    $this->actingAs($admin)->from(route('gestion.pedidos.index'))
        ->patch(route('gestion.pedidos.estado', $pedido), ['estado' => Pedido::ESTADO_CONFIRMADO])
        ->assertRedirect(route('gestion.pedidos.index'))
        ->assertSessionHasNoErrors();

    expect($pedido->fresh()->estado)->toBe(Pedido::ESTADO_CONFIRMADO);
});

test('vendedor con permiso pedidos.gestion puede confirmar un pedido', function () {
    $vendedor = usuarioGestion(Role::VENDEDOR);
    $pedido = crearPedido();

    $this->actingAs($vendedor)->post(route('gestion.pedidos.confirmar', $pedido))
        ->assertRedirect();

    $producto = Producto::findOrFail($pedido->detalles()->firstOrFail()->producto_id);

    expect($pedido->fresh()->estado)->toBe(Pedido::ESTADO_CONFIRMADO)
        ->and($producto->inventario->fresh()->stock_actual)->toBe(9)
        ->and(InventarioMovimiento::query()
            ->where('origen_tipo', 'pedido')
            ->where('origen_id', $pedido->id)
            ->where('tipo', 'salida')
            ->sum('cantidad'))->toBe(1);
});

test('confirmar un pedido descuenta solamente la talla y color comprados', function () {
    $vendedor = usuarioGestion(Role::VENDEDOR);
    $pedido = crearPedido();
    $detalle = $pedido->detalles()->firstOrFail();
    $producto = Producto::findOrFail($detalle->producto_id);
    $variante = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $producto->talla_id,
        'color_id' => $producto->color_id,
        'stock' => 1,
        'disponible' => true,
    ]);

    $detalle->update([
        'talla' => $producto->talla->nombre,
        'color' => $producto->color->nombre,
    ]);

    $this->actingAs($vendedor)->post(route('gestion.pedidos.confirmar', $pedido))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($variante->fresh()->stock)->toBe(0)
        ->and($variante->fresh()->disponible)->toBeFalse()
        ->and($producto->inventario->fresh()->stock_actual)->toBe(9);
});

test('un pedido pendiente no descuenta existencias hasta ser confirmado', function () {
    $pedido = crearPedido();
    $producto = Producto::findOrFail($pedido->detalles()->firstOrFail()->producto_id);

    expect($pedido->estado)->toBe(Pedido::ESTADO_PENDIENTE)
        ->and($producto->inventario->fresh()->stock_actual)->toBe(10)
        ->and(InventarioMovimiento::query()
            ->where('origen_tipo', 'pedido')
            ->where('origen_id', $pedido->id)
            ->exists())->toBeFalse();
});

test('vendedor con permiso pedidos.gestion puede marcar un pedido como entregado', function () {
    $vendedor = usuarioGestion(Role::VENDEDOR);
    $pedido = crearPedido();
    $pedido->update(['estado' => Pedido::ESTADO_EN_PREPARACION]);

    $this->actingAs($vendedor)->post(route('gestion.pedidos.entregar', $pedido))
        ->assertRedirect();

    expect($pedido->fresh()->estado)->toBe(Pedido::ESTADO_ENTREGADO);
});

test('vendedor con permiso pedidos.gestion puede cancelar un pedido pendiente', function () {
    $vendedor = usuarioGestion(Role::VENDEDOR);
    $pedido = crearPedido();

    $this->actingAs($vendedor)->post(route('gestion.pedidos.cancelar', $pedido))
        ->assertRedirect();

    expect($pedido->fresh()->estado)->toBe(Pedido::ESTADO_CANCELADO);
});

test('no se puede cancelar un pedido ya entregado', function () {
    $admin = usuarioGestion(Role::ADMIN);
    $pedido = crearPedido();
    $pedido->update(['estado' => Pedido::ESTADO_ENTREGADO]);

    $this->actingAs($admin)->post(route('gestion.pedidos.cancelar', $pedido))
        ->assertSessionHasErrors('pedido');

    expect($pedido->fresh()->estado)->toBe(Pedido::ESTADO_ENTREGADO);
});

test('usuario sin permiso pedidos.gestion no puede modificar estados (403)', function () {
    $vendedor = usuarioGestion(Role::VENDEDOR);
    $pedido = crearPedido();
    $permisoGestion = Permission::where('nombre', PermissionEnum::PedidosGestion->value)->firstOrFail();
    $vendedor->roles()->first()->permissions()->detach($permisoGestion->id);

    $this->actingAs($vendedor->fresh())
        ->patch(route('gestion.pedidos.estado', $pedido), ['estado' => Pedido::ESTADO_CONFIRMADO])
        ->assertForbidden();

    expect($pedido->fresh()->estado)->toBe(Pedido::ESTADO_PENDIENTE);
});

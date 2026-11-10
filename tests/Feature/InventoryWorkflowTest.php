<?php

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Color;
use App\Models\Compra;
use App\Models\Inventario;
use App\Models\InventarioMovimiento;
use App\Models\Marca;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\Talla;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\PermissionsSeeder;

beforeEach(fn () => $this->seed(PermissionsSeeder::class));

function inventoryWorkflowAdmin(): User
{
    $user = User::factory()->create(['role' => Role::ADMIN]);
    $role = Role::where('nombre', Role::ADMIN)->firstOrFail();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function inventoryWorkflowProduct(): Producto
{
    $producto = Producto::create([
        'categoria_id' => Categoria::create(['nombre' => 'Camisas'])->id,
        'marca_id' => Marca::create(['nombre' => 'Shein'])->id,
        'talla_id' => Talla::create(['nombre' => 'L'])->id,
        'color_id' => Color::create(['nombre' => 'Azul Rey', 'codigo_hex' => '#0033CC'])->id,
        'nombre' => 'Camisa Cuello V',
        'precio_venta' => 80,
        'estado' => 'activo',
        'genero' => 'femenino',
    ]);

    Inventario::create(['producto_id' => $producto->id, 'stock_actual' => 0, 'disponible' => true]);

    return $producto;
}

test('una compra genera una entrada y aumenta las existencias', function () {
    $admin = inventoryWorkflowAdmin();
    $admin->update([
        'name' => 'Gimena Quinaya',
        'nombres' => null,
        'apellido_paterno' => null,
        'apellido_materno' => null,
    ]);
    $producto = inventoryWorkflowProduct();
    $proveedor = Proveedor::create(['nombre' => 'Proveedor Moda', 'ci_nit' => '123456']);

    $this->actingAs($admin)->post(route('compras.store'), [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-08-11',
        'detalles' => [['producto_id' => $producto->id, 'cantidad' => 10, 'precio_unitario' => 50, 'subtotal' => 1]],
    ])->assertRedirect(route('compras.index'))->assertSessionHasNoErrors();

    $movimiento = InventarioMovimiento::sole();
    expect($producto->inventario->fresh()->stock_actual)->toBe(10)
        ->and($producto->inventario->fresh()->disponible)->toBeTrue()
        ->and($movimiento->tipo)->toBe('entrada')
        ->and($movimiento->cantidad)->toBe(10)
        ->and($movimiento->origen_tipo)->toBe('compra');

    $this->assertDatabaseHas('compras', ['total' => 500]);

    $this->actingAs($admin)->get(route('compras.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('compras.data.0.user.name', 'Gimena Quinaya'));
});

test('una venta genera una salida y rechaza cantidades mayores a las existencias', function () {
    $admin = inventoryWorkflowAdmin();
    $producto = inventoryWorkflowProduct();
    $producto->inventario->update(['stock_actual' => 10, 'disponible' => true]);
    $cliente = Cliente::create(['nombre' => 'Gimena', 'ci_nit' => '987654']);

    $payload = [
        'cliente_id' => $cliente->id,
        'fecha' => '2026-08-11',
        'detalles' => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio_unitario' => 80, 'descuento' => 0]],
    ];

    $this->actingAs($admin)->post(route('ventas.store'), $payload)
        ->assertRedirect(route('ventas.index'))->assertSessionHasNoErrors();

    expect($producto->inventario->fresh()->stock_actual)->toBe(8)
        ->and(InventarioMovimiento::sole()->tipo)->toBe('salida')
        ->and(InventarioMovimiento::sole()->origen_tipo)->toBe('venta');

    $payload['detalles'][0]['cantidad'] = 9;
    $this->actingAs($admin)->post(route('ventas.store'), $payload)->assertSessionHasErrors('detalles');

    expect($producto->inventario->fresh()->stock_actual)->toBe(8)
        ->and(Venta::count())->toBe(1)
        ->and(InventarioMovimiento::count())->toBe(1);
});

test('el inventario calcula movimientos y filtros sin permitir operaciones manuales', function () {
    $admin = inventoryWorkflowAdmin();
    $producto = inventoryWorkflowProduct();
    $inventario = $producto->inventario;
    $inventario->update(['stock_actual' => 4, 'disponible' => true]);
    $proveedor = Proveedor::create(['nombre' => 'Proveedor Inventario', 'ci_nit' => '123123']);
    $cliente = Cliente::create(['nombre' => 'Cliente Inventario', 'ci_nit' => '456456']);
    $compra = Compra::create([
        'proveedor_id' => $proveedor->id,
        'user_id' => $admin->id,
        'numero_compra' => 'CMP-TEST-0001',
        'total' => 10,
        'fecha' => '2026-08-11',
    ]);
    $venta = Venta::create([
        'cliente_id' => $cliente->id,
        'user_id' => $admin->id,
        'numero_venta' => 'VTA-TEST-0001',
        'total' => 6,
        'fecha' => '2026-08-11',
        'estado' => 'completada',
    ]);
    $inventario->movimientos()->createMany([
        ['tipo' => 'entrada', 'cantidad' => 10, 'motivo' => 'Compra', 'origen_tipo' => 'compra', 'origen_id' => $compra->id, 'user_id' => $admin->id],
        ['tipo' => 'salida', 'cantidad' => 6, 'motivo' => 'Venta', 'origen_tipo' => 'venta', 'origen_id' => $venta->id, 'user_id' => $admin->id],
    ]);

    $this->actingAs($admin)->get(route('inventario.index', ['search' => 'Camisa']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Inventario/index')
            ->has('inventario', 1)
            ->where('inventario.0.total_entradas', 10)
            ->where('inventario.0.total_salidas', 6));

    $stockAntes = $inventario->stock_actual;
    $movimientosAntes = InventarioMovimiento::count();

    $this->actingAs($admin)->patch(route('inventario.disponibilidad.update', $inventario), [
        'disponible' => false,
    ])->assertRedirect(route('inventario.index'));

    expect($inventario->fresh()->disponible)->toBeFalse()
        ->and($inventario->fresh()->stock_actual)->toBe($stockAntes)
        ->and(InventarioMovimiento::count())->toBe($movimientosAntes);

    $this->actingAs($admin)->patch(route('inventario.disponibilidad.update', $inventario), [
        'disponible' => true,
    ])->assertRedirect(route('inventario.index'));

    expect($inventario->fresh()->disponible)->toBeTrue();

    $this->actingAs($admin)->post('/inventario')->assertMethodNotAllowed();
});

test('el inventario muestra todas las tallas configuradas para una prenda', function () {
    $admin = inventoryWorkflowAdmin();
    $producto = inventoryWorkflowProduct();
    $tallaM = Talla::firstOrCreate(['nombre' => 'M']);
    $tallaS = Talla::firstOrCreate(['nombre' => 'S']);

    $producto->variantes()->createMany([
        ['talla_id' => $producto->talla_id, 'color_id' => $producto->color_id, 'stock' => 0, 'disponible' => true],
        ['talla_id' => $tallaM->id, 'color_id' => $producto->color_id, 'stock' => 3, 'disponible' => true],
        ['talla_id' => $tallaS->id, 'color_id' => $producto->color_id, 'stock' => 3, 'disponible' => true],
    ]);

    $this->actingAs($admin)->get(route('inventario.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('inventario', 1)
            ->has('inventario.0.producto.variantes', 3)
            ->where('inventario.0.producto.variantes', function ($variantes): bool {
                $stockPorTalla = collect($variantes)
                    ->mapWithKeys(fn (array $variante): array => [
                        $variante['talla']['nombre'] => $variante['stock'],
                    ])
                    ->sortKeys();

                return $stockPorTalla->all() === ['L' => 0, 'M' => 3, 'S' => 3];
            }));

    $this->actingAs($admin)->get(route('inventario.index', ['search' => 'M']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('inventario', 1));
});

test('el botón general puede consultar todos los movimientos del inventario', function () {
    $admin = inventoryWorkflowAdmin();
    $producto = inventoryWorkflowProduct();
    $inventario = $producto->inventario;
    $proveedor = Proveedor::create(['nombre' => 'Proveedor General', 'ci_nit' => '987123']);
    $compra = Compra::create([
        'proveedor_id' => $proveedor->id,
        'user_id' => $admin->id,
        'numero_compra' => 'CMP-GENERAL-0001',
        'total' => 250,
        'fecha' => '2026-10-07',
    ]);

    $inventario->movimientos()->create([
        'tipo' => 'entrada',
        'cantidad' => 5,
        'motivo' => 'Compra general',
        'origen_tipo' => 'compra',
        'origen_id' => $compra->id,
        'user_id' => $admin->id,
    ]);

    $this->actingAs($admin)->get(route('inventario.movimientos.todos'))
        ->assertOk()
        ->assertJsonCount(1, 'movimientos')
        ->assertJsonPath('movimientos.0.producto', $producto->nombre)
        ->assertJsonPath('movimientos.0.tipo', 'entrada')
        ->assertJsonPath('movimientos.0.cantidad', 5);
});

test('la pantalla de inventario muestra solo el acceso general a movimientos', function () {
    $source = file_get_contents(resource_path('js/pages/Inventario/index.vue'));

    expect($source)
        ->toContain('Ver movimientos')
        ->not->toContain('Informes')
        ->not->toContain('Acciones')
        ->not->toContain('openMovimientos(item)');
});

test('la pantalla de inventario muestra las tallas con su stock por talla', function () {
    $source = file_get_contents(resource_path('js/pages/Inventario/index.vue'));

    expect($source)
        ->toContain('productSizes(item)')
        ->toContain('{{ size }}')
        ->toContain('sizeStock(item, size)')
        ->toContain('Stock total');
});

test('el inventario no muestra movimientos de compras o ventas eliminadas', function () {
    $admin = inventoryWorkflowAdmin();
    $inventario = inventoryWorkflowProduct()->inventario;
    $cliente = Cliente::create(['nombre' => 'Cliente Anulado', 'ci_nit' => '789789']);
    $ventaAnulada = Venta::create([
        'cliente_id' => $cliente->id,
        'user_id' => $admin->id,
        'numero_venta' => 'VTA-ANULADA-0001',
        'total' => 3,
        'fecha' => '2026-08-11',
        'estado' => 'anulada',
    ]);
    $inventario->movimientos()->createMany([
        ['tipo' => 'entrada', 'cantidad' => 3, 'motivo' => 'Compra eliminada', 'origen_tipo' => 'compra', 'origen_id' => 999, 'user_id' => $admin->id],
        ['tipo' => 'salida', 'cantidad' => 3, 'motivo' => 'Venta eliminada', 'origen_tipo' => 'venta', 'origen_id' => 999, 'user_id' => $admin->id],
        ['tipo' => 'salida', 'cantidad' => 3, 'motivo' => 'Venta anulada', 'origen_tipo' => 'venta', 'origen_id' => $ventaAnulada->id, 'user_id' => $admin->id],
        ['tipo' => 'entrada', 'cantidad' => 3, 'motivo' => 'Anulación de venta', 'origen_tipo' => 'anulacion_venta', 'origen_id' => $ventaAnulada->id, 'user_id' => $admin->id],
    ]);

    $this->actingAs($admin)->get(route('inventario.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('inventario.0.total_entradas', 0)
            ->where('inventario.0.total_salidas', 0));

    $this->actingAs($admin)->get(route('inventario.movimientos', $inventario))
        ->assertOk()
        ->assertJsonCount(0, 'movimientos');
});

test('el inventario no cuenta pedidos pendientes como salidas realizadas', function () {
    $admin = inventoryWorkflowAdmin();
    $producto = inventoryWorkflowProduct();
    $inventario = $producto->inventario;
    $pedido = Pedido::create([
        'numero' => 'PED-PENDIENTE-0001',
        'user_id' => $admin->id,
        'total' => 480,
        'estado' => Pedido::ESTADO_PENDIENTE,
    ]);

    $inventario->movimientos()->create([
        'tipo' => 'salida',
        'cantidad' => 6,
        'motivo' => 'Pedido pendiente',
        'origen_tipo' => 'pedido',
        'origen_id' => $pedido->id,
        'user_id' => $admin->id,
    ]);

    $this->actingAs($admin)->get(route('inventario.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('inventario.0.total_salidas', 0));

    $this->actingAs($admin)->get(route('inventario.movimientos', $inventario))
        ->assertOk()
        ->assertJsonCount(0, 'movimientos');
});

test('no permite marcar como disponible un producto sin stock', function () {
    $admin = inventoryWorkflowAdmin();
    $inventario = inventoryWorkflowProduct()->inventario;
    $inventario->update(['stock_actual' => 0, 'disponible' => false]);

    $this->actingAs($admin)->patch(route('inventario.disponibilidad.update', $inventario), [
        'disponible' => true,
    ])->assertRedirect(route('inventario.index'))
        ->assertSessionHas('inertia.flash_data.toast', [
            'type' => 'error',
            'message' => 'No se puede marcar como disponible un producto sin stock.',
        ]);

    expect($inventario->fresh()->stock_actual)->toBe(0)
        ->and($inventario->fresh()->disponible)->toBeFalse();
});

test('la disponibilidad se puede alternar cuando existe stock', function () {
    $admin = inventoryWorkflowAdmin();
    $inventario = inventoryWorkflowProduct()->inventario;
    $inventario->update(['stock_actual' => 4, 'disponible' => false]);

    $this->actingAs($admin)->patch(route('inventario.disponibilidad.update', $inventario), [
        'disponible' => true,
    ])->assertRedirect(route('inventario.index'));

    expect($inventario->fresh()->disponible)->toBeTrue();
});

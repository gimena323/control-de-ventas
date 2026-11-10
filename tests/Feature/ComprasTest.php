<?php

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Color;
use App\Models\Compra;
use App\Models\Inventario;
use App\Models\InventarioMovimiento;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\Talla;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;

beforeEach(fn () => $this->seed(PermissionsSeeder::class));

test('desactivar un proveedor conserva compras detalles movimientos e inventario', function () {
    $admin = comprasUsuarioAdmin();
    $proveedor = comprasProveedor();
    $producto = comprasProducto();
    $payload = [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-09-15',
        'detalles' => [['producto_id' => $producto->id, 'cantidad' => 7, 'precio_unitario' => 80]],
    ];

    $this->actingAs($admin)->post(route('compras.store'), $payload)->assertSessionHasNoErrors();
    $compra = Compra::sole();
    $detalleId = $compra->detalles->sole()->id;
    $movimientos = InventarioMovimiento::all()->toArray();

    $this->delete(route('proveedores.destroy', $proveedor))->assertRedirect(route('proveedores.index'));

    $this->assertSoftDeleted('proveedores', ['id' => $proveedor->id]);
    $this->assertDatabaseHas('compras', ['id' => $compra->id, 'total' => 560]);
    $this->assertDatabaseHas('compra_detalles', ['id' => $detalleId, 'cantidad' => 7]);
    expect($compra->fresh()->proveedor->nombre)->toBe($proveedor->nombre)
        ->and(Inventario::where('producto_id', $producto->id)->sole()->stock_actual)->toBe(7)
        ->and(InventarioMovimiento::all()->toArray())->toBe($movimientos);

    $this->get(route('compras.index'))->assertInertia(fn ($page) => $page
        ->has('compras.data', 1)
        ->where('compras.data.0.proveedor.nombre', $proveedor->nombre)
        ->has('proveedores', 0));
    $this->get(route('proveedores.index'))->assertInertia(fn ($page) => $page->has('proveedores.data', 0));
    $this->get(route('reportes.compras', ['proveedor_id' => $proveedor->id]))
        ->assertInertia(fn ($page) => $page
            ->has('proveedores', 1)
            ->where('proveedores.0.nombre', $proveedor->nombre));

    $this->patch(route('compras.update', $compra), $payload)->assertSessionHasNoErrors();
    expect($compra->fresh()->proveedor_id)->toBe($proveedor->id);
});

test('un proveedor desactivado no permite registrar nuevas compras', function () {
    $admin = comprasUsuarioAdmin();
    $proveedor = comprasProveedor();
    $producto = comprasProducto();
    $proveedor->delete();

    $this->actingAs($admin)->post(route('compras.store'), [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-09-15',
        'detalles' => [['producto_id' => $producto->id, 'cantidad' => 7, 'precio_unitario' => 80]],
    ])->assertSessionHasErrors('proveedor_id');

    expect(Compra::count())->toBe(0)
        ->and(InventarioMovimiento::count())->toBe(0);
});

function comprasUsuarioAdmin(): User
{
    $user = User::factory()->create(['role' => Role::ADMIN]);
    $role = Role::where('nombre', Role::ADMIN)->firstOrFail();
    $role->ensureDefaultPermissions();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function comprasProveedor(): Proveedor
{
    return Proveedor::create([
        'nombre' => 'Proveedor de Prueba',
        'ci_nit' => '123456789',
        'telefono' => '77777777',
    ]);
}

function comprasProducto(): Producto
{
    $categoria = Categoria::create(['nombre' => 'Poleras']);
    $marca = Marca::create(['nombre' => 'Galaxy']);
    $talla = Talla::create(['nombre' => 'S']);
    $color = Color::create(['nombre' => 'Verde Botella', 'codigo_hex' => '#2E5E1E']);

    return Producto::create([
        'categoria_id' => $categoria->id,
        'marca_id' => $marca->id,
        'talla_id' => $talla->id,
        'color_id' => $color->id,
        'nombre' => 'polera',
        'precio_venta' => 90.00,
        'estado' => 'activo',
    ]);
}

test('una compra con la misma variante enviada dos veces es rechazada por el backend', function () {
    $admin = comprasUsuarioAdmin();
    $proveedor = comprasProveedor();
    $producto = comprasProducto();

    $this->actingAs($admin)->post(route('compras.store'), [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-08-31',
        'detalles' => [
            [
                'producto_id' => $producto->id,
                'cantidad' => 2,
                'precio_unitario' => 85.00,
            ],
            [
                'producto_id' => $producto->id,
                'cantidad' => 3,
                'precio_unitario' => 85.00,
            ],
        ],
    ])->assertSessionHasErrors('detalles.1.producto_id');

    expect(Compra::count())->toBe(0);
});

test('una compra con dos variantes distintas crea una fila por cada una', function () {
    $admin = comprasUsuarioAdmin();
    $proveedor = comprasProveedor();
    $productoA = comprasProducto();

    $marcaB = Marca::create(['nombre' => 'Scraper']);
    $productoB = Producto::create([
        'categoria_id' => $productoA->categoria_id,
        'marca_id' => $marcaB->id,
        'talla_id' => $productoA->talla_id,
        'color_id' => $productoA->color_id,
        'nombre' => 'polera',
        'precio_venta' => 95.00,
        'estado' => 'activo',
    ]);

    $this->actingAs($admin)->post(route('compras.store'), [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-08-31',
        'detalles' => [
            [
                'producto_id' => $productoA->id,
                'cantidad' => 2,
                'precio_unitario' => 85.00,
            ],
            [
                'producto_id' => $productoB->id,
                'cantidad' => 4,
                'precio_unitario' => 90.00,
            ],
        ],
    ])->assertRedirect(route('compras.index'))->assertSessionHasNoErrors();

    $compra = Compra::first();
    expect($compra->detalles)->toHaveCount(2);
    expect($compra->total)->toBe('530.00');
    expect($compra->detalles->first()->talla)->toBe('S')
        ->and($compra->detalles->first()->color)->toBe('Verde Botella');
});

test('una compra vuelve disponible el inventario de un producto sin existencias', function () {
    $admin = comprasUsuarioAdmin();
    $proveedor = comprasProveedor();
    $producto = comprasProducto();
    $variante = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $producto->talla_id,
        'color_id' => $producto->color_id,
        'stock' => 0,
        'disponible' => false,
    ]);
    Inventario::create([
        'producto_id' => $producto->id,
        'stock_actual' => 0,
        'disponible' => false,
    ]);

    $this->actingAs($admin)->post(route('compras.store'), [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-10-14',
        'detalles' => [[
            'producto_id' => $producto->id,
            'producto_variante_id' => $variante->id,
            'cantidad' => 3,
            'precio_unitario' => 80,
        ]],
    ])->assertRedirect(route('compras.index'))->assertSessionHasNoErrors();

    $inventario = Inventario::where('producto_id', $producto->id)->sole();

    expect($inventario->stock_actual)->toBe(3)
        ->and($inventario->disponible)->toBeTrue()
        ->and($variante->fresh()->stock)->toBe(3)
        ->and($variante->fresh()->disponible)->toBeTrue();
});

test('una compra registra varias tallas del mismo producto con cantidades independientes', function () {
    $admin = comprasUsuarioAdmin();
    $proveedor = comprasProveedor();
    $producto = comprasProducto();
    $tallaM = Talla::firstOrCreate(['nombre' => 'M']);
    $tallaL = Talla::firstOrCreate(['nombre' => 'L']);
    $varianteS = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $producto->talla_id,
        'color_id' => $producto->color_id,
        'stock' => 0,
        'disponible' => false,
    ]);
    $varianteM = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $tallaM->id,
        'color_id' => $producto->color_id,
        'stock' => 0,
        'disponible' => false,
    ]);
    $varianteL = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $tallaL->id,
        'color_id' => $producto->color_id,
        'stock' => 0,
        'disponible' => false,
    ]);

    $this->actingAs($admin)->post(route('compras.store'), [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-10-14',
        'detalles' => [
            ['producto_id' => $producto->id, 'producto_variante_id' => $varianteS->id, 'cantidad' => 2, 'precio_unitario' => 80],
            ['producto_id' => $producto->id, 'producto_variante_id' => $varianteM->id, 'cantidad' => 3, 'precio_unitario' => 80],
            ['producto_id' => $producto->id, 'producto_variante_id' => $varianteL->id, 'cantidad' => 1, 'precio_unitario' => 80],
        ],
    ])->assertRedirect(route('compras.index'))->assertSessionHasNoErrors();

    $compra = Compra::sole();
    expect($compra->detalles)->toHaveCount(3)
        ->and($compra->detalles->pluck('talla')->all())->toBe(['S', 'M', 'L'])
        ->and($varianteS->fresh()->stock)->toBe(2)
        ->and($varianteM->fresh()->stock)->toBe(3)
        ->and($varianteL->fresh()->stock)->toBe(1)
        ->and(Inventario::where('producto_id', $producto->id)->sole()->stock_actual)->toBe(6);
});

test('la lista de compras muestra producto talla color y cantidad comprada', function () {
    $admin = comprasUsuarioAdmin();
    $proveedor = comprasProveedor();
    $producto = comprasProducto();

    $this->actingAs($admin)->post(route('compras.store'), [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-09-28',
        'detalles' => [[
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => 80,
        ]],
    ])->assertSessionHasNoErrors();

    $this->actingAs($admin)->get(route('compras.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('compras.data.0.detalles.0.producto', 'polera')
            ->where('compras.data.0.detalles.0.talla', 'S')
            ->where('compras.data.0.detalles.0.color', 'Verde Botella')
            ->where('compras.data.0.detalles.0.cantidad', 2));
});

test('la edición carga los datos reales y guarda la nueva cantidad actualizando el inventario', function () {
    $admin = comprasUsuarioAdmin();
    $proveedor = comprasProveedor();
    $producto = comprasProducto();

    $this->actingAs($admin)->post(route('compras.store'), [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-09-03',
        'detalles' => [[
            'producto_id' => $producto->id,
            'cantidad' => 10,
            'precio_unitario' => 84,
        ]],
    ])->assertSessionHasNoErrors();

    $compra = Compra::sole();

    $this->actingAs($admin)->get(route('compras.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('compras.data.0.numero_compra', $compra->numero_compra)
            ->where('compras.data.0.proveedor_id', $proveedor->id)
            ->where('compras.data.0.detalles.0.producto_id', $producto->id)
            ->where('compras.data.0.detalles.0.cantidad', 10));

    $this->actingAs($admin)->patch(route('compras.update', $compra), [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-09-04',
        'detalles' => [[
            'producto_id' => $producto->id,
            'cantidad' => 7,
            'precio_unitario' => 80,
        ]],
    ])->assertRedirect(route('compras.index'))->assertSessionHasNoErrors();

    expect($compra->fresh()->fecha->toDateString())->toBe('2026-09-04')
        ->and($compra->fresh()->total)->toBe('560.00')
        ->and($compra->fresh()->detalles->sole()->cantidad)->toBe(7)
        ->and(Inventario::where('producto_id', $producto->id)->sole()->stock_actual)->toBe(7);
});

test('el proveedor solo cuenta y lista las compras no anuladas', function () {
    $admin = comprasUsuarioAdmin();
    $proveedor = comprasProveedor();
    $producto = comprasProducto();

    foreach (['2026-09-01', '2026-09-02'] as $fecha) {
        $this->actingAs($admin)->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'fecha' => $fecha,
            'detalles' => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio_unitario' => 80]],
        ])->assertSessionHasNoErrors();
    }

    $this->actingAs($admin)->post(route('compras.anular', Compra::query()->orderByDesc('id')->first()))
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)->get(route('proveedores.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('proveedores.data.0.compras_count', 1));

    $this->actingAs($admin)->get(route('proveedores.show', $proveedor))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('proveedor.compras_count', 1)
            ->has('proveedor.compras', 1));
});

test('anular una compra conserva sus datos y revierte sus existencias', function () {
    $admin = comprasUsuarioAdmin();
    $proveedor = comprasProveedor();
    $producto = comprasProducto();

    $this->actingAs($admin)->post(route('compras.store'), [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-09-03',
        'detalles' => [[
            'producto_id' => $producto->id,
            'cantidad' => 7,
            'precio_unitario' => 80,
        ]],
    ])->assertSessionHasNoErrors();

    $compra = Compra::sole();

    $this->actingAs($admin)->post(route('compras.anular', $compra))
        ->assertRedirect(route('compras.index'))
        ->assertSessionHasNoErrors();

    expect(Compra::count())->toBe(1)
        ->and($compra->fresh()->estado)->toBe('anulada')
        ->and($compra->fresh()->anulada_por_user_id)->toBe($admin->id)
        ->and($compra->fresh()->anulada_en)->not->toBeNull()
        ->and($compra->fresh()->detalles)->toHaveCount(1)
        ->and(Inventario::where('producto_id', $producto->id)->sole()->stock_actual)->toBe(0);

    $this->actingAs($admin)->get(route('compras.index'))
        ->assertInertia(fn ($page) => $page->has('compras.data', 0));

    $this->actingAs($admin)->post(route('compras.anular', $compra))
        ->assertSessionHasErrors('compra');
});

test('una compra con existencias vendidas muestra una advertencia y no se anula', function () {
    $admin = comprasUsuarioAdmin();
    $proveedor = comprasProveedor();
    $producto = comprasProducto();

    $this->actingAs($admin)->post(route('compras.store'), [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-09-03',
        'detalles' => [[
            'producto_id' => $producto->id,
            'cantidad' => 6,
            'precio_unitario' => 80,
        ]],
    ])->assertSessionHasNoErrors();

    $compra = Compra::sole();
    $producto->inventario()->update(['stock_actual' => 5]);

    $this->actingAs($admin)->post(route('compras.anular', $compra))
        ->assertRedirect(route('compras.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('inertia.flash_data.toast', [
            'type' => 'warning',
            'message' => 'No se puede anular esta compra porque parte de sus productos ya fue vendida. La compra permanecerá registrada.',
        ]);

    expect($compra->fresh()->estado)->toBe('completada')
        ->and($compra->fresh()->anulada_en)->toBeNull()
        ->and($producto->inventario->fresh()->stock_actual)->toBe(5);
});

test('una compra consumida puede aumentar de seis a siete guardando solo la diferencia', function () {
    $admin = comprasUsuarioAdmin();
    $proveedor = comprasProveedor();
    $producto = comprasProducto();
    $cliente = Cliente::create(['nombre' => 'Cliente de prueba', 'ci_nit' => '987654']);

    $this->actingAs($admin)->post(route('compras.store'), [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-09-06',
        'detalles' => [[
            'producto_id' => $producto->id,
            'cantidad' => 6,
            'precio_unitario' => 140,
        ]],
    ])->assertSessionHasNoErrors();

    $compra = Compra::sole();

    $this->actingAs($admin)->post(route('ventas.store'), [
        'cliente_id' => $cliente->id,
        'fecha' => '2026-09-06',
        'detalles' => [[
            'producto_id' => $producto->id,
            'cantidad' => 6,
            'precio_unitario' => 150,
            'descuento' => 0,
        ]],
    ])->assertSessionHasNoErrors();

    expect($producto->inventario->fresh()->stock_actual)->toBe(0);

    $this->actingAs($admin)->patch(route('compras.update', $compra), [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-09-06',
        'detalles' => [[
            'producto_id' => $producto->id,
            'cantidad' => 7,
            'precio_unitario' => 140,
        ]],
    ])->assertRedirect(route('compras.index'))->assertSessionHasNoErrors();

    expect($compra->fresh()->detalles->sole()->cantidad)->toBe(7)
        ->and($compra->fresh()->total)->toBe('980.00')
        ->and($producto->inventario->fresh()->stock_actual)->toBe(1)
        ->and(InventarioMovimiento::query()
            ->where('origen_tipo', 'compra')
            ->where('origen_id', $compra->id)
            ->sum('cantidad'))->toBe(7);
});

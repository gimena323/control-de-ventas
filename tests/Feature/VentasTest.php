<?php

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Color;
use App\Models\Compra;
use App\Models\Inventario;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\Talla;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\PermissionsSeeder;

beforeEach(fn () => $this->seed(PermissionsSeeder::class));

function ventasUsuario(string $roleName): User
{
    $user = User::factory()->create(['role' => $roleName]);
    $role = Role::where('nombre', $roleName)->firstOrFail();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function crearProductoConStock(int $stock = 35): Producto
{
    $categoria = Categoria::create(['nombre' => 'Camisas']);
    $marca = Marca::create(['nombre' => 'Shein']);
    $talla = Talla::create(['nombre' => 'L']);
    $color = Color::create(['nombre' => 'Azul Rey', 'codigo_hex' => '#0033CC']);

    $producto = Producto::create([
        'categoria_id' => $categoria->id,
        'marca_id' => $marca->id,
        'talla_id' => $talla->id,
        'color_id' => $color->id,
        'nombre' => 'camisas',
        'precio_venta' => 80.00,
        'estado' => 'activo',
    ]);

    Inventario::create([
        'producto_id' => $producto->id,
        'stock_actual' => $stock,
        'disponible' => true,
    ]);

    return $producto;
}

test('la vendedora puede registrar una venta y el stock se descuenta automaticamente', function () {
    $kenia = ventasUsuario(Role::VENDEDOR);
    $gimena = Cliente::create([
        'nombre' => 'gimena',
        'apellido' => null,
        'ci_nit' => '99939',
        'telefono' => '3838883',
    ]);
    $producto = crearProductoConStock(35);

    $response = $this->actingAs($kenia)->post(route('ventas.store'), [
        'cliente_id' => $gimena->id,
        'fecha' => '2026-08-10',
        'detalles' => [
            [
                'producto_id' => $producto->id,
                'cantidad' => 6,
                'precio_unitario' => 80.00,
                'descuento' => 0,
            ],
        ],
    ]);

    $response->assertRedirect(route('ventas.index'))->assertSessionHasNoErrors();

    $venta = Venta::first();
    expect($venta)->not->toBeNull();
    expect($venta->cliente_id)->toBe($gimena->id);
    expect($venta->user_id)->toBe($kenia->id);
    expect($venta->total)->toBe('480.00');
    expect($venta->estado)->toBe('completada');
    expect($venta->numero_venta)->toStartWith('VNT-');

    expect($venta->detalles)->toHaveCount(1);
    expect($venta->detalles->first()->producto)->toBe('camisas');
    expect($venta->detalles->first()->cantidad)->toBe(6);

    expect($producto->inventario->fresh()->stock_actual)->toBe(29);
});

test('la venta descuenta la talla y el color seleccionados sin afectar otras variantes', function () {
    $vendedora = ventasUsuario(Role::VENDEDOR);
    $cliente = Cliente::create([
        'nombre' => 'Cliente Variantes',
        'ci_nit' => 'VAR-001',
        'telefono' => '70000001',
    ]);
    $producto = crearProductoConStock(6);
    $tallaS = Talla::firstOrCreate(['nombre' => 'S']);
    $tallaM = Talla::firstOrCreate(['nombre' => 'M']);
    $colorCafe = Color::create(['nombre' => 'Café', 'codigo_hex' => '#795548']);
    $colorRosa = Color::create(['nombre' => 'Rosa Bebé', 'codigo_hex' => '#F4B6C2']);
    $varianteS = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $tallaS->id,
        'color_id' => $colorCafe->id,
        'stock' => 3,
        'disponible' => true,
    ]);
    $varianteM = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $tallaM->id,
        'color_id' => $colorRosa->id,
        'stock' => 3,
        'disponible' => true,
    ]);

    $this->actingAs($vendedora)->post(route('ventas.store'), [
        'cliente_id' => $cliente->id,
        'fecha' => '2026-10-19',
        'detalles' => [[
            'producto_id' => $producto->id,
            'producto_variante_id' => $varianteM->id,
            'cantidad' => 2,
            'precio_unitario' => 80,
            'descuento' => 0,
        ]],
    ])->assertRedirect(route('ventas.index'))->assertSessionHasNoErrors();

    $detalle = Venta::sole()->detalles()->sole();

    expect($detalle->producto_variante_id)->toBe($varianteM->id)
        ->and($detalle->talla)->toBe('M')
        ->and($detalle->color)->toBe('Rosa Bebé')
        ->and($varianteM->fresh()->stock)->toBe(1)
        ->and($varianteS->fresh()->stock)->toBe(3)
        ->and($producto->inventario->fresh()->stock_actual)->toBe(4);
});

test('la venta rechaza una variante que pertenece a otro producto', function () {
    $vendedora = ventasUsuario(Role::VENDEDOR);
    $cliente = Cliente::create(['nombre' => 'Cliente', 'ci_nit' => 'VAR-002']);
    $producto = crearProductoConStock(3);
    $otroProducto = Producto::create([
        'categoria_id' => $producto->categoria_id,
        'marca_id' => $producto->marca_id,
        'talla_id' => $producto->talla_id,
        'color_id' => $producto->color_id,
        'nombre' => 'camisa diferente',
        'precio_venta' => 90,
        'estado' => 'activo',
    ]);
    Inventario::create([
        'producto_id' => $otroProducto->id,
        'stock_actual' => 3,
        'disponible' => true,
    ]);
    $varianteAjena = ProductoVariante::create([
        'producto_id' => $otroProducto->id,
        'talla_id' => $otroProducto->talla_id,
        'color_id' => $otroProducto->color_id,
        'stock' => 3,
        'disponible' => true,
    ]);

    $this->actingAs($vendedora)->post(route('ventas.store'), [
        'cliente_id' => $cliente->id,
        'fecha' => '2026-10-19',
        'detalles' => [[
            'producto_id' => $producto->id,
            'producto_variante_id' => $varianteAjena->id,
            'cantidad' => 1,
            'precio_unitario' => 80,
            'descuento' => 0,
        ]],
    ])->assertSessionHasErrors('detalles.0.producto_variante_id');

    expect(Venta::count())->toBe(0);
});

test('la vendedora puede registrar un cliente nuevo dentro de la venta', function () {
    $vendedora = ventasUsuario(Role::VENDEDOR);
    $producto = crearProductoConStock(10);

    $response = $this->actingAs($vendedora)->post(route('ventas.store'), [
        'cliente_id' => null,
        'cliente_nuevo' => [
            'nombre' => 'María López Vargas',
            'ci_nit' => 'CI-90210',
            'telefono' => '76543210',
            'email' => 'maria@example.com',
            'direccion' => 'Eterazama',
        ],
        'fecha' => '2026-10-02',
        'detalles' => [[
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => 80,
            'descuento' => 0,
        ]],
    ]);

    $response->assertRedirect(route('ventas.index'))->assertSessionHasNoErrors();

    $cliente = Cliente::where('ci_nit', 'CI-90210')->sole();

    expect($cliente->nombre)->toBe('María López Vargas')
        ->and($cliente->apellido)->toBeNull()
        ->and($cliente->estado)->toBe('activo')
        ->and(Venta::sole()->cliente_id)->toBe($cliente->id)
        ->and($producto->inventario->fresh()->stock_actual)->toBe(8);
});

test('no se puede registrar una venta con stock insuficiente', function () {
    $kenia = ventasUsuario(Role::VENDEDOR);
    $gimena = Cliente::create([
        'nombre' => 'gimena',
        'ci_nit' => '99939',
        'telefono' => '3838883',
    ]);
    $producto = crearProductoConStock(3);

    $this->actingAs($kenia)->post(route('ventas.store'), [
        'cliente_id' => $gimena->id,
        'fecha' => '2026-08-10',
        'detalles' => [
            [
                'producto_id' => $producto->id,
                'cantidad' => 5,
                'precio_unitario' => 80.00,
                'descuento' => 0,
            ],
        ],
    ])->assertSessionHasErrors();

    expect(Venta::count())->toBe(0);
    expect($producto->inventario->fresh()->stock_actual)->toBe(3);
});

test('la vendedora solo ve sus propias ventas en el listado', function () {
    $kenia = ventasUsuario(Role::VENDEDOR);
    $otraVendedora = ventasUsuario(Role::VENDEDOR);
    $gimena = Cliente::create(['nombre' => 'gimena', 'ci_nit' => '99939', 'telefono' => '3838883']);

    Venta::create([
        'cliente_id' => $gimena->id,
        'user_id' => $kenia->id,
        'numero_venta' => 'VNT-0001',
        'total' => 100,
        'fecha' => '2026-08-10',
        'estado' => 'completada',
    ]);

    Venta::create([
        'cliente_id' => $gimena->id,
        'user_id' => $otraVendedora->id,
        'numero_venta' => 'VNT-0002',
        'total' => 200,
        'fecha' => '2026-08-10',
        'estado' => 'completada',
    ]);

    $this->actingAs($kenia)->get(route('ventas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Ventas/index')->where('ventas.total', 1));
});

test('el listado muestra la ganancia real según el último costo de compra', function () {
    $admin = ventasUsuario(Role::ADMIN);
    $cliente = Cliente::create(['nombre' => 'Gimena', 'ci_nit' => '99119']);
    $producto = crearProductoConStock();
    $proveedor = Proveedor::factory()->create();
    $compra = Compra::create([
        'proveedor_id' => $proveedor->id,
        'user_id' => $admin->id,
        'numero_compra' => 'CMP-GANANCIA-1',
        'total' => 120,
        'fecha' => '2026-08-01',
    ]);

    $compra->detalles()->create([
        'producto_id' => $producto->id,
        'producto' => $producto->nombre,
        'cantidad' => 2,
        'precio_unitario' => 60,
        'subtotal' => 120,
    ]);

    $venta = Venta::create([
        'cliente_id' => $cliente->id,
        'user_id' => $admin->id,
        'numero_venta' => 'VNT-GANANCIA-1',
        'total' => 180,
        'fecha' => '2026-08-10',
        'estado' => 'completada',
    ]);

    $venta->detalles()->create([
        'producto_id' => $producto->id,
        'producto' => $producto->nombre,
        'cantidad' => 2,
        'precio_unitario' => 90,
        'descuento' => 0,
        'subtotal' => 180,
    ]);

    $this->actingAs($admin)->get(route('ventas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Ventas/index')
            ->where('ventas.data.0.ganancia', 60)
        );
});

test('el listado de ventas muestra el descuento real y no la ganancia', function () {
    $source = file_get_contents(resource_path('js/pages/Ventas/index.vue'));

    expect($source)
        ->toContain('function saleDiscount(venta: Venta): number')
        ->toContain('formatCurrency(saleDiscount(venta))')
        ->not->toContain('formatCurrency(venta.ganancia)');
});

test('la vendedora no puede ver la venta de otra vendedora', function () {
    $kenia = ventasUsuario(Role::VENDEDOR);
    $otraVendedora = ventasUsuario(Role::VENDEDOR);
    $gimena = Cliente::create(['nombre' => 'gimena', 'ci_nit' => '99939', 'telefono' => '3838883']);

    $venta = Venta::create([
        'cliente_id' => $gimena->id,
        'user_id' => $otraVendedora->id,
        'numero_venta' => 'VNT-0002',
        'total' => 200,
        'fecha' => '2026-08-10',
        'estado' => 'completada',
    ]);

    $this->actingAs($kenia)->get(route('ventas.show', $venta))->assertForbidden();
});

test('anular una venta conserva sus datos reales y restaura las existencias', function () {
    $admin = ventasUsuario(Role::ADMIN);
    $cliente = Cliente::create(['nombre' => 'Gimena', 'ci_nit' => '88888']);
    $producto = crearProductoConStock(20);

    $this->actingAs($admin)->post(route('ventas.store'), [
        'cliente_id' => $cliente->id,
        'fecha' => '2026-09-03',
        'detalles' => [[
            'producto_id' => $producto->id,
            'cantidad' => 7,
            'precio_unitario' => 80,
            'descuento' => 0,
        ]],
    ])->assertSessionHasNoErrors();

    $venta = Venta::sole();
    expect($producto->inventario->fresh()->stock_actual)->toBe(13);

    $this->actingAs($admin)->post(route('ventas.anular', $venta))
        ->assertRedirect(route('ventas.index'))
        ->assertSessionHasNoErrors();

    expect($venta->fresh()->estado)->toBe('anulada')
        ->and($venta->fresh()->detalles->sole()->cantidad)->toBe(7)
        ->and($producto->inventario->fresh()->stock_actual)->toBe(20);
});

test('una venta rechaza clientes inexistentes y productos duplicados', function () {
    $admin = ventasUsuario(Role::ADMIN);
    $producto = crearProductoConStock();

    $payload = [
        'cliente_id' => 999999,
        'fecha' => '2026-09-03',
        'detalles' => [[
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 80,
            'descuento' => 0,
        ]],
    ];

    $this->actingAs($admin)->post(route('ventas.store'), $payload)
        ->assertSessionHasErrors('cliente_id');

    $cliente = Cliente::create(['nombre' => 'Cliente válido', 'ci_nit' => '77777']);
    $payload['cliente_id'] = $cliente->id;
    $payload['detalles'][] = $payload['detalles'][0];

    $this->actingAs($admin)->post(route('ventas.store'), $payload)
        ->assertSessionHasErrors('detalles.1.producto_id');

    expect(Venta::count())->toBe(0);
});

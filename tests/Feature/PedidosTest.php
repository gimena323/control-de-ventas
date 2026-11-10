<?php

use App\Models\Categoria;
use App\Models\Color;
use App\Models\Inventario;
use App\Models\InventarioMovimiento;
use App\Models\Marca;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Role;
use App\Models\Talla;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->seed(PermissionsSeeder::class));

function clienteUser(): User
{
    $role = Role::firstOrCreate(
        ['nombre' => Role::CLIENTE],
        ['descripcion' => 'Cliente de la tienda']
    );
    $user = User::factory()->create(['role' => Role::CLIENTE]);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function productoDePrueba(): Producto
{
    $categoria = Categoria::create(['nombre' => 'Categoria '.fake()->unique()->word()]);
    $marca = Marca::create(['nombre' => 'Marca '.fake()->unique()->word()]);
    $talla = Talla::create(['nombre' => 'Talla '.fake()->unique()->word()]);
    $color = Color::create(['nombre' => 'Color '.fake()->unique()->word(), 'codigo_hex' => '#000000']);

    $producto = Producto::create([
        'categoria_id' => $categoria->id,
        'marca_id' => $marca->id,
        'talla_id' => $talla->id,
        'color_id' => $color->id,
        'nombre' => 'Vestido Test',
        'descripcion' => 'Descripcion de prueba',
        'precio_venta' => 120.50,
    ]);

    Inventario::create([
        'producto_id' => $producto->id,
        'stock_actual' => 10,
        'disponible' => true,
    ]);

    return $producto;
}

test('cliente puede ver sus pedidos en /pedidos', function () {
    $user = clienteUser();
    Pedido::create([
        'numero' => 'PED-TEST-0001',
        'user_id' => $user->id,
        'total' => 120.50,
        'estado' => Pedido::ESTADO_CONFIRMADO,
        'metodo_pago' => 'Pago por QR / WhatsApp',
    ]);

    $this->actingAs($user)->get(route('pedidos'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Pedidos/index')
            ->has('pedidos', 1));
});

test('cliente solo ve sus propios pedidos', function () {
    $user = clienteUser();
    $otroCliente = clienteUser();
    Pedido::create(['numero' => 'PED-TEST-0001', 'user_id' => $user->id, 'total' => 100, 'estado' => Pedido::ESTADO_CONFIRMADO]);
    Pedido::create(['numero' => 'PED-TEST-0002', 'user_id' => $otroCliente->id, 'total' => 200, 'estado' => Pedido::ESTADO_CONFIRMADO]);

    $this->actingAs($user)->get(route('pedidos'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('pedidos', 1)
            ->where('pedidos.0.numero', 'PED-TEST-0001'));
});

test('cliente no ve sus compras canceladas', function () {
    $user = clienteUser();
    Pedido::create(['numero' => 'PED-ACTIVA', 'user_id' => $user->id, 'total' => 100, 'estado' => Pedido::ESTADO_CONFIRMADO]);
    Pedido::create(['numero' => 'PED-CANCELADA', 'user_id' => $user->id, 'total' => 80, 'estado' => Pedido::ESTADO_CANCELADO]);

    $this->actingAs($user)->get(route('pedidos'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('pedidos', 1)
            ->where('pedidos.0.numero', 'PED-ACTIVA'));
});

test('muestra solo la compra confirmada mas reciente', function () {
    $user = clienteUser();

    $vieja = Pedido::create(['numero' => 'PED-VIEJA', 'user_id' => $user->id, 'total' => 100, 'estado' => Pedido::ESTADO_CONFIRMADO]);
    $vieja->forceFill(['created_at' => now()->subDay()])->save();

    Pedido::create(['numero' => 'PED-NUEVA', 'user_id' => $user->id, 'total' => 150, 'estado' => Pedido::ESTADO_CONFIRMADO]);
    Pedido::create(['numero' => 'PED-PENDIENTE', 'user_id' => $user->id, 'total' => 80, 'estado' => Pedido::ESTADO_PENDIENTE]);

    $this->actingAs($user)->get(route('pedidos'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('pedidos', 1)
            ->where('pedidos.0.numero', 'PED-NUEVA'));
});

test('cliente puede registrar una solicitud de compra pendiente sin descontar stock', function () {
    $user = clienteUser();
    $producto = productoDePrueba();

    $this->actingAs($user)->from(route('catalogo'))->post(route('pedidos.store'), [
        'productos' => [
            [
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'precio' => $producto->precio_venta,
                'cantidad' => 2,
                'color' => 'Negro',
                'talla' => 'M',
                'imagen' => null,
            ],
        ],
        'metodo_pago' => 'Reserva por WhatsApp',
    ])->assertRedirect(route('catalogo'))
        ->assertSessionHasNoErrors();

    expect(Pedido::count())->toBe(1)
        ->and(Venta::count())->toBe(0)
        ->and(InventarioMovimiento::count())->toBe(0);

    $pedido = Pedido::first();
    expect($pedido->user_id)->toBe($user->id);
    expect((float) $pedido->total)->toBe(241.0);
    expect($pedido->estado)->toBe(Pedido::ESTADO_PENDIENTE);
    expect($pedido->numero)->toMatch('/^PED-\d{8}-\d{4}$/');
    expect($pedido->detalles()->count())->toBe(1);
    expect($pedido->detalles()->first()->subtotal)->toBe('241.00');
    expect($pedido->venta_id)->toBeNull();
    expect(Inventario::where('producto_id', $producto->id)->value('stock_actual'))->toBe(10);
});

test('la solicitud de compra conserva la talla y color seleccionados sin descontar stock', function () {
    $user = clienteUser();
    $producto = productoDePrueba();
    $tallaM = Talla::firstOrCreate(['nombre' => 'M']);
    $tallaL = Talla::create(['nombre' => 'L']);
    $colorNegro = Color::create(['nombre' => 'Negro', 'codigo_hex' => '#111111']);

    $varianteM = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $tallaM->id,
        'color_id' => $colorNegro->id,
        'stock' => 0,
        'disponible' => false,
    ]);
    $varianteL = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $tallaL->id,
        'color_id' => $colorNegro->id,
        'stock' => 4,
        'disponible' => true,
    ]);

    $this->actingAs($user)->post(route('pedidos.store'), [
        'productos' => [[
            'producto_id' => $producto->id,
            'producto_variante_id' => $varianteM->id,
            'nombre' => $producto->nombre,
            'precio' => 1,
            'cantidad' => 2,
            'color' => 'Negro',
            'talla' => 'M',
            'imagen' => null,
        ]],
    ])->assertSessionHasNoErrors();

    expect($varianteM->fresh()->stock)->toBe(0)
        ->and($varianteL->fresh()->stock)->toBe(4)
        ->and(Inventario::where('producto_id', $producto->id)->value('stock_actual'))->toBe(10)
        ->and(Venta::count())->toBe(0);

    $detalle = Pedido::firstOrFail()->detalles()->firstOrFail();
    expect($detalle->producto_variante_id)->toBe($varianteM->id)
        ->and($detalle->talla)->toBe('M')
        ->and($detalle->color)->toBe('Negro')
        ->and((float) $detalle->precio)->toBe(120.5);
});

test('cliente puede adjuntar comprobante de pago por QR en la solicitud', function () {
    Storage::fake('public');

    $user = clienteUser();
    $producto = productoDePrueba();

    $this->actingAs($user)->post(route('pedidos.store'), [
        'productos' => [[
            'producto_id' => $producto->id,
            'nombre' => $producto->nombre,
            'precio' => $producto->precio_venta,
            'cantidad' => 1,
            'color' => 'Negro',
            'talla' => 'M',
            'imagen' => null,
        ]],
        'metodo_pago' => 'Pago por QR',
        'comprobante_pago' => UploadedFile::fake()->image('comprobante.jpg'),
    ])->assertSessionHasNoErrors();

    $pedido = Pedido::sole();

    expect($pedido->metodo_pago)->toBe('Pago por QR')
        ->and($pedido->comprobante_pago)->not->toBeNull();

    Storage::disk('public')->assertExists($pedido->comprobante_pago);
});

test('el personal ve el comprobante de pago en la solicitud pendiente', function () {
    Storage::fake('public');

    $cliente = clienteUser();
    $producto = productoDePrueba();

    $this->actingAs($cliente)->post(route('pedidos.store'), [
        'productos' => [[
            'producto_id' => $producto->id,
            'nombre' => $producto->nombre,
            'precio' => $producto->precio_venta,
            'cantidad' => 1,
            'color' => 'Negro',
            'talla' => 'M',
            'imagen' => null,
        ]],
        'metodo_pago' => 'Pago por QR',
        'comprobante_pago' => UploadedFile::fake()->image('comprobante.jpg'),
    ])->assertSessionHasNoErrors();

    $pedido = Pedido::sole();

    $vendedorRole = Role::where('nombre', Role::VENDEDOR)->firstOrFail();
    $vendedora = User::factory()->create(['role' => Role::VENDEDOR]);
    $vendedora->roles()->sync([$vendedorRole->id]);
    $vendedora = $vendedora->fresh();

    $this->actingAs($vendedora)->get(route('ventas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('solicitudes', 1)
            ->where('solicitudes.0.comprobante_pago', $pedido->comprobante_pago));
});

test('la vendedora ve la solicitud pendiente en ventas y al confirmarla se registra la venta', function () {
    $cliente = clienteUser();
    $producto = productoDePrueba();

    $this->actingAs($cliente)->post(route('pedidos.store'), [
        'productos' => [[
            'producto_id' => $producto->id,
            'nombre' => $producto->nombre,
            'precio' => $producto->precio_venta,
            'cantidad' => 2,
            'color' => 'Negro',
            'talla' => 'M',
            'imagen' => null,
        ]],
    ])->assertSessionHasNoErrors();

    $pedido = Pedido::sole();

    $vendedorRole = Role::where('nombre', Role::VENDEDOR)->firstOrFail();
    $vendedora = User::factory()->create(['role' => Role::VENDEDOR]);
    $vendedora->roles()->sync([$vendedorRole->id]);
    $vendedora = $vendedora->fresh();

    $this->actingAs($vendedora)->get(route('ventas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Ventas/index')
            ->where('ventas.total', 0)
            ->has('solicitudes', 1)
            ->where('solicitudes.0.id', $pedido->id));

    $this->actingAs($vendedora)
        ->post(route('ventas.solicitudes.confirmar', $pedido))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $venta = Venta::sole();

    expect($pedido->fresh()->estado)->toBe(Pedido::ESTADO_CONFIRMADO)
        ->and($pedido->fresh()->venta_id)->toBe($venta->id)
        ->and(Inventario::where('producto_id', $producto->id)->value('stock_actual'))->toBe(8);

    $this->actingAs($vendedora)->get(route('ventas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('ventas.total', 1)
            ->where('ventas.data.0.id', $venta->id)
            ->has('solicitudes', 0));

    $this->actingAs($vendedora)->get(route('ventas.show', $venta))->assertOk();
});

test('confirmar una solicitud descuenta la talla y color comprados', function () {
    $cliente = clienteUser();
    $producto = productoDePrueba();
    $tallaM = Talla::firstOrCreate(['nombre' => 'M']);
    $colorNegro = Color::create(['nombre' => 'Negro', 'codigo_hex' => '#111111']);

    $variante = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $tallaM->id,
        'color_id' => $colorNegro->id,
        'stock' => 3,
        'disponible' => true,
    ]);

    $this->actingAs($cliente)->post(route('pedidos.store'), [
        'productos' => [[
            'producto_id' => $producto->id,
            'producto_variante_id' => $variante->id,
            'nombre' => $producto->nombre,
            'precio' => $producto->precio_venta,
            'cantidad' => 2,
            'color' => 'Negro',
            'talla' => 'M',
            'imagen' => null,
        ]],
    ])->assertSessionHasNoErrors();

    $pedido = Pedido::sole();

    $vendedorRole = Role::where('nombre', Role::VENDEDOR)->firstOrFail();
    $vendedora = User::factory()->create(['role' => Role::VENDEDOR]);
    $vendedora->roles()->sync([$vendedorRole->id]);

    $this->actingAs($vendedora->fresh())
        ->post(route('ventas.solicitudes.confirmar', $pedido))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($variante->fresh()->stock)->toBe(1)
        ->and($variante->fresh()->disponible)->toBeTrue()
        ->and($producto->inventario->fresh()->stock_actual)->toBe(8);
});

test('cancelar una solicitud de compra no descuenta ni devuelve stock', function () {
    $cliente = clienteUser();
    $producto = productoDePrueba();

    $this->actingAs($cliente)->post(route('pedidos.store'), [
        'productos' => [[
            'producto_id' => $producto->id,
            'nombre' => $producto->nombre,
            'precio' => $producto->precio_venta,
            'cantidad' => 2,
            'color' => 'Negro',
            'talla' => 'M',
            'imagen' => null,
        ]],
    ])->assertSessionHasNoErrors();

    $pedido = Pedido::firstOrFail();

    expect($producto->inventario->fresh()->stock_actual)->toBe(10)
        ->and(Venta::count())->toBe(0);

    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $admin->roles()->sync([Role::where('nombre', Role::ADMIN)->firstOrFail()->id]);

    $this->actingAs($admin->fresh())
        ->post(route('ventas.solicitudes.cancelar', $pedido))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($pedido->fresh()->estado)->toBe(Pedido::ESTADO_CANCELADO)
        ->and($producto->inventario->fresh()->stock_actual)->toBe(10)
        ->and(Venta::count())->toBe(0)
        ->and(InventarioMovimiento::count())->toBe(0);
});

test('la solicitud guarda la variante exacta seleccionada aunque los nombres no coincidan', function () {
    $user = clienteUser();
    $producto = productoDePrueba();
    $tallaM = Talla::firstOrCreate(['nombre' => 'M']);
    $tallaS = Talla::create(['nombre' => 'S']);
    $colorNegro = Color::create(['nombre' => 'Negro', 'codigo_hex' => '#111111']);

    $varianteM = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $tallaM->id,
        'color_id' => $colorNegro->id,
        'stock' => 5,
        'disponible' => true,
    ]);
    $varianteS = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $tallaS->id,
        'color_id' => $colorNegro->id,
        'stock' => 5,
        'disponible' => true,
    ]);

    $this->actingAs($user)->post(route('pedidos.store'), [
        'productos' => [[
            'producto_id' => $producto->id,
            'producto_variante_id' => $varianteS->id,
            'nombre' => $producto->nombre,
            'precio' => 1,
            'cantidad' => 2,
            'color' => 'Color que no coincide',
            'talla' => 'M',
            'imagen' => null,
        ]],
    ])->assertSessionHasNoErrors();

    expect($varianteS->fresh()->stock)->toBe(5)
        ->and($varianteM->fresh()->stock)->toBe(5)
        ->and(Inventario::where('producto_id', $producto->id)->value('stock_actual'))->toBe(10)
        ->and(Venta::count())->toBe(0);

    $detalle = Pedido::firstOrFail()->detalles()->firstOrFail();
    expect($detalle->producto_variante_id)->toBe($varianteS->id);
});

test('rechaza la solicitud cuando el producto no tiene stock total', function () {
    $user = clienteUser();
    $producto = productoDePrueba();
    $producto->inventario->update(['stock_actual' => 0]);
    $tallaM = Talla::firstOrCreate(['nombre' => 'M']);
    $colorCafe = Color::create(['nombre' => 'Café', 'codigo_hex' => '#6F4E37']);
    $colorVinotinto = Color::create(['nombre' => 'Vinotinto', 'codigo_hex' => '#722F37']);

    $varianteCafe = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $tallaM->id,
        'color_id' => $colorCafe->id,
        'stock' => 0,
        'disponible' => true,
    ]);
    $varianteVinotinto = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $tallaM->id,
        'color_id' => $colorVinotinto->id,
        'stock' => 2,
        'disponible' => true,
    ]);

    $this->actingAs($user)->post(route('pedidos.store'), [
        'productos' => [[
            'producto_id' => $producto->id,
            'producto_variante_id' => $varianteCafe->id,
            'nombre' => $producto->nombre,
            'precio' => 1,
            'cantidad' => 1,
            'color' => 'Café',
            'talla' => 'M',
            'imagen' => null,
        ]],
    ])->assertSessionHasErrors('pedido');

    expect($varianteCafe->fresh()->stock)->toBe(0);
    expect($varianteVinotinto->fresh()->stock)->toBe(2);
    expect(Inventario::where('producto_id', $producto->id)->value('stock_actual'))->toBe(0);
    expect(Pedido::count())->toBe(0);
    expect(Venta::count())->toBe(0);
});

test('rechaza la solicitud si el stock total es menor a la cantidad pedida', function () {
    $user = clienteUser();
    $producto = productoDePrueba();
    $producto->inventario->update(['stock_actual' => 1]);
    $tallaM = Talla::firstOrCreate(['nombre' => 'M']);
    $colorNegro = Color::create(['nombre' => 'Negro', 'codigo_hex' => '#111111']);

    $varianteM = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $tallaM->id,
        'color_id' => $colorNegro->id,
        'stock' => 1,
        'disponible' => true,
    ]);

    $this->actingAs($user)->post(route('pedidos.store'), [
        'productos' => [[
            'producto_id' => $producto->id,
            'producto_variante_id' => $varianteM->id,
            'nombre' => $producto->nombre,
            'precio' => 1,
            'cantidad' => 2,
            'talla' => 'M',
            'color' => 'Negro',
            'imagen' => null,
        ]],
    ])->assertSessionHasErrors('pedido');

    expect(Pedido::count())->toBe(0);
    expect(Venta::count())->toBe(0);
    expect(InventarioMovimiento::count())->toBe(0);
    expect($varianteM->fresh()->stock)->toBe(1);
    expect(Inventario::where('producto_id', $producto->id)->value('stock_actual'))->toBe(1);
});

test('no se puede crear un pedido sin productos', function () {
    $user = clienteUser();

    $this->actingAs($user)->from(route('catalogo'))->post(route('pedidos.store'), [
        'productos' => [],
    ])->assertSessionHasErrors('productos');
});

test('admin no puede acceder a /pedidos (ruta exclusiva del cliente)', function () {
    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $admin->roles()->sync([Role::where('nombre', Role::ADMIN)->firstOrFail()->id]);

    $this->actingAs($admin->fresh())->get(route('pedidos'))->assertForbidden();
});

test('el endpoint de notificaciones devuelve el conteo y la ultima solicitud pendiente', function () {
    $cliente = clienteUser();
    $producto = productoDePrueba();

    $this->actingAs($cliente)->post(route('pedidos.store'), [
        'productos' => [[
            'producto_id' => $producto->id,
            'nombre' => $producto->nombre,
            'precio' => $producto->precio_venta,
            'cantidad' => 1,
            'color' => 'Negro',
            'talla' => 'M',
            'imagen' => null,
        ]],
    ])->assertSessionHasNoErrors();

    $pedido = Pedido::sole();
    $vendedorRole = Role::where('nombre', Role::VENDEDOR)->firstOrFail();
    $vendedora = User::factory()->create(['role' => Role::VENDEDOR]);
    $vendedora->roles()->sync([$vendedorRole->id]);

    $this->actingAs($vendedora->fresh())
        ->getJson(route('ventas.solicitudes.pendientes'))
        ->assertOk()
        ->assertJson([
            'count' => 1,
            'latest_id' => $pedido->id,
        ]);
});

function vendedoraUser(): User
{
    $role = Role::where('nombre', Role::VENDEDOR)->firstOrFail();
    $user = User::factory()->create(['role' => Role::VENDEDOR]);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function crearSolicitudDeCompra($test, int $cantidad = 1, ?int $varianteId = null, ?Producto $producto = null): Pedido
{
    $cliente = clienteUser();
    $producto ??= productoDePrueba();

    $test->actingAs($cliente)->post(route('pedidos.store'), [
        'productos' => [[
            'producto_id' => $producto->id,
            'producto_variante_id' => $varianteId,
            'nombre' => $producto->nombre,
            'precio' => $producto->precio_venta,
            'cantidad' => $cantidad,
            'color' => 'Negro',
            'talla' => 'M',
            'imagen' => null,
        ]],
    ])->assertSessionHasNoErrors();

    return Pedido::sole();
}

test('registrar una venta desde una solicitud emite el flash con los datos de la venta', function () {
    $pedido = crearSolicitudDeCompra($this);
    $vendedora = vendedoraUser();

    $this->actingAs($vendedora)
        ->post(route('ventas.solicitudes.confirmar', $pedido))
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('venta_registrada');

    $venta = Venta::sole();

    expect($pedido->fresh()->estado)->toBe(Pedido::ESTADO_CONFIRMADO)
        ->and($pedido->fresh()->venta_id)->toBe($venta->id);
});

test('la vendedora no puede cancelar una solicitud de compra', function () {
    $pedido = crearSolicitudDeCompra($this);
    $vendedora = vendedoraUser();

    $this->actingAs($vendedora)
        ->post(route('ventas.solicitudes.cancelar', $pedido))
        ->assertForbidden();

    expect($pedido->fresh()->estado)->toBe(Pedido::ESTADO_PENDIENTE);
});

test('una solicitud ya atendida no puede registrarse dos veces', function () {
    $pedido = crearSolicitudDeCompra($this);
    $vendedora = vendedoraUser();

    $this->actingAs($vendedora)
        ->post(route('ventas.solicitudes.confirmar', $pedido))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($vendedora)
        ->post(route('ventas.solicitudes.confirmar', $pedido))
        ->assertRedirect()
        ->assertSessionHasErrors('solicitud');

    expect(Venta::count())->toBe(1)
        ->and($pedido->fresh()->estado)->toBe(Pedido::ESTADO_CONFIRMADO);
});

test('una solicitud cancelada no puede registrarse como venta', function () {
    $pedido = crearSolicitudDeCompra($this);

    $admin = User::factory()->create(['role' => Role::ADMIN]);
    $admin->roles()->sync([Role::where('nombre', Role::ADMIN)->firstOrFail()->id]);

    $this->actingAs($admin->fresh())
        ->post(route('ventas.solicitudes.cancelar', $pedido))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $vendedora = vendedoraUser();

    $this->actingAs($vendedora)
        ->post(route('ventas.solicitudes.confirmar', $pedido))
        ->assertRedirect()
        ->assertSessionHasErrors([
            'solicitud' => 'Esta solicitud ha sido cancelada por el administrador.',
        ]);

    expect(Venta::count())->toBe(0)
        ->and($pedido->fresh()->estado)->toBe(Pedido::ESTADO_CANCELADO);
});

test('registrar una venta sin stock suficiente muestra un mensaje claro', function () {
    $producto = productoDePrueba();
    $talla = Talla::create(['nombre' => 'Talla stock']);
    $color = Color::create(['nombre' => 'Color stock', 'codigo_hex' => '#222222']);

    $variante = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $talla->id,
        'color_id' => $color->id,
        'stock' => 1,
        'disponible' => true,
    ]);

    $pedido = crearSolicitudDeCompra($this, 4, $variante->id, $producto);
    $vendedora = vendedoraUser();

    $this->actingAs($vendedora)
        ->post(route('ventas.solicitudes.confirmar', $pedido))
        ->assertRedirect()
        ->assertSessionHasErrors([
            'solicitud' => 'No hay suficiente stock disponible para completar esta venta.',
        ]);

    expect(Venta::count())->toBe(0)
        ->and($pedido->fresh()->estado)->toBe(Pedido::ESTADO_PENDIENTE)
        ->and($variante->fresh()->stock)->toBe(1);
});

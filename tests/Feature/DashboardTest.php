<?php

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Color;
use App\Models\Compra;
use App\Models\Inventario;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Talla;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $this->seed(PermissionsSeeder::class);
    $user = User::factory()->create(['role' => 'vendedor']);
    $user->assignRole('vendedor');

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('dashboard shows sales and purchase statistics from real data', function () {
    $this->seed(PermissionsSeeder::class);
    $user = User::factory()->create(['role' => 'admin']);
    $user->assignRole('admin');

    $cliente = Cliente::create([
        'nombre' => 'Maria',
        'apellido' => 'Perez',
        'email' => 'maria@example.com',
        'telefono' => '77777777',
        'direccion' => 'Calle Falsa 123',
        'ci_nit' => '1234567',
    ]);

    $venta = Venta::create([
        'cliente_id' => $cliente->id,
        'user_id' => $user->id,
        'numero_venta' => 'VNT-TEST-0001',
        'total' => 150.00,
        'fecha' => now()->toDateString(),
        'estado' => 'completada',
    ]);

    VentaDetalle::create([
        'venta_id' => $venta->id,
        'producto' => 'camisas',
        'cantidad' => 2,
        'precio_unitario' => 75.00,
        'subtotal' => 150.00,
    ]);

    $proveedor = Proveedor::create([
        'nombre' => 'Proveedor Prueba',
        'ci_nit' => '99887766',
        'telefono' => '70000000',
    ]);

    Compra::create([
        'proveedor_id' => $proveedor->id,
        'user_id' => $user->id,
        'numero_compra' => 'CMP-DASHBOARD-0001',
        'total' => 90,
        'fecha' => today(),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('estadisticas.6.fecha', today()->format('d/m'))
            ->where('estadisticas.6.ventas', 150)
            ->where('estadisticas.6.compras', 90));
});

test('dashboard shows real sales totals and product statistics', function () {
    Storage::fake('public');

    $this->seed(PermissionsSeeder::class);
    $user = User::factory()->create(['role' => 'admin']);
    $user->assignRole('admin');

    $categoria = Categoria::create(['nombre' => 'Blusas']);
    $marca = Marca::create(['nombre' => 'Emma']);
    $talla = Talla::firstOrCreate(['nombre' => 'M']);
    $color = Color::firstOrCreate(
        ['nombre' => 'Rosa'],
        ['codigo_hex' => '#ec4899'],
    );
    $producto = Producto::create([
        'categoria_id' => $categoria->id,
        'marca_id' => $marca->id,
        'talla_id' => $talla->id,
        'color_id' => $color->id,
        'nombre' => 'Camisa Elegante',
        'precio_venta' => 120,
        'imagen' => 'productos/camisa.jpg',
        'estado' => 'activo',
    ]);

    Inventario::create([
        'producto_id' => $producto->id,
        'stock_actual' => 3,
        'disponible' => true,
    ]);

    $cliente = Cliente::create([
        'nombre' => 'Ana',
        'apellido' => 'Torres',
        'ci_nit' => '7654321',
    ]);

    Venta::create([
        'cliente_id' => $cliente->id,
        'user_id' => $user->id,
        'numero_venta' => 'VNT-DASHBOARD-0001',
        'total' => 350,
        'fecha' => today(),
        'estado' => 'completada',
    ]);

    $this
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.totalProductos', 1)
            ->where('stats.stockBajo', 1)
            ->where('stats.totalUsuarios', 1)
            ->where('stats.totalVentas', 1)
            ->where('stats.totalCompras', 0)
            ->where('stats.totalClientes', 1)
            ->where('stats.totalProveedores', 0)
            ->where('stats.ventasHoy', 350)
            ->where('stats.ventasMes', 350)
            ->where('estadisticas.6.ventas', 350));
});

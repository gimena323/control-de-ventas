<?php

use App\Models\Cliente;
use App\Models\Role;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Database\Seeders\PermissionsSeeder;

beforeEach(function () {
    $this->seed(PermissionsSeeder::class);

    $this->user = User::factory()->create(['role' => Role::ADMIN]);
    $role = Role::where('nombre', Role::ADMIN)->firstOrFail();
    $this->user->roles()->sync([$role->id]);
});

test('utiliza la hora local de Bolivia al generar reportes', function () {
    expect(config('app.timezone'))->toBe('America/La_Paz')
        ->and(now()->utcOffset())->toBe(-240);
});

test('muestra por separado los reportes de compras y ventas', function () {
    $this->actingAs($this->user)->get(route('reportes.compras'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Reportes/compras')
            ->has('reporte.detalles')
            ->has('proveedores')
            ->has('categorias'));

    $this->actingAs($this->user)->get(route('reportes.ventas'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Reportes/ventas')
            ->has('reporte.detalles')
            ->has('usuarios')
            ->has('categorias'));
});

test('agrupa los productos repetidos en el resumen del reporte de ventas', function () {
    $cliente = Cliente::create(['nombre' => 'Cliente', 'ci_nit' => 'RPT-AGG']);

    $venta = Venta::create([
        'cliente_id' => $cliente->id,
        'user_id' => $this->user->id,
        'numero_venta' => 'VNT-RPT-AGG',
        'total' => 150,
        'fecha' => '2026-08-11',
        'estado' => 'completada',
    ]);

    VentaDetalle::create([
        'venta_id' => $venta->id,
        'producto' => 'Blusa Rosa',
        'cantidad' => 1,
        'precio_unitario' => 50,
        'subtotal' => 50,
    ]);

    VentaDetalle::create([
        'venta_id' => $venta->id,
        'producto' => 'Blusa Rosa',
        'cantidad' => 2,
        'precio_unitario' => 50,
        'subtotal' => 100,
    ]);

    $this->actingAs($this->user)->get(route('reportes.ventas'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('reporte.detalles', 2)
            ->has('reporte.resumen_producto', 1)
            ->where('reporte.resumen_producto.0.descripcion', 'Blusa Rosa')
            ->where('reporte.resumen_producto.0.cantidad', 3)
            ->where('reporte.resumen_producto.0.precio_unitario', 50)
            ->where('reporte.resumen_producto.0.subtotal', 150)
            ->where('reporte.resumen_producto.0.stock', 0));
});

test('genera los pdf de compras y ventas para verlos en el navegador', function () {
    $comprasPdf = $this->actingAs($this->user)->get(route('reportes.compras.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $ventasPdf = $this->actingAs($this->user)->get(route('reportes.ventas.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($comprasPdf->headers->get('content-disposition'))->toContain('inline')
        ->and($ventasPdf->headers->get('content-disposition'))->toContain('inline');
});

test('muestra la vista previa y permite descargar cada pdf', function () {
    $this->actingAs($this->user)->get(route('reportes.compras.vista-pdf'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Reportes/pdf-viewer')
            ->where('titulo', 'Reporte Detallado de Compras')
            ->has('pdfUrl')
            ->has('downloadUrl'));

    $this->actingAs($this->user)->get(route('reportes.ventas.vista-pdf'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Reportes/pdf-viewer')
            ->where('titulo', 'Reporte Detallado de Ventas')
            ->has('pdfUrl')
            ->has('downloadUrl'));

    $download = $this->actingAs($this->user)->get(route('reportes.compras.pdf', ['download' => 1]));
    expect($download->headers->get('content-disposition'))->toContain('attachment');
});

test('la vendedora solo accede al reporte de sus propias ventas', function () {
    $vendedora = User::factory()->create(['role' => Role::VENDEDOR]);
    $otraVendedora = User::factory()->create(['role' => Role::VENDEDOR]);
    $role = Role::where('nombre', Role::VENDEDOR)->firstOrFail();
    $vendedora->roles()->sync([$role->id]);
    $otraVendedora->roles()->sync([$role->id]);
    $cliente = Cliente::create(['nombre' => 'Cliente', 'ci_nit' => 'RPT-1']);

    Venta::create([
        'cliente_id' => $cliente->id,
        'user_id' => $vendedora->id,
        'numero_venta' => 'VNT-RPT-1',
        'total' => 80,
        'fecha' => '2026-08-11',
        'estado' => 'completada',
    ]);
    Venta::create([
        'cliente_id' => $cliente->id,
        'user_id' => $otraVendedora->id,
        'numero_venta' => 'VNT-RPT-2',
        'total' => 100,
        'fecha' => '2026-08-11',
        'estado' => 'completada',
    ]);

    $this->actingAs($vendedora)->get(route('reportes.ventas'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Reportes/ventas')
            ->where('reporte.total_ventas', 1)
            ->where('usuarios', []));

    $this->actingAs($vendedora)->get(route('reportes.compras'))->assertForbidden();
    $this->actingAs($vendedora)->get(route('reportes.compras.pdf'))->assertForbidden();
});

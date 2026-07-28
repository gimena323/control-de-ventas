<?php

use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ComprasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ProductosController;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\VentasController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::get('catalogo', [CatalogoController::class, 'index'])->name('catalogo');

Route::middleware(['auth', 'verified', 'role:admin,vendedor,encargado_inventario'])->group(function () {
    Route::get('dashboard', DashboardController::class)
        ->middleware('permission:panel.ver')
        ->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::prefix('clientes')
        ->name('clientes.')
        ->middleware('permission:clientes.ver')
        ->group(function () {
            Route::get('/', [ClientesController::class, 'index'])->name('index');
            Route::get('{cliente}', [ClientesController::class, 'show'])->name('show');

            Route::middleware('permission:clientes.gestion')->group(function () {
                Route::post('/', [ClientesController::class, 'store'])->name('store');
                Route::patch('{cliente}', [ClientesController::class, 'update'])->name('update');
                Route::delete('{cliente}', [ClientesController::class, 'destroy'])->name('destroy');
            });
        });

    Route::prefix('proveedores')
        ->name('proveedores.')
        ->middleware('permission:proveedores.ver')
        ->group(function () {
            Route::get('/', [ProveedoresController::class, 'index'])->name('index');
            Route::get('{proveedor}', [ProveedoresController::class, 'show'])->name('show');

            Route::middleware('permission:proveedores.gestion')->group(function () {
                Route::post('/', [ProveedoresController::class, 'store'])->name('store');
                Route::patch('{proveedor}', [ProveedoresController::class, 'update'])->name('update');
                Route::delete('{proveedor}', [ProveedoresController::class, 'destroy'])->name('destroy');
            });
        });

            Route::prefix('compras')
        ->name('compras.')
        ->middleware('permission:compras.ver')
        ->group(function () {
            Route::get('/', [ComprasController::class, 'index'])->name('index');
            Route::get('{compra}', [ComprasController::class, 'show'])->name('show');

            Route::middleware('permission:compras.crear')->group(function () {
                Route::post('/', [ComprasController::class, 'store'])->name('store');
            });

            Route::middleware('permission:compras.editar')->group(function () {
                Route::patch('{compra}', [ComprasController::class, 'update'])->name('update');
            });

            Route::middleware('permission:compras.eliminar')->group(function () {
                Route::delete('{compra}', [ComprasController::class, 'destroy'])->name('destroy');
            });
        });

    Route::prefix('ventas')
        ->name('ventas.')
        ->middleware('permission:ventas.ver')
        ->group(function () {
            Route::get('/', [VentasController::class, 'index'])->name('index');
            Route::get('anuladas', [VentasController::class, 'anuladas'])->name('anuladas');
            Route::get('{venta}', [VentasController::class, 'show'])->name('show');

            Route::middleware('permission:ventas.crear')->group(function () {
                Route::post('/', [VentasController::class, 'store'])->name('store');
                Route::post('{venta}/anular', [VentasController::class, 'anular'])->name('anular');
            });
        });

    Route::prefix('inventario')
        ->name('inventario.')
        ->middleware('permission:inventario.ver')
        ->group(function () {
            Route::get('/', [InventarioController::class, 'index'])->name('index');
            Route::get('{inventario}/movimientos', [InventarioController::class, 'movimientos'])->name('movimientos');

            Route::middleware('permission:inventario.gestion')->group(function () {
                Route::post('/', [InventarioController::class, 'store'])->name('store');
                Route::patch('{inventario}', [InventarioController::class, 'update'])->name('update');
                Route::delete('{inventario}', [InventarioController::class, 'destroy'])->name('destroy');
                Route::patch('{inventario}/toggle-disponible', [InventarioController::class, 'toggleDisponible'])->name('toggle-disponible');
                Route::post('{inventario}/entrada', [InventarioController::class, 'registrarEntrada'])->name('entrada');
                Route::post('{inventario}/salida', [InventarioController::class, 'registrarSalida'])->name('salida');
            });
        });

    Route::prefix('productos')
        ->name('productos.')
        ->middleware('permission:productos.ver')
        ->group(function () {
            Route::get('/', [ProductosController::class, 'index'])->name('index');
            Route::get('crear', [ProductosController::class, 'create'])->name('create');
            Route::get('{producto}/editar', [ProductosController::class, 'edit'])->name('edit');

            Route::middleware('permission:productos.crear')->group(function () {
                Route::post('/', [ProductosController::class, 'store'])->name('store');
            });

            Route::middleware('permission:productos.editar')->group(function () {
                Route::post('{producto}', [ProductosController::class, 'update'])->name('update');
            });

            Route::middleware('permission:productos.eliminar')->group(function () {
                Route::delete('{producto}', [ProductosController::class, 'destroy'])->name('destroy');
            });

            Route::post('categorias', [ProductosController::class, 'storeCategoria'])->name('categorias.store');
            Route::patch('categorias/{categoria}', [ProductosController::class, 'updateCategoria'])->name('categorias.update');
            Route::delete('categorias/{categoria}', [ProductosController::class, 'destroyCategoria'])->name('categorias.destroy');
            Route::post('marcas', [ProductosController::class, 'storeMarca'])->name('marcas.store');
            Route::patch('marcas/{marca}', [ProductosController::class, 'updateMarca'])->name('marcas.update');
            Route::delete('marcas/{marca}', [ProductosController::class, 'destroyMarca'])->name('marcas.destroy');
            Route::post('modelos', [ProductosController::class, 'storeModelo'])->name('modelos.store');
            Route::patch('modelos/{modelo}', [ProductosController::class, 'updateModelo'])->name('modelos.update');
            Route::delete('modelos/{modelo}', [ProductosController::class, 'destroyModelo'])->name('modelos.destroy');
            Route::post('tallas', [ProductosController::class, 'storeTalla'])->name('tallas.store');
            Route::patch('tallas/{talla}', [ProductosController::class, 'updateTalla'])->name('tallas.update');
            Route::delete('tallas/{talla}', [ProductosController::class, 'destroyTalla'])->name('tallas.destroy');
            Route::post('colores', [ProductosController::class, 'storeColor'])->name('colores.store');
            Route::patch('colores/{color}', [ProductosController::class, 'updateColor'])->name('colores.update');
            Route::delete('colores/{color}', [ProductosController::class, 'destroyColor'])->name('colores.destroy');
        });
});

Route::middleware(['auth'])->group(function () {
    Route::prefix('reportes')
        ->name('reportes.')
        ->middleware('permission:panel.ver')
        ->group(function () {
            Route::get('/', [ReportesController::class, 'compras'])->name('index');
            Route::get('compras', [ReportesController::class, 'compras'])->name('compras');
            Route::get('ventas', [ReportesController::class, 'ventas'])->name('ventas');
            Route::get('compras/pdf', [ReportesController::class, 'comprasPdf'])->name('compras.pdf');
            Route::get('ventas/pdf', [ReportesController::class, 'ventasPdf'])->name('ventas.pdf');
        });
});

Route::middleware(['auth', 'verified', 'role:admin', 'permission:usuarios.gestion'])->group(function () {
    Route::resource('roles', RolesController::class)->except(['show', 'create', 'edit']);

    Route::prefix('usuarios')->name('usuarios.')->group(function () {
        Route::get('/', [UsersController::class, 'index'])->name('index');
        Route::post('/', [UsersController::class, 'store'])->name('store');
        Route::patch('{user}', [UsersController::class, 'update'])->name('update');
        Route::delete('{user}', [UsersController::class, 'destroy'])->name('destroy');
        Route::patch('{user}/status', [UsersController::class, 'toggleStatus'])->name('toggle-status');
    });
});

require __DIR__.'/settings.php';

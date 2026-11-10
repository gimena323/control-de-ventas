<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ComprasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PedidosController;
use App\Http\Controllers\PedidosGestionController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProductosController;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\VentasController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)
    ->middleware('redirect-client')
    ->name('principal');

Route::get('catalogo', [CatalogoController::class, 'index'])->name('catalogo');
Route::get('catalogo/{producto}', [CatalogoController::class, 'show'])->name('catalogo.show');
Route::get('carrito', [CarritoController::class, 'index'])->name('carrito');

Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth', 'verified', 'role:cliente'])->group(function () {
    Route::get('pedidos', [PedidosController::class, 'misPedidos'])->name('pedidos');
    Route::post('pedidos', [PedidosController::class, 'store'])->name('pedidos.store');
    Route::get('perfil', [PerfilController::class, 'index'])->name('perfil');
    Route::patch('perfil', [PerfilController::class, 'update'])->name('perfil.update');
});

Route::middleware(['auth', 'verified', 'redirect-client', 'role:admin,vendedor', 'permission:pedidos.ver'])
    ->prefix('gestion')
    ->name('gestion.')
    ->group(function () {
        Route::get('pedidos', [PedidosGestionController::class, 'index'])->name('pedidos.index');
        Route::get('pedidos/{pedido}', [PedidosGestionController::class, 'show'])->name('pedidos.show');

        Route::middleware('permission:pedidos.gestion')->group(function () {
            Route::patch('pedidos/{pedido}/estado', [PedidosGestionController::class, 'updateEstado'])->name('pedidos.estado');
            Route::post('pedidos/{pedido}/confirmar', [PedidosGestionController::class, 'confirmar'])->name('pedidos.confirmar');
            Route::post('pedidos/{pedido}/preparar', [PedidosGestionController::class, 'preparar'])->name('pedidos.preparar');
            Route::post('pedidos/{pedido}/entregar', [PedidosGestionController::class, 'entregar'])->name('pedidos.entregar');
            Route::post('pedidos/{pedido}/cancelar', [PedidosGestionController::class, 'cancelar'])->name('pedidos.cancelar');
        });
    });

Route::middleware(['auth', 'verified', 'redirect-client', 'role:admin,vendedor,encargado_inventario'])->group(function () {
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
                Route::patch('{cliente}/estado', [ClientesController::class, 'toggleEstado'])->name('estado');
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
                Route::post('{compra}/anular', [ComprasController::class, 'anular'])->name('anular');
            });
        });

    Route::prefix('ventas')
        ->name('ventas.')
        ->middleware('permission:ventas.ver')
        ->group(function () {
            Route::get('/', [VentasController::class, 'index'])->name('index');
            Route::get('anuladas', [VentasController::class, 'anuladas'])->name('anuladas');
            Route::get('solicitudes/pendientes', [VentasController::class, 'solicitudesPendientes'])->name('solicitudes.pendientes');

            Route::middleware('permission:pedidos.gestion')->group(function () {
                Route::post('solicitudes/{pedido}/confirmar', [VentasController::class, 'confirmarSolicitud'])->name('solicitudes.confirmar');
                Route::post('solicitudes/{pedido}/cancelar', [VentasController::class, 'cancelarSolicitud'])->name('solicitudes.cancelar');
            });

            Route::get('{venta}', [VentasController::class, 'show'])->name('show');

            Route::middleware('permission:ventas.crear')->group(function () {
                Route::post('/', [VentasController::class, 'store'])->name('store');
                Route::patch('{venta}', [VentasController::class, 'update'])->name('update');
                Route::post('{venta}/anular', [VentasController::class, 'anular'])->name('anular');
                Route::delete('{venta}', [VentasController::class, 'destroy'])->name('destroy');
            });
        });

    Route::prefix('inventario')
        ->name('inventario.')
        ->middleware('permission:inventario.ver')
        ->group(function () {
            Route::get('/', [InventarioController::class, 'index'])->name('index');
            Route::get('movimientos', [InventarioController::class, 'todosLosMovimientos'])->name('movimientos.todos');
            Route::get('{inventario}/movimientos', [InventarioController::class, 'movimientos'])->name('movimientos');
            Route::patch('{inventario}/disponibilidad', [InventarioController::class, 'updateDisponibilidad'])
                ->middleware('permission:inventario.gestion')
                ->name('disponibilidad.update');
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

            Route::post('categorias', [ProductosController::class, 'storeCategoria'])->name('categorias.store');
            Route::patch('categorias/{categoria}', [ProductosController::class, 'updateCategoria'])->name('categorias.update');
            Route::delete('categorias/{categoria}', [ProductosController::class, 'destroyCategoria'])->name('categorias.destroy');
            Route::post('marcas', [ProductosController::class, 'storeMarca'])->name('marcas.store');
            Route::patch('marcas/{marca}', [ProductosController::class, 'updateMarca'])->name('marcas.update');
            Route::delete('marcas/{marca}', [ProductosController::class, 'destroyMarca'])->name('marcas.destroy');
            Route::post('tallas', [ProductosController::class, 'storeTalla'])->name('tallas.store');
            Route::patch('tallas/{talla}', [ProductosController::class, 'updateTalla'])->name('tallas.update');
            Route::delete('tallas/{talla}', [ProductosController::class, 'destroyTalla'])->name('tallas.destroy');
            Route::post('colores', [ProductosController::class, 'storeColor'])->name('colores.store');
            Route::patch('colores/{color}', [ProductosController::class, 'updateColor'])->name('colores.update');
            Route::delete('colores/{color}', [ProductosController::class, 'destroyColor'])->name('colores.destroy');

            Route::middleware('permission:productos.editar')->group(function () {
                Route::patch('{producto}', [ProductosController::class, 'update'])->name('update');
            });

            Route::middleware('permission:productos.eliminar')->group(function () {
                Route::delete('{producto}', [ProductosController::class, 'destroy'])->name('destroy');
            });
        });
});

Route::middleware(['auth'])->group(function () {
    Route::prefix('reportes')
        ->name('reportes.')
        ->middleware('permission:panel.ver')
        ->group(function () {
            Route::middleware('permission:reportes.compras.ver')->group(function () {
                Route::get('/', [ReportesController::class, 'compras'])->name('index');
                Route::get('compras', [ReportesController::class, 'compras'])->name('compras');
                Route::get('compras/vista-pdf', [ReportesController::class, 'comprasVistaPdf'])->name('compras.vista-pdf');
                Route::get('compras/pdf', [ReportesController::class, 'comprasPdf'])->name('compras.pdf');
            });

            Route::middleware('permission:reportes.ventas.ver')->group(function () {
                Route::get('ventas', [ReportesController::class, 'ventas'])->name('ventas');
                Route::get('ventas/vista-pdf', [ReportesController::class, 'ventasVistaPdf'])->name('ventas.vista-pdf');
                Route::get('ventas/pdf', [ReportesController::class, 'ventasPdf'])->name('ventas.pdf');
            });
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

Route::get('configuraciones', fn () => redirect()->route('profile.edit'))
    ->middleware(['auth', 'verified', 'role:admin', 'permission:configuraciones.gestion'])
    ->name('configuraciones.index');

require __DIR__.'/settings.php';

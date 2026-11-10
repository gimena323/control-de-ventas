<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class WelcomeController extends Controller
{
    public function __invoke(): Response
    {
        $categorias = Categoria::withCount('productos')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'total_productos' => $c->productos_count,
            ]);

        $marcas = Marca::withCount('productos')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'nombre' => $m->nombre,
                'total_productos' => $m->productos_count,
            ]);

        $productosMasVendidos = Producto::with([
            'categoria:id,nombre',
            'marca:id,nombre',
            'inventario',
        ])
            ->where('estado', 'activo')
            ->whereHas('ventasDetalles')
            ->withCount(['ventasDetalles as total_vendido' => function ($q) {
                $q->select(DB::raw('COALESCE(SUM(cantidad), 0)'));
            }])
            ->orderByDesc('total_vendido')
            ->take(8)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'precio_venta' => $p->precio_venta,
                'imagen' => $p->imagen ? asset('storage/'.$p->imagen) : null,
                'categoria' => $p->categoria->nombre ?? '---',
                'marca' => $p->marca->nombre ?? '---',
                'stock' => $p->inventario->stock_actual ?? 0,
                'disponible' => ($p->inventario->disponible ?? false) && ($p->inventario->stock_actual ?? 0) > 0,
                'total_vendido' => (int) $p->total_vendido,
            ]);

        $productosRecientes = Producto::with([
            'categoria:id,nombre',
            'marca:id,nombre',
            'color:id,nombre,codigo_hex',
            'variantes.color:id,nombre,codigo_hex',
            'inventario:id,producto_id,stock_actual,disponible',
            'imagenes:id,producto_id,ruta,orden',
        ])
            ->where('estado', 'activo')
            ->whereHas('inventario', fn ($query) => $query->where('disponible', true))
            ->latest()
            ->take(8)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'descripcion' => $p->descripcion,
                'precio_venta' => $p->precio_venta,
                'imagen' => $p->imagen ? asset('storage/'.$p->imagen) : null,
                'imagenes' => collect([$p->imagen])
                    ->merge($p->imagenes->pluck('ruta'))
                    ->filter()
                    ->unique()
                    ->map(fn (string $ruta): string => asset('storage/'.$ruta))
                    ->values()
                    ->all(),
                'categoria' => $p->categoria->nombre ?? '---',
                'marca' => $p->marca->nombre ?? '---',
                'genero' => $p->genero ?? '---',
                'stock' => $p->inventario->stock_actual ?? 0,
                'disponible' => (bool) ($p->inventario->disponible ?? false),
                'colores' => $this->buildColores($p),
            ]);

        $totalProductos = Producto::where('estado', 'activo')->count();
        $totalCategorias = Categoria::count();
        $totalMarcas = Marca::count();
        $totalClientes = Cliente::count();
        $totalProveedores = Proveedor::count();
        $totalUsuarios = User::count();

        $ventasCompletadas = Venta::where('estado', 'completada')->count();
        $productosAgotados = Producto::where('estado', 'activo')
            ->whereDoesntHave('inventario', fn ($q) => $q->where('stock_actual', '>', 0)->where('disponible', true))
            ->count();

        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'categorias' => $categorias,
            'marcas' => $marcas,
            'productosRecientes' => $productosRecientes,
            'productosMasVendidos' => $productosMasVendidos,
            'stats' => [
                'totalProductos' => $totalProductos,
                'totalCategorias' => $totalCategorias,
                'totalMarcas' => $totalMarcas,
                'totalClientes' => $totalClientes,
                'totalProveedores' => $totalProveedores,
                'totalUsuarios' => $totalUsuarios,
                'ventasCompletadas' => $ventasCompletadas,
                'productosAgotados' => $productosAgotados,
            ],
        ]);
    }

    private function buildColores(Producto $producto): array
    {
        $stock = $producto->inventario->stock_actual ?? 0;
        $disponible = (bool) ($producto->inventario->disponible ?? false);

        $colores = $producto->variantes
            ->pluck('color')
            ->filter()
            ->unique('id')
            ->map(fn ($c) => [
                'color_nombre' => $c->nombre,
                'color_hex' => $c->codigo_hex,
                'stock_total' => $stock,
                'disponible' => $disponible && $stock > 0,
            ])
            ->values()
            ->all();

        if (empty($colores) && $producto->color) {
            $colores = [[
                'color_nombre' => $producto->color->nombre,
                'color_hex' => $producto->color->codigo_hex,
                'stock_total' => $stock,
                'disponible' => $disponible && $stock > 0,
            ]];
        }

        return $colores;
    }
}

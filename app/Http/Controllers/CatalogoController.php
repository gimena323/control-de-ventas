<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogoController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Producto::query()
            ->with([
                'categoria:id,nombre',
                'marca:id,nombre',
                'modelo:id,nombre',
                'talla:id,nombre',
                'color:id,nombre,codigo_hex',
                'inventario',
                'variantes.color:id,nombre,codigo_hex',
                'variantes.talla:id,nombre',
            ])
            ->where('estado', 'activo');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->search}%")
                    ->orWhere('descripcion', 'like', "%{$request->search}%");
            });
        }

        if ($request->categoria_id) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->marca_id) {
            $query->where('marca_id', $request->marca_id);
        }

        $allProductos = $query->orderBy('nombre')->get();

        $productos = $allProductos->map(function ($producto) {
            $inventario = $producto->inventario;
            $stock = $inventario ? $inventario->stock_actual : 0;
            $disponible = $inventario && $inventario->disponible && $stock > 0;

            $variantes = $producto->variantes->map(function ($v) {
                return [
                    'id' => $v->id,
                    'color_nombre' => $v->color->nombre ?? '---',
                    'color_hex' => $v->color->codigo_hex ?? '#ccc',
                    'talla' => $v->talla->nombre ?? '---',
                    'stock' => $v->stock,
                    'disponible' => $v->disponible,
                ];
            })->values()->all();

            $coloresUnicos = collect($variantes)
                ->groupBy('color_nombre')
                ->map(function ($group, $name) {
                    $first = $group->first();
                    $totalStock = $group->sum('stock');
                    $hasAvailable = $group->contains('disponible', true);
                    return [
                        'color_nombre' => $name,
                        'color_hex' => $first['color_hex'],
                        'stock_total' => $totalStock,
                        'disponible' => $hasAvailable && $totalStock > 0,
                    ];
                })
                ->values()
                ->all();

            $tallasUnicas = collect($variantes)
                ->groupBy('talla')
                ->map(function ($group, $name) {
                    $totalStock = $group->sum('stock');
                    $hasAvailable = $group->contains('disponible', true);
                    return [
                        'talla' => $name,
                        'stock_total' => $totalStock,
                        'disponible' => $hasAvailable && $totalStock > 0,
                    ];
                })
                ->values()
                ->all();

            return [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'precio_venta' => $producto->precio_venta,
                'imagen' => $producto->imagen ? asset('storage/'.$producto->imagen) : null,
                'categoria' => $producto->categoria->nombre ?? '---',
                'marca' => $producto->marca->nombre ?? '---',
                'modelo' => $producto->modelo->nombre ?? '---',
                'talla' => $producto->talla->nombre ?? '---',
                'color' => $producto->color->nombre ?? '---',
                'color_hex' => $producto->color->codigo_hex ?? '#ccc',
                'stock' => $stock,
                'disponible' => $disponible,
                'variantes' => $variantes,
                'colores' => $coloresUnicos,
                'tallas' => $tallasUnicas,
            ];
        });

        return Inertia::render('Catalogo/index', [
            'productos' => $productos,
            'categorias' => Categoria::select('id', 'nombre')->orderBy('nombre')->get(),
            'filtros' => $request->only(['search', 'categoria_id', 'marca_id']),
        ]);
    }
}

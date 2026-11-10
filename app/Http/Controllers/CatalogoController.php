<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Color;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Talla;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogoController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Producto::query()
            ->select([
                'id',
                'categoria_id',
                'marca_id',
                'color_id',
                'nombre',
                'precio_venta',
                'imagen',
                'estado',
                'genero',
                'created_at',
            ])
            ->with([
                'categoria:id,nombre',
                'marca:id,nombre',
                'color:id,nombre,codigo_hex',
                'variantes.color:id,nombre,codigo_hex',
                'inventario:id,producto_id,stock_actual,disponible',
                'imagenes:id,producto_id,ruta,orden',
            ])
            ->where('estado', 'activo')
            ->whereHas('inventario', fn ($inventoryQuery) => $inventoryQuery
                ->where('disponible', true)
                ->where('stock_actual', '>', 0));

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->search}%")
                    ->orWhere('descripcion', 'like', "%{$request->search}%")
                    ->orWhere('genero', 'like', "%{$request->search}%")
                    ->orWhereHas('marca', fn ($mq) => $mq->where('nombre', 'like', "%{$request->search}%"))
                    ->orWhereHas('categoria', fn ($cq) => $cq->where('nombre', 'like', "%{$request->search}%"))
                    ->orWhereHas('color', fn ($cq) => $cq->where('nombre', 'like', "%{$request->search}%"))
                    ->orWhereHas('variantes.color', fn ($cq) => $cq->where('nombre', 'like', "%{$request->search}%"))
                    ->orWhereHas('variantes.talla', fn ($tq) => $tq->where('nombre', 'like', "%{$request->search}%"));
            });
        }

        if ($request->categoria_id) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->marca_id) {
            $query->where('marca_id', $request->marca_id);
        }

        if ($request->genero) {
            $query->where('genero', $request->genero);
        }

        if ($request->color_id) {
            $query->where(function ($q) use ($request) {
                $q->where('color_id', $request->color_id)
                    ->orWhereHas('variantes', fn ($vq) => $vq->where('color_id', $request->color_id));
            });
        }

        if ($request->talla_id) {
            $query->where(function ($q) use ($request) {
                $q->where('talla_id', $request->talla_id)
                    ->orWhereHas('variantes', fn ($vq) => $vq->where('talla_id', $request->talla_id));
            });
        }

        if ($request->disponible !== null) {
            if ($request->disponible === '1' || $request->disponible === 'true') {
                $query->whereHas('inventario', fn ($iq) => $iq->where('stock_actual', '>', 0)->where('disponible', true));
            } else {
                $query->whereDoesntHave('inventario', fn ($iq) => $iq->where('stock_actual', '>', 0)->where('disponible', true));
            }
        }

        if ($request->precio_min) {
            $query->where('precio_venta', '>=', (float) $request->precio_min);
        }

        if ($request->precio_max) {
            $query->where('precio_venta', '<=', (float) $request->precio_max);
        }

        $sortField = 'nombre';
        $sortDir = 'asc';
        if ($request->sort) {
            match ($request->sort) {
                'precio_asc' => [$sortField = 'precio_venta', $sortDir = 'asc'],
                'precio_desc' => [$sortField = 'precio_venta', $sortDir = 'desc'],
                'nombre' => [$sortField = 'nombre', $sortDir = 'asc'],
                'recientes' => [$sortField = 'created_at', $sortDir = 'desc'],
                default => null,
            };
        }

        $productos = $query->orderBy($sortField, $sortDir)
            ->get()
            ->map(fn (Producto $producto): array => $this->formatProducto($producto))
            ->values();

        return Inertia::render('Catalogo/index', [
            'productos' => $productos,
            'destacados' => $productos->take(8)->values(),
            'categorias' => Categoria::select('id', 'nombre')
                ->whereNotIn('nombre', ['Accesorios', 'Shorts'])
                ->orderBy('nombre')
                ->get(),
            'marcas' => Marca::select('id', 'nombre')->orderBy('nombre')->get(),
            'colores' => Color::select('id', 'nombre', 'codigo_hex')->orderBy('nombre')->get(),
            'tallas' => Talla::select('id', 'nombre')
                ->orderByRaw("CASE nombre WHEN 'XS' THEN 1 WHEN 'S' THEN 2 WHEN 'M' THEN 3 WHEN 'L' THEN 4 WHEN 'XL' THEN 5 WHEN 'XXL' THEN 6 ELSE 7 END")
                ->orderBy('nombre')
                ->get(),
            'filtros' => $request->only(['search', 'categoria_id', 'marca_id', 'color_id', 'talla_id', 'genero', 'disponible', 'precio_min', 'precio_max', 'sort']),
        ]);
    }

    public function show(Producto $producto): Response
    {
        $producto->load([
            'categoria:id,nombre',
            'marca:id,nombre',
            'talla:id,nombre',
            'color:id,nombre,codigo_hex',
            'variantes.color:id,nombre,codigo_hex',
            'variantes.talla:id,nombre',
            'inventario',
            'imagenes:id,producto_id,color_id,ruta,orden',
        ]);

        $inventario = $producto->inventario;
        $stock = $inventario ? $inventario->stock_actual : 0;
        $disponible = (bool) ($inventario?->disponible ?? false);
        $comprable = $disponible && $stock > 0;

        $tallasEstandar = ['XS', 'S', 'M', 'L', 'XL'];

        $variantes = $producto->variantes
            ->filter(fn ($v): bool => in_array($v->talla?->nombre, $tallasEstandar, true))
            ->sortBy('id')
            ->map(fn ($v) => [
                'id' => $v->id,
                'color_id' => $v->color_id,
                'talla_id' => $v->talla_id,
                'color_nombre' => $v->color?->nombre ?? '---',
                'color_hex' => $v->color?->codigo_hex ?? '#ccc',
                'talla' => $v->talla?->nombre ?? '---',
                'stock' => $stock,
                'disponible' => $comprable,
            ])
            ->values()
            ->all();

        if (empty($variantes) && $producto->talla_id && $producto->color_id) {
            $variantes[] = [
                'id' => null,
                'color_id' => $producto->color_id,
                'talla_id' => $producto->talla_id,
                'color_nombre' => $producto->color->nombre ?? '---',
                'color_hex' => $producto->color->codigo_hex ?? '#ccc',
                'talla' => $producto->talla->nombre ?? '---',
                'stock' => $stock,
                'disponible' => $comprable,
            ];
        }

        $coloresUnicos = collect($variantes)
            ->groupBy('color_nombre')
            ->map(function ($group, $name) use ($stock, $comprable) {
                $first = $group->first();

                return [
                    'color_id' => $first['color_id'],
                    'color_nombre' => $name,
                    'color_hex' => $first['color_hex'],
                    'stock_total' => $stock,
                    'disponible' => $comprable,
                ];
            })
            ->values()
            ->all();

        $tallasUnicas = collect($variantes)
            ->groupBy('talla')
            ->map(function ($group, $name) use ($stock, $comprable) {
                $first = $group->first();

                return [
                    'talla_id' => $first['talla_id'],
                    'talla' => $name,
                    'stock_total' => $stock,
                    'disponible' => $comprable,
                ];
            })
            ->values()
            ->all();

        $tallasUnicas = Talla::query()
            ->select(['id', 'nombre'])
            ->whereIn('nombre', ['XS', 'S', 'M', 'L', 'XL'])
            ->get()
            ->sortBy(fn (Talla $talla): int => array_search($talla->nombre, ['XS', 'S', 'M', 'L', 'XL'], true))
            ->map(function (Talla $talla) use ($tallasUnicas): array {
                $tallaProducto = collect($tallasUnicas)->firstWhere('talla_id', $talla->id);

                return [
                    'talla_id' => $talla->id,
                    'talla' => $talla->nombre,
                    'stock_total' => $tallaProducto['stock_total'] ?? 0,
                    'disponible' => $tallaProducto['disponible'] ?? false,
                ];
            })
            ->values()
            ->all();

        $productosRelacionados = Producto::with([
            'categoria:id,nombre',
            'marca:id,nombre',
            'inventario',
        ])
            ->where('estado', 'activo')
            ->whereHas('inventario', fn ($inventoryQuery) => $inventoryQuery->where('disponible', true))
            ->where('categoria_id', $producto->categoria_id)
            ->where('id', '!=', $producto->id)
            ->latest()
            ->take(4)
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
            ]);

        return Inertia::render('Catalogo/show', [
            'producto' => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'precio_venta' => $producto->precio_venta,
                'imagen' => $producto->imagen ? asset('storage/'.$producto->imagen) : null,
                'imagenes' => $producto->imagenes
                    ->map(fn ($imagen): string => asset('storage/'.$imagen->ruta))
                    ->values()
                    ->all(),
                'imagen_colores' => $producto->imagenes
                    ->pluck('color_id')
                    ->values()
                    ->all(),
                'categoria' => $producto->categoria->nombre ?? '---',
                'categoria_id' => $producto->categoria_id,
                'marca' => $producto->marca->nombre ?? '---',
                'talla' => $producto->talla->nombre ?? '---',
                'color' => $producto->color->nombre ?? '---',
                'color_hex' => $producto->color->codigo_hex ?? '#ccc',
                'stock' => $stock,
                'disponible' => $disponible,
                'comprable' => $comprable,
                'codigo' => $producto->id,
                'variantes' => $variantes,
                'colores' => $coloresUnicos,
                'tallas' => $tallasUnicas,
            ],
            'productosRelacionados' => $productosRelacionados,
        ]);
    }

    private function formatProducto(Producto $producto): array
    {
        $inventario = $producto->inventario;
        $stock = $inventario ? $inventario->stock_actual : 0;
        $disponible = (bool) ($inventario?->disponible ?? false);

        $colores = $producto->variantes
            ->pluck('color')
            ->filter()
            ->unique('id')
            ->map(fn ($color): array => [
                'color_nombre' => $color->nombre,
                'color_hex' => $color->codigo_hex,
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

        return [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'precio_venta' => $producto->precio_venta,
            'imagen' => $producto->imagen ? asset('storage/'.$producto->imagen) : null,
            'imagenes' => collect([$producto->imagen])
                ->merge($producto->imagenes->pluck('ruta'))
                ->filter()
                ->unique()
                ->map(fn (string $ruta): string => asset('storage/'.$ruta))
                ->values()
                ->all(),
            'categoria' => $producto->categoria->nombre ?? '---',
            'marca' => $producto->marca->nombre ?? '---',
            'genero' => $producto->genero ?? '---',
            'stock' => $stock,
            'disponible' => $disponible,
            'colores' => $colores,
        ];
    }
}

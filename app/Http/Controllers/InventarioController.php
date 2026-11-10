<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInventarioDisponibilidadRequest;
use App\Models\Inventario;
use App\Models\InventarioMovimiento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventarioController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Inventario/index', [
            'inventario' => $this->getInventario($request),
            'filters' => $request->only(['search']),
        ]);
    }

    public function movimientos(Inventario $inventario): JsonResponse
    {
        $movimientos = $inventario->movimientos()
            ->withExistingOrigin()
            ->with(['user:id,name', 'inventario.producto:id,nombre'])
            ->latest()
            ->get()
            ->map(fn (InventarioMovimiento $movimiento): array => $this->formatMovement($movimiento));

        return response()->json([
            'inventario' => $inventario->load('producto:id,nombre,talla_id,color_id', 'producto.talla:id,nombre', 'producto.color:id,nombre,codigo_hex'),
            'movimientos' => $movimientos,
        ]);
    }

    public function todosLosMovimientos(): JsonResponse
    {
        $movimientos = InventarioMovimiento::query()
            ->withExistingOrigin()
            ->with(['user:id,name', 'inventario.producto:id,nombre'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (InventarioMovimiento $movimiento): array => $this->formatMovement($movimiento));

        return response()->json(['movimientos' => $movimientos]);
    }

    public function updateDisponibilidad(UpdateInventarioDisponibilidadRequest $request, Inventario $inventario): RedirectResponse
    {
        if ((int) $inventario->stock_actual <= 0 && $request->boolean('disponible')) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede marcar como disponible un producto sin stock.']);

            return to_route('inventario.index');
        }

        $inventario->update(['disponible' => $request->boolean('disponible')]);

        $estado = $inventario->disponible ? 'disponible' : 'no disponible';
        Inertia::flash('toast', ['type' => 'success', 'message' => "Producto marcado como {$estado}."]);

        return to_route('inventario.index');
    }

    private function getInventario(Request $request): mixed
    {
        $subEntradas = InventarioMovimiento::selectRaw('COALESCE(SUM(cantidad), 0)')
            ->withExistingOrigin()
            ->whereColumn('inventario_id', 'inventario.id')
            ->where('tipo', 'entrada');

        $subSalidas = InventarioMovimiento::selectRaw('COALESCE(SUM(cantidad), 0)')
            ->withExistingOrigin()
            ->whereColumn('inventario_id', 'inventario.id')
            ->where('tipo', 'salida');

        return Inventario::query()
            ->select('inventario.*')
            ->selectSub($subEntradas, 'total_entradas')
            ->selectSub($subSalidas, 'total_salidas')
            ->with([
                'producto.categoria',
                'producto.marca',
                'producto.talla',
                'producto.color',
                'producto.variantes.talla',
            ])
            ->when($request->string('search')->isNotEmpty(), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->whereHas('producto', function (Builder $productQuery) use ($search): void {
                    $productQuery->where('nombre', 'like', "%{$search}%")
                        ->orWhere('genero', 'like', "%{$search}%")
                        ->orWhereHas('categoria', fn (Builder $related) => $related->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('marca', fn (Builder $related) => $related->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('talla', fn (Builder $related) => $related->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('variantes.talla', fn (Builder $related) => $related->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('color', fn (Builder $related) => $related->where('nombre', 'like', "%{$search}%"));
                });
            })
            ->orderBy('inventario.stock_actual')
            ->get();
    }

    private function originLabel(InventarioMovimiento $movimiento): string
    {
        return match ($movimiento->origen_tipo) {
            'compra' => 'Compra',
            'venta' => 'Venta',
            'anulacion_venta' => 'Anulación de venta',
            'anulacion_compra' => 'Anulación de compra',
            'pedido' => 'Pedido',
            default => 'Movimiento del sistema',
        };
    }

    /** @return array<string, mixed> */
    private function formatMovement(InventarioMovimiento $movimiento): array
    {
        return [
            'id' => $movimiento->id,
            'inventario_id' => $movimiento->inventario_id,
            'producto' => $movimiento->inventario?->producto?->nombre ?? 'Producto no disponible',
            'tipo' => $movimiento->tipo,
            'cantidad' => $movimiento->cantidad,
            'motivo' => $movimiento->motivo,
            'origen_tipo' => $movimiento->origen_tipo,
            'origen_id' => $movimiento->origen_id,
            'origen' => $this->originLabel($movimiento),
            'user_id' => $movimiento->user_id,
            'user' => $movimiento->user,
            'created_at' => $movimiento->created_at?->format('d/m/Y H:i'),
        ];
    }
}

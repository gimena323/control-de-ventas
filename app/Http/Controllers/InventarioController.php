<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\InventarioMovimiento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InventarioController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Inventario/index', [
            'inventario' => $this->getPaginatedInventario($request),
            'filters' => $request->only(['search']),
            'movimientos' => [],
            'selectedInventarioId' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id|unique:inventario,producto_id',
            'stock_actual' => 'required|integer|min:0',
            'disponible' => 'boolean',
        ]);

        $inventario = Inventario::create($validated);

        if ($validated['stock_actual'] > 0) {
            InventarioMovimiento::create([
                'inventario_id' => $inventario->id,
                'tipo' => 'entrada',
                'cantidad' => $validated['stock_actual'],
                'motivo' => 'Stock inicial',
                'user_id' => Auth::id(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto agregado al inventario.']);

        return to_route('inventario.index');
    }

    public function update(Request $request, Inventario $inventario): RedirectResponse
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id|unique:inventario,producto_id,'.$inventario->id,
            'stock_actual' => 'required|integer|min:0',
            'disponible' => 'boolean',
        ]);

        $inventario->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Inventario actualizado exitosamente.']);

        return to_route('inventario.index');
    }

    public function destroy(Inventario $inventario): RedirectResponse
    {
        $inventario->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Producto eliminado del inventario.']);

        return to_route('inventario.index');
    }

    public function toggleDisponible(Inventario $inventario): RedirectResponse
    {
        $inventario->update(['disponible' => ! $inventario->disponible]);

        $estado = $inventario->disponible ? 'disponible' : 'no disponible';

        Inertia::flash('toast', ['type' => 'success', 'message' => "Producto marcado como {$estado}."]);

        return to_route('inventario.index');
    }

    public function registrarEntrada(Request $request, Inventario $inventario): RedirectResponse
    {
        $validated = $request->validate([
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'nullable|string|max:255',
        ]);

        InventarioMovimiento::create([
            'inventario_id' => $inventario->id,
            'tipo' => 'entrada',
            'cantidad' => $validated['cantidad'],
            'motivo' => $validated['motivo'] ?? null,
            'user_id' => Auth::id(),
        ]);

        $inventario->increment('stock_actual', $validated['cantidad']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Entrada registrada exitosamente.']);

        return to_route('inventario.index');
    }

    public function registrarSalida(Request $request, Inventario $inventario): RedirectResponse
    {
        $validated = $request->validate([
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'nullable|string|max:255',
        ]);

        if ($inventario->stock_actual < $validated['cantidad']) {
            return back()->withErrors(['cantidad' => 'Stock insuficiente para registrar esta salida.']);
        }

        InventarioMovimiento::create([
            'inventario_id' => $inventario->id,
            'tipo' => 'salida',
            'cantidad' => $validated['cantidad'],
            'motivo' => $validated['motivo'] ?? null,
            'user_id' => Auth::id(),
        ]);

        $inventario->decrement('stock_actual', $validated['cantidad']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Salida registrada exitosamente.']);

        return to_route('inventario.index');
    }

    public function movimientos(Inventario $inventario): JsonResponse
    {
        $movimientos = $inventario->movimientos()
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'inventario' => $inventario->load('producto:id,nombre', 'producto.talla:id,nombre', 'producto.color:id,nombre,codigo_hex'),
            'movimientos' => $movimientos,
        ]);
    }

    private function getPaginatedInventario(Request $request): mixed
    {
        $subEntradas = InventarioMovimiento::selectRaw('COALESCE(SUM(cantidad), 0)')
            ->whereColumn('inventario_id', 'inventario.id')
            ->where('tipo', 'entrada');

        $subSalidas = InventarioMovimiento::selectRaw('COALESCE(SUM(cantidad), 0)')
            ->whereColumn('inventario_id', 'inventario.id')
            ->where('tipo', 'salida');

        return Inventario::query()
            ->select('inventario.*')
            ->selectSub($subEntradas, 'total_entradas')
            ->selectSub($subSalidas, 'total_salidas')
            ->with(['producto.categoria', 'producto.marca', 'producto.modelo', 'producto.talla', 'producto.color'])
            ->when($request->search, function ($q, $search) {
                $q->whereHas('producto', function ($pq) use ($search) {
                    $pq->where('nombre', 'like', "%{$search}%")
                        ->orWhereHas('categoria', fn ($cq) => $cq->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('marca', fn ($mq) => $mq->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('modelo', fn ($mq) => $mq->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('talla', fn ($tq) => $tq->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('color', fn ($cq) => $cq->where('nombre', 'like', "%{$search}%"));
                });
            })
            ->orderBy('inventario.created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
    }
}

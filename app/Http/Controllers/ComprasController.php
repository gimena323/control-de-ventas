<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComprasController extends Controller
{
    public function index(Request $request): Response
    {
        $compras = Compra::query()
            ->with(['proveedor:id,nombre', 'user:id,nombres,apellido_paterno,apellido_materno', 'detalles'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('numero_compra', 'like', "%{$search}%")
                        ->orWhereHas('proveedor', fn ($q) => $q->where('nombre', 'like', "%{$search}%"));
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Compras/index', [
            'compras' => $compras,
            'proveedores' => Proveedor::select('id', 'nombre')->orderBy('nombre')->get(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha' => 'required|date',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto' => 'required|string|max:255',
            'detalles.*.producto_id' => 'nullable|exists:productos,id',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.subtotal' => 'required|numeric|min:0',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['numero_compra'] = 'CMP-'.now()->format('Ymd').'-'.str_pad((Compra::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT);
        $validated['total'] = collect($validated['detalles'])->sum('subtotal');

        $detalles = $validated['detalles'];
        unset($validated['detalles']);

        $compra = Compra::create($validated);

        foreach ($detalles as $detalle) {
            $compra->detalles()->create($detalle);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Compra registrada exitosamente.']);

        return to_route('compras.index');
    }

    public function show(Compra $compra): Response
    {
        $compra->load(['proveedor', 'user', 'detalles']);

        return Inertia::render('Compras/show', [
            'compra' => $compra,
        ]);
    }

    public function update(Request $request, Compra $compra): RedirectResponse
    {
        $validated = $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha' => 'required|date',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto' => 'required|string|max:255',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.subtotal' => 'required|numeric|min:0',
        ]);

        $validated['total'] = collect($validated['detalles'])->sum('subtotal');

        $detalles = $validated['detalles'];
        unset($validated['detalles']);

        $compra->update($validated);
        $compra->detalles()->delete();
        $compra->detalles()->createMany($detalles);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Compra actualizada exitosamente.']);

        return to_route('compras.index');
    }

    public function destroy(Compra $compra): RedirectResponse
    {
        $compra->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Compra eliminada exitosamente.']);

        return to_route('compras.index');
    }
}

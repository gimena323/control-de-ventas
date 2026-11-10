<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProveedoresController extends Controller
{
    public function index(Request $request): Response
    {
        $proveedores = Proveedor::query()
            ->withCount(['compras' => fn ($query) => $query->where('estado', '!=', 'anulada')])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('ci_nit', 'like', "%{$search}%")
                        ->orWhere('telefono', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Proveedores/index', [
            'proveedores' => $proveedores,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Proveedor $proveedor): Response
    {
        $proveedor->loadCount(['compras' => fn ($query) => $query->where('estado', '!=', 'anulada')]);
        $proveedor->load(['compras' => function ($query) {
            $query->where('estado', '!=', 'anulada')->latest()->limit(10);
        }]);

        return Inertia::render('Proveedores/show', [
            'proveedor' => $proveedor,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:60',
            'ci_nit' => 'required|string|max:20|unique:proveedores,ci_nit',
            'telefono' => 'required|string|max:20|unique:proveedores,telefono',
            'direccion' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
        ]);

        $validated['nombre'] = trim($validated['nombre']);
        $validated['ci_nit'] = trim($validated['ci_nit']);
        $validated['telefono'] = trim($validated['telefono']);

        Proveedor::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proveedor registrado correctamente.']);

        return to_route('proveedores.index');
    }

    public function update(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:60',
            'ci_nit' => 'required|string|max:20|unique:proveedores,ci_nit,'.$proveedor->id,
            'telefono' => 'required|string|max:20|unique:proveedores,telefono,'.$proveedor->id,
            'direccion' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
        ]);

        $validated['nombre'] = trim($validated['nombre']);
        $validated['ci_nit'] = trim($validated['ci_nit']);
        $validated['telefono'] = trim($validated['telefono']);

        $proveedor->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proveedor actualizado correctamente.']);

        return to_route('proveedores.index');
    }

    public function destroy(Proveedor $proveedor): RedirectResponse
    {
        $proveedor->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proveedor anulado. Sus compras e historial se conservan.']);

        return to_route('proveedores.index');
    }
}

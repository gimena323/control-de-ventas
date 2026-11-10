<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientesController extends Controller
{
    public function index(Request $request): Response
    {
        $clientes = Cliente::query()
            ->with('registradoPor:id,name,nombres,apellido_paterno,apellido_materno')
            ->withCount('ventas')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%")
                        ->orWhere('ci_nit', 'like', "%{$search}%")
                        ->orWhere('telefono', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Clientes/index', [
            'clientes' => $clientes,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Cliente $cliente): Response
    {
        $cliente->loadCount('ventas');
        $cliente->load('registradoPor:id,name,nombres,apellido_paterno,apellido_materno');
        $cliente->load(['ventas' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return Inertia::render('Clientes/show', [
            'cliente' => $cliente,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:160',
            'apellido' => 'nullable|string|max:100',
            'ci_nit' => 'required|string|max:20|unique:clientes,ci_nit',
            'telefono' => 'required|string|max:20',
            'direccion' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
        ]);

        $validated['nombre'] = trim($validated['nombre']);
        $validated['ci_nit'] = trim($validated['ci_nit']);
        $validated['telefono'] = trim($validated['telefono']);

        if (! empty($validated['apellido'])) {
            $validated['apellido'] = trim($validated['apellido']);
        }

        $validated['registrado_por_user_id'] = $request->user()->id;

        Cliente::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente registrado correctamente.']);

        return to_route('clientes.index');
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:160',
            'apellido' => 'nullable|string|max:100',
            'ci_nit' => 'required|string|max:20|unique:clientes,ci_nit,'.$cliente->id,
            'telefono' => 'required|string|max:20',
            'direccion' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
        ]);

        $validated['nombre'] = trim($validated['nombre']);
        $validated['ci_nit'] = trim($validated['ci_nit']);
        $validated['telefono'] = trim($validated['telefono']);

        if (! empty($validated['apellido'])) {
            $validated['apellido'] = trim($validated['apellido']);
        }

        $cliente->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente actualizado correctamente.']);

        return to_route('clientes.index');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $cliente->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente eliminado correctamente.']);

        return to_route('clientes.index');
    }

    public function toggleEstado(Cliente $cliente): RedirectResponse
    {
        $nuevoEstado = $cliente->isActivo() ? 'inactivo' : 'activo';
        $cliente->update(['estado' => $nuevoEstado]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Cliente marcado como {$nuevoEstado}.",
        ]);

        return to_route('clientes.index');
    }
}

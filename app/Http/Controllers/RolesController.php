<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RolesController extends Controller
{
    public function index(): Response
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('nombre')
            ->get();
        $permisos = Permission::orderBy('grupo')->orderBy('nombre')->get();

        return Inertia::render('Roles/index', [
            'roles' => $roles,
            'permisos' => $permisos,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:roles,nombre',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $role = Role::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Rol {$role->nombre} creado exitosamente."]);

        return to_route('roles.index');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => "required|string|max:50|unique:roles,nombre,{$role->id}",
            'descripcion' => 'nullable|string|max:255',
            'permisos' => 'nullable|array',
            'permisos.*' => 'string|exists:permissions,nombre',
        ]);

        $role->update([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

        if ($request->has('permisos')) {
            $role->syncPermissionsByName($validated['permisos'] ?? []);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Rol {$role->nombre} actualizado exitosamente."]);

        return to_route('roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar un rol con usuarios asignados.']);

            return to_route('roles.index');
        }

        $role->permissions()->detach();
        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol eliminado exitosamente.']);

        return to_route('roles.index');
    }
}

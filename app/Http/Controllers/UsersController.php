<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::with('roles')
            ->when($request->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellido_paterno', 'like', "%{$search}%")
                    ->orWhere('apellido_materno', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) like ?", ["%{$search}%"]);
            }))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $roles = Role::orderBy('nombre')->get(['id', 'nombre', 'descripcion']);

        return Inertia::render('Usuarios/index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombres' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,nombre',
            'telefono' => 'nullable|string|max:20',
            'fecha_ingreso' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'status' => 'nullable|string|in:activo,desactivado',
        ]);

        $user = User::create([
            'nombres' => $validated['nombres'],
            'apellido_paterno' => $validated['apellido_paterno'],
            'apellido_materno' => $validated['apellido_materno'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'telefono' => $validated['telefono'] ?? null,
            'fecha_ingreso' => $validated['fecha_ingreso'] ?? null,
            'fecha_fin' => $validated['fecha_fin'] ?? null,
            'status' => $validated['status'] ?? 'activo',
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Usuario {$user->name} creado exitosamente."]);

        return to_route('usuarios.index');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'nombres' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'required|string|max:255',
            'email' => "required|email|max:255|unique:users,email,{$user->id}",
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|exists:roles,nombre',
            'telefono' => 'nullable|string|max:20',
            'fecha_ingreso' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'status' => 'nullable|string|in:activo,desactivado',
        ]);

        $user->update([
            'nombres' => $validated['nombres'],
            'apellido_paterno' => $validated['apellido_paterno'],
            'apellido_materno' => $validated['apellido_materno'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'] ?? null,
            'fecha_ingreso' => $validated['fecha_ingreso'] ?? null,
            'fecha_fin' => $validated['fecha_fin'] ?? null,
            'role' => $validated['role'],
            'status' => $validated['status'] ?? $user->status,
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => bcrypt($validated['password'])]);
        }

        $user->assignRole($validated['role']);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Usuario {$user->name} actualizado exitosamente."]);

        return to_route('usuarios.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No puedes eliminar tu propio usuario.']);

            return to_route('usuarios.index');
        }

        $user->roles()->detach();
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario eliminado exitosamente.']);

        return to_route('usuarios.index');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $user->status = $user->isActivo() ? 'desactivado' : 'activo';
        $user->save();

        $action = $user->isActivo() ? 'activado' : 'desactivado';

        Inertia::flash('toast', ['type' => 'success', 'message' => "Usuario {$user->name} {$action} exitosamente."]);

        return to_route('usuarios.index');
    }
}

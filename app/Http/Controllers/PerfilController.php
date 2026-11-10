<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PerfilController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Perfil/index', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'cliente' => $request->user()->cliente,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nombres' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => "required|email|max:255|unique:users,email,{$user->id}",
            'ci_nit' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('clientes', 'ci_nit')->ignore($user->cliente?->id),
            ],
            'direccion' => ['nullable', 'string', 'max:500'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $user->cliente()->updateOrCreate(
            [],
            [
                'nombre' => trim(implode(' ', array_filter([
                    $validated['nombres'],
                    $validated['apellido_paterno'],
                    $validated['apellido_materno'],
                ]))),
                'apellido' => null,
                'email' => $validated['email'],
                'telefono' => $validated['telefono'] ?? null,
                'ci_nit' => $validated['ci_nit'] ?? null,
                'direccion' => $validated['direccion'] ?? null,
                'estado' => 'activo',
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Perfil actualizado exitosamente.']);

        return to_route('perfil');
    }
}

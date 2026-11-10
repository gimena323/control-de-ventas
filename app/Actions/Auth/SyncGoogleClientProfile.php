<?php

namespace App\Actions\Auth;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Str;

class SyncGoogleClientProfile
{
    public function execute(User $user): Cliente
    {
        $nameParts = preg_split('/\s+/', trim($user->name), 2) ?: [];

        $cliente = Cliente::query()
            ->where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->lockForUpdate()
            ->first();

        if (! $cliente) {
            $cliente = new Cliente;
            $cliente->fill([
                'nombre' => Str::limit($nameParts[0] ?? $user->name, 60, ''),
                'apellido' => isset($nameParts[1]) ? Str::limit($nameParts[1], 100, '') : null,
                'email' => $user->email,
            ]);
        }

        $cliente->user()->associate($user);
        $cliente->save();

        return $cliente;
    }
}

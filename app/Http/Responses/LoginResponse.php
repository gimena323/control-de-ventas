<?php

namespace App\Http\Responses;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        $user = $request->user();

        $user->syncRolesFromLegacyColumn();
        $user->ensureRolesHavePermissions();
        $user->loadMissing('roles.permissions');

        $redirectTo = match (true) {
            $user->hasRole(Role::CLIENTE) => '/catalogo',
            $user->hasPermission(Permission::PanelVer->value) => route('dashboard'),
            $user->hasPermission(Permission::ClientesVer->value) => '/clientes',
            default => '/',
        };

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended($redirectTo);
    }
}

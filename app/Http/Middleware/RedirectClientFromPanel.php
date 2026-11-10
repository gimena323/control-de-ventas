<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectClientFromPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole(Role::CLIENTE)) {
            return redirect()->route('catalogo');
        }

        return $next($request);
    }
}

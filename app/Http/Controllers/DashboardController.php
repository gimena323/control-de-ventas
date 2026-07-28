<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();

        $totalProductos = 0;
        $stockBajo = 0;
        $ventasHoy = 0;
        $ventasMes = 0;
        $stockAlertas = [];
        $ventasRecientes = [];

        $totalClientes = Cliente::count();
        $totalUsuarios = User::count();

        $isVendedor = $user && $user->hasRole('vendedor');

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalProductos' => $totalProductos,
                'stockBajo' => $stockBajo,
                'ventasHoy' => $ventasHoy,
                'ventasMes' => $ventasMes,
                'totalClientes' => $totalClientes,
                'totalUsuarios' => $totalUsuarios,
            ],
            'stockAlertas' => $stockAlertas,
            'ventasRecientes' => $ventasRecientes,
            'isVendedor' => $isVendedor,
        ]);
    }
}

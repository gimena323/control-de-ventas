<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();

        $totalProductos = Producto::count();
        $stockBajo = Inventario::where('stock_actual', '<=', 10)->count();

        $ventasCompletadas = Venta::query()->whereNull('anulada_en');
        $totalVentas = (clone $ventasCompletadas)->count();
        $comprasCompletadas = Compra::query()->where('estado', 'completada');
        $totalCompras = (clone $comprasCompletadas)->count();
        $ventasHoy = (float) (clone $ventasCompletadas)
            ->whereDate('fecha', today())
            ->sum('total');
        $ventasMes = (float) (clone $ventasCompletadas)
            ->whereBetween('fecha', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total');

        $inicioGrafico = today()->subDays(6);
        $ventasPorFecha = Venta::query()
            ->select(['fecha', 'total'])
            ->whereNull('anulada_en')
            ->whereBetween('fecha', [$inicioGrafico, today()])
            ->get()
            ->groupBy(fn (Venta $venta): string => $venta->fecha->toDateString());
        $comprasPorFecha = Compra::query()
            ->select(['fecha', 'total'])
            ->where('estado', 'completada')
            ->whereBetween('fecha', [$inicioGrafico, today()])
            ->get()
            ->groupBy(fn (Compra $compra): string => $compra->fecha->toDateString());

        $estadisticas = collect(range(0, 6))
            ->map(function (int $dias) use ($inicioGrafico, $ventasPorFecha, $comprasPorFecha): array {
                $fecha = $inicioGrafico->copy()->addDays($dias);
                $clave = $fecha->toDateString();

                return [
                    'fecha' => $fecha->format('d/m'),
                    'ventas' => (float) $ventasPorFecha->get($clave, collect())->sum('total'),
                    'compras' => (float) $comprasPorFecha->get($clave, collect())->sum('total'),
                ];
            })
            ->all();

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
                'totalVentas' => $totalVentas,
                'totalCompras' => $totalCompras,
                'totalProveedores' => Proveedor::count(),
            ],
            'estadisticas' => $estadisticas,
            'stockAlertas' => [],
            'ventasRecientes' => [],
            'isVendedor' => $isVendedor,
        ]);
    }
}

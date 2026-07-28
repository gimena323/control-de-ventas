<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ReportesController extends Controller
{
    public function compras(Request $request): Response
    {
        $compras = $this->getComprasData($request);

        return Inertia::render('Reportes/compras', [
            'reporte' => $compras,
            'proveedores' => Proveedor::select('id', 'nombre')->orderBy('nombre')->get(),
            'categorias' => Categoria::select('id', 'nombre')->orderBy('nombre')->get(),
            'filtros' => $request->only(['fecha_inicio', 'fecha_fin', 'proveedor_id', 'categoria_id']),
        ]);
    }

    public function ventas(Request $request): Response
    {
        $ventas = $this->getVentasData($request);

        return Inertia::render('Reportes/ventas', [
            'reporte' => $ventas,
            'usuarios' => User::select('id', 'name', 'nombres', 'apellido_paterno', 'apellido_materno')->orderBy('name')->get(),
            'categorias' => Categoria::select('id', 'nombre')->orderBy('nombre')->get(),
            'filtros' => $request->only(['fecha_inicio', 'fecha_fin', 'user_id', 'categoria_id']),
        ]);
    }

    public function comprasPdf(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $compras = $this->getComprasData($request);

        $pdf = Pdf::loadView('pdf.compras', [
            'reporte' => $compras,
            'filtros' => $request->only(['fecha_inicio', 'fecha_fin', 'proveedor_id', 'categoria_id']),
            'usuario' => Auth::user(),
            'fechaGeneracion' => now()->format('d-m-Y H:i:s'),
            'rangoFechas' => $this->getRangoFechas($request),
        ])->setPaper('letter', 'landscape');

        return $pdf->download('reporte_compras_'.now()->format('Y-m-d').'.pdf');
    }

    public function ventasPdf(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $ventas = $this->getVentasData($request);

        $pdf = Pdf::loadView('pdf.ventas', [
            'reporte' => $ventas,
            'filtros' => $request->only(['fecha_inicio', 'fecha_fin', 'user_id', 'categoria_id']),
            'usuario' => Auth::user(),
            'fechaGeneracion' => now()->format('d-m-Y H:i:s'),
            'rangoFechas' => $this->getRangoFechas($request),
        ])->setPaper('letter', 'landscape');

        return $pdf->download('reporte_ventas_'.now()->format('Y-m-d').'.pdf');
    }

    private function getComprasData(Request $request): array
    {
        $query = Compra::query()
            ->with(['proveedor:id,nombre', 'detalles.productoRelacion.categoria:id,nombre', 'detalles.productoRelacion.marca:id,nombre', 'detalles.productoRelacion.modelo:id,nombre']);

        if ($request->fecha_inicio) {
            $query->where('fecha', '>=', $request->fecha_inicio);
        }
        if ($request->fecha_fin) {
            $query->where('fecha', '<=', $request->fecha_fin);
        }
        if ($request->proveedor_id) {
            $query->where('proveedor_id', $request->proveedor_id);
        }
        if ($request->categoria_id) {
            $query->whereHas('detalles.productoRelacion', function ($q) use ($request) {
                $q->where('categoria_id', $request->categoria_id);
            });
        }

        $compras = $query->orderBy('fecha', 'desc')->get();

        $detalles = [];
        foreach ($compras as $compra) {
            foreach ($compra->detalles as $detalle) {
                $producto = $detalle->productoRelacion;
                $detalles[] = [
                    'compra_id' => $compra->id,
                    'numero_compra' => $compra->numero_compra,
                    'fecha' => $compra->fecha,
                    'proveedor' => $compra->proveedor->nombre ?? '---',
                    'categoria' => $producto?->categoria->nombre ?? '---',
                    'marca' => $producto?->marca->nombre ?? '---',
                    'modelo' => $producto?->modelo->nombre ?? '---',
                    'descripcion' => $detalle->producto,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'subtotal' => $detalle->subtotal,
                ];
            }
        }

        $resumenProveedor = collect($detalles)
            ->groupBy('proveedor')
            ->map(function ($items, $proveedor) {
                return [
                    'proveedor' => $proveedor,
                    'total_productos' => $items->sum('cantidad'),
                    'total_compra' => $items->sum('subtotal'),
                ];
            })
            ->sortByDesc('total_compra')
            ->values()
            ->toArray();

        return [
            'detalles' => $detalles,
            'resumen_proveedor' => $resumenProveedor,
            'total_compras' => $compras->count(),
            'total_productos' => collect($detalles)->sum('cantidad'),
            'monto_total' => collect($detalles)->sum('subtotal'),
        ];
    }

    private function getVentasData(Request $request): array
    {
        $query = Venta::query()
            ->with(['cliente:id,nombre,apellido', 'detalles.productoRelacion.categoria:id,nombre', 'detalles.productoRelacion.marca:id,nombre', 'detalles.productoRelacion.modelo:id,nombre'])
            ->where('estado', 'completada');

        if ($request->fecha_inicio) {
            $query->where('fecha', '>=', $request->fecha_inicio);
        }
        if ($request->fecha_fin) {
            $query->where('fecha', '<=', $request->fecha_fin);
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->categoria_id) {
            $query->whereHas('detalles.productoRelacion', function ($q) use ($request) {
                $q->where('categoria_id', $request->categoria_id);
            });
        }

        $ventas = $query->orderBy('fecha', 'desc')->get();

        $detalles = [];
        foreach ($ventas as $venta) {
            foreach ($venta->detalles as $detalle) {
                $producto = $detalle->productoRelacion;
                $clienteNombre = $venta->cliente ? trim($venta->cliente->nombre.' '.($venta->cliente->apellido ?? '')) : '---';
                $detalles[] = [
                    'venta_id' => $venta->id,
                    'numero_venta' => $venta->numero_venta,
                    'fecha' => $venta->fecha,
                    'cliente' => $clienteNombre,
                    'categoria' => $producto?->categoria->nombre ?? '---',
                    'marca' => $producto?->marca->nombre ?? '---',
                    'modelo' => $producto?->modelo->nombre ?? '---',
                    'descripcion' => $detalle->producto,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'subtotal' => $detalle->subtotal,
                ];
            }
        }

        $resumenCliente = collect($detalles)
            ->groupBy('cliente')
            ->map(function ($items, $cliente) {
                return [
                    'cliente' => $cliente,
                    'total_productos' => $items->sum('cantidad'),
                    'total_venta' => $items->sum('subtotal'),
                ];
            })
            ->sortByDesc('total_venta')
            ->values()
            ->toArray();

        return [
            'detalles' => $detalles,
            'resumen_cliente' => $resumenCliente,
            'total_ventas' => $ventas->count(),
            'total_productos' => collect($detalles)->sum('cantidad'),
            'monto_total' => collect($detalles)->sum('subtotal'),
        ];
    }

    private function getRangoFechas(Request $request): string
    {
        $inicio = $request->fecha_inicio ? now()->parse($request->fecha_inicio)->format('d/m/Y') : 'Inicio';
        $fin = $request->fecha_fin ? now()->parse($request->fecha_fin)->format('d/m/Y') : 'Fin';

        return "{$inicio} - {$fin}";
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
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
            'proveedores' => Proveedor::withTrashed()->select('id', 'nombre')->orderBy('nombre')->get(),
            'categorias' => Categoria::select('id', 'nombre')->orderBy('nombre')->get(),
            'filtros' => $request->only(['fecha_inicio', 'fecha_fin', 'proveedor_id', 'categoria_id']),
        ]);
    }

    public function ventas(Request $request): Response
    {
        $ventas = $this->getVentasData($request);

        return Inertia::render('Reportes/ventas', [
            'reporte' => $ventas,
            'usuarios' => $request->user()->isAdmin()
                ? User::select('id', 'name', 'nombres', 'apellido_paterno', 'apellido_materno')->orderBy('name')->get()
                : [],
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

        $filename = 'reporte_compras_'.now()->format('Y-m-d_H-i-s').'.pdf';

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
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

        $filename = 'reporte_ventas_'.now()->format('Y-m-d_H-i-s').'.pdf';

        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    public function comprasVistaPdf(Request $request): Response
    {
        return $this->pdfPreview($request, 'Reporte Detallado de Compras', 'reportes.compras.pdf', 'reportes.compras', 'reporte_compras');
    }

    public function ventasVistaPdf(Request $request): Response
    {
        return $this->pdfPreview($request, 'Reporte Detallado de Ventas', 'reportes.ventas.pdf', 'reportes.ventas', 'reporte_ventas');
    }

    private function getComprasData(Request $request): array
    {
        $query = Compra::query()
            ->with([
                'proveedor:id,nombre',
                'detalles' => function ($q) {
                    $q->with([
                        'productoRelacion.categoria:id,nombre',
                        'productoRelacion.marca:id,nombre',
                        'productoRelacion.inventario:id,producto_id,stock_actual',
                    ]);
                },
            ])
            ->where('estado', 'completada');

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
                    'categoria' => $producto?->categoria?->nombre ?? '---',
                    'marca' => $producto?->marca?->nombre ?? '---',
                    'genero' => $producto?->genero ?? '---',
                    'descripcion' => $detalle->producto,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'subtotal' => $detalle->subtotal,
                    'stock' => $producto?->inventario?->stock_actual ?? 0,
                ];
            }
        }

        $resumenProducto = collect($detalles)
            ->groupBy('descripcion')
            ->map(function ($items, $descripcion) {
                $cantidad = $items->sum('cantidad');
                $subtotal = $items->sum('subtotal');

                return [
                    'descripcion' => $descripcion,
                    'categoria' => $items->first()['categoria'],
                    'marca' => $items->first()['marca'],
                    'genero' => $items->first()['genero'],
                    'cantidad' => $cantidad,
                    'stock' => $items->first()['stock'],
                    'precio_unitario' => $cantidad > 0 ? round($subtotal / $cantidad, 2) : 0,
                    'subtotal' => $subtotal,
                ];
            })
            ->sortByDesc('subtotal')
            ->values()
            ->toArray();

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
            'resumen_producto' => $resumenProducto,
            'resumen_proveedor' => $resumenProveedor,
            'total_compras' => $compras->count(),
            'total_productos' => collect($detalles)->sum('cantidad'),
            'monto_total' => collect($detalles)->sum('subtotal'),
        ];
    }

    private function getVentasData(Request $request): array
    {
        $query = Venta::query()
            ->with([
                'cliente:id,nombre,apellido',
                'detalles' => function ($q) {
                    $q->with([
                        'productoRelacion.categoria:id,nombre',
                        'productoRelacion.marca:id,nombre',
                        'productoRelacion.inventario:id,producto_id,stock_actual',
                    ]);
                },
            ])
            ->where('estado', 'completada');

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->fecha_inicio) {
            $query->where('fecha', '>=', $request->fecha_inicio);
        }
        if ($request->fecha_fin) {
            $query->where('fecha', '<=', $request->fecha_fin);
        }
        if ($request->user()->isAdmin() && $request->user_id) {
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
                $clienteNombre = $venta->cliente
                    ? trim($venta->cliente->nombre.' '.($venta->cliente->apellido ?? ''))
                    : '---';

                $detalles[] = [
                    'venta_id' => $venta->id,
                    'numero_venta' => $venta->numero_venta,
                    'fecha' => $venta->fecha,
                    'cliente' => $clienteNombre,
                    'categoria' => $producto?->categoria?->nombre ?? '---',
                    'marca' => $producto?->marca?->nombre ?? '---',
                    'genero' => $producto?->genero ?? '---',
                    'descripcion' => $detalle->producto,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'subtotal' => $detalle->subtotal,
                    'stock' => $producto?->inventario?->stock_actual ?? 0,
                ];
            }
        }

        $resumenProducto = collect($detalles)
            ->groupBy('descripcion')
            ->map(function ($items, $descripcion) {
                $cantidad = $items->sum('cantidad');
                $subtotal = $items->sum('subtotal');

                return [
                    'descripcion' => $descripcion,
                    'categoria' => $items->first()['categoria'],
                    'marca' => $items->first()['marca'],
                    'genero' => $items->first()['genero'],
                    'cantidad' => $cantidad,
                    'stock' => $items->first()['stock'],
                    'precio_unitario' => $cantidad > 0 ? round($subtotal / $cantidad, 2) : 0,
                    'subtotal' => $subtotal,
                ];
            })
            ->sortByDesc('subtotal')
            ->values()
            ->toArray();

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
            'resumen_producto' => $resumenProducto,
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

    private function pdfPreview(Request $request, string $titulo, string $pdfRoute, string $returnRoute, string $filenamePrefix): Response
    {
        $filters = $request->only(['fecha_inicio', 'fecha_fin', 'proveedor_id', 'user_id', 'categoria_id']);

        return Inertia::render('Reportes/pdf-viewer', [
            'titulo' => $titulo,
            'pdfUrl' => route($pdfRoute, $filters),
            'downloadUrl' => route($pdfRoute, [...$filters, 'download' => 1]),
            'returnUrl' => route($returnRoute, $filters),
            'filename' => $filenamePrefix.'_'.now()->format('Y-m-d_H-i-s').'.pdf',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\InventarioMovimiento;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VentasController extends Controller
{
    public function index(Request $request): Response
    {
        $ventas = Venta::query()
            ->with(['cliente:id,nombre,apellido', 'user:id,name,nombres,apellido_paterno,apellido_materno', 'detalles'])
            ->where('estado', 'completada')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('numero_venta', 'like', "%{$search}%")
                        ->orWhere('observaciones', 'like', "%{$search}%")
                        ->orWhereHas('cliente', fn ($cq) => $cq->where('nombre', 'like', "%{$search}%")->orWhere('apellido', 'like', "%{$search}%"))
                        ->orWhereHas('detalles', fn ($dq) => $dq->where('producto', 'like', "%{$search}%"));
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Ventas/index', [
            'ventas' => $ventas,
            'clientes' => \App\Models\Cliente::select('id', 'nombre', 'apellido', 'ci_nit')->orderBy('nombre')->get(),
            'productos' => Producto::query()
                ->with(['inventario', 'categoria:id,nombre', 'marca:id,nombre', 'modelo:id,nombre'])
                ->whereHas('inventario', fn ($q) => $q->where('disponible', true))
                ->orderBy('nombre')
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'descripcion' => $p->descripcion,
                    'precio_venta' => $p->precio_venta,
                    'imagen' => $p->imagen,
                    'categoria' => $p->categoria,
                    'marca' => $p->marca,
                    'modelo' => $p->modelo,
                    'stock' => $p->inventario?->stock_actual ?? 0,
                ]),
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Venta $venta): Response
    {
        $venta->load([
            'cliente:id,nombre,apellido,ci_nit,telefono,email',
            'user:id,name,nombres,apellido_paterno,apellido_materno',
            'anuladaPor:id,name,nombres,apellido_paterno,apellido_materno',
            'detalles.productoRelacion:id,nombre',
        ]);

        return Inertia::render('Ventas/show', [
            'venta' => $venta,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'fecha' => 'required|date',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.descuento' => 'nullable|numeric|min:0',
        ]);

        $total = 0;
        foreach ($validated['detalles'] as $detalle) {
            $producto = Producto::with('inventario')->find($detalle['producto_id']);
            $inventario = $producto->inventario;

            if (! $inventario || $inventario->stock_actual < $detalle['cantidad']) {
                $stockDisponible = $inventario?->stock_actual ?? 0;
                return back()->withErrors([
                    "detalles.{$detalle['producto_id']}.cantidad" => "Stock insuficiente para \"{$producto->nombre}\". Disponible: {$stockDisponible}.",
                ]);
            }

            $subtotal = ($detalle['cantidad'] * $detalle['precio_unitario']) - ($detalle['descuento'] ?? 0);
            $detalle['subtotal'] = max(0, $subtotal);
            $detalle['producto'] = $producto->nombre;
            $total += $detalle['subtotal'];
        }

        $ventaData = [
            'cliente_id' => $validated['cliente_id'],
            'user_id' => Auth::id(),
            'numero_venta' => 'VNT-'.now()->format('Ymd').'-'.str_pad((Venta::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT),
            'total' => $total,
            'fecha' => $validated['fecha'],
            'estado' => 'completada',
        ];

        DB::beginTransaction();

        try {
            $venta = Venta::create($ventaData);

            foreach ($validated['detalles'] as $detalle) {
                $venta->detalles()->create([
                    'producto_id' => $detalle['producto_id'],
                    'producto' => $detalle['producto'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'descuento' => $detalle['descuento'] ?? 0,
                    'subtotal' => $detalle['subtotal'],
                ]);

                $inventario = Inventario::where('producto_id', $detalle['producto_id'])->first();
                $inventario->decrement('stock_actual', $detalle['cantidad']);

                InventarioMovimiento::create([
                    'inventario_id' => $inventario->id,
                    'tipo' => 'salida',
                    'cantidad' => $detalle['cantidad'],
                    'motivo' => "Venta #{$venta->numero_venta}",
                    'user_id' => Auth::id(),
                ]);
            }

            DB::commit();

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Venta registrada correctamente.']);

            return to_route('ventas.index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors([' venta' => 'Error al registrar la venta: '.$e->getMessage()]);
        }
    }

    public function anular(Venta $venta): RedirectResponse
    {
        if ($venta->isAnulada()) {
            return back()->withErrors(['venta' => 'Esta venta ya fue anulada.']);
        }

        DB::beginTransaction();

        try {
            $venta->update([
                'estado' => 'anulada',
                'anulada_en' => now(),
                'anulada_por_user_id' => Auth::id(),
            ]);

            foreach ($venta->detalles as $detalle) {
                if ($detalle->producto_id) {
                    $inventario = Inventario::where('producto_id', $detalle->producto_id)->first();

                    if ($inventario) {
                        $inventario->increment('stock_actual', $detalle->cantidad);

                        InventarioMovimiento::create([
                            'inventario_id' => $inventario->id,
                            'tipo' => 'entrada',
                            'cantidad' => $detalle->cantidad,
                            'motivo' => "Anulación de venta #{$venta->numero_venta}",
                            'user_id' => Auth::id(),
                        ]);
                    }
                }
            }

            DB::commit();

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Venta anulada correctamente.']);

            return to_route('ventas.index');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['venta' => 'Error al anular la venta: '.$e->getMessage()]);
        }
    }

    public function anuladas(Request $request): Response
    {
        $ventas = Venta::query()
            ->with(['cliente:id,nombre,apellido', 'anuladaPor:id,name,nombres,apellido_paterno,apellido_materno', 'detalles'])
            ->where('estado', 'anulada')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('numero_venta', 'like', "%{$search}%")
                        ->orWhereHas('cliente', fn ($cq) => $cq->where('nombre', 'like', "%{$search}%")->orWhere('apellido', 'like', "%{$search}%"))
                        ->orWhereHas('detalles', fn ($dq) => $dq->where('producto', 'like', "%{$search}%"));
                });
            })
            ->orderBy('anulada_en', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Ventas/anuladas', [
            'ventas' => $ventas,
            'filters' => $request->only(['search']),
        ]);
    }

    public function productos(): \Illuminate\Http\JsonResponse
    {
        $productos = Producto::query()
            ->with(['inventario', 'categoria:id,nombre', 'marca:id,nombre', 'modelo:id,nombre', 'talla:id,nombre', 'color:id,nombre'])
            ->whereHas('inventario', fn ($q) => $q->where('disponible', true))
            ->orderBy('nombre')
            ->get();

        return response()->json($productos);
    }
}

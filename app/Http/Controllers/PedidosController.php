<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoVariante;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PedidosController extends Controller
{
    public function misPedidos(Request $request): Response
    {
        $pedido = Pedido::with('detalles')
            ->where('user_id', $request->user()->id)
            ->whereNotIn('estado', [Pedido::ESTADO_PENDIENTE, Pedido::ESTADO_CANCELADO])
            ->latest('created_at')
            ->first();

        return Inertia::render('Pedidos/index', [
            'pedidos' => $pedido ? [$pedido->toClienteArray()] : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.producto_variante_id' => 'nullable|integer|exists:producto_variantes,id',
            'productos.*.nombre' => 'required|string|max:255',
            'productos.*.precio' => 'required|numeric|min:0',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.color' => 'nullable|string|max:100',
            'productos.*.talla' => 'nullable|string|max:100',
            'productos.*.imagen' => 'nullable|string|max:500',
            'metodo_pago' => 'nullable|string|max:100',
            'comprobante_pago' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $numero = $this->generarNumero();

        $comprobantePago = $request->hasFile('comprobante_pago')
            ? $request->file('comprobante_pago')->store('comprobantes', 'public')
            : null;

        try {
            $pedido = DB::transaction(function () use ($numero, $request, $validated, $comprobantePago): Pedido {
                $pedido = Pedido::create([
                    'numero' => $numero,
                    'user_id' => $request->user()->id,
                    'total' => 0,
                    'estado' => Pedido::ESTADO_PENDIENTE,
                    'metodo_pago' => $validated['metodo_pago'] ?? 'Reserva por WhatsApp',
                    'comprobante_pago' => $comprobantePago,
                ]);

                $total = 0.0;

                foreach ($validated['productos'] as $item) {
                    $producto = Producto::query()
                        ->with(['inventario', 'variantes'])
                        ->findOrFail($item['producto_id']);

                    $this->assertProductoDisponible($producto, $item);

                    $precio = (float) $producto->precio_venta;
                    $subtotal = $precio * $item['cantidad'];

                    $pedido->detalles()->create([
                        'producto_id' => $producto->id,
                        'producto_variante_id' => $item['producto_variante_id'] ?? null,
                        'producto' => $producto->nombre,
                        'cantidad' => $item['cantidad'],
                        'precio' => $precio,
                        'subtotal' => $subtotal,
                        'color' => $item['color'] ?? null,
                        'talla' => $item['talla'] ?? null,
                        'imagen' => $item['imagen'] ?? null,
                    ]);

                    $total += $subtotal;
                }

                $pedido->update(['total' => $total]);

                return $pedido->fresh('detalles');
            }, 3);

            session()->flash('lastOrder', $pedido->toClienteArray());

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Solicitud de compra registrada. La vendedora debe confirmarla para descontar el stock.',
            ]);

            return back();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->withErrors(['pedido' => 'Error al registrar el pedido: '.$e->getMessage()]);
        }
    }

    /**
     * @param  array{producto_id: int, producto_variante_id?: int|null, cantidad: int}  $item
     */
    private function assertProductoDisponible(Producto $producto, array $item): void
    {
        $inventario = $producto->inventario;

        if (! $inventario || ! $inventario->disponible || $inventario->stock_actual < $item['cantidad']) {
            throw ValidationException::withMessages([
                'pedido' => "Stock insuficiente para «{$producto->nombre}». Disponible: ".($inventario?->stock_actual ?? 0).'.',
            ]);
        }

        if (empty($item['producto_variante_id']) || ! $producto->variantes->isNotEmpty()) {
            return;
        }

        $variantePertenece = ProductoVariante::query()
            ->whereKey($item['producto_variante_id'])
            ->where('producto_id', $producto->id)
            ->exists();

        if (! $variantePertenece) {
            throw ValidationException::withMessages([
                'pedido' => "La talla y el color seleccionados no pertenecen a «{$producto->nombre}».",
            ]);
        }
    }

    private function generarNumero(): string
    {
        $numero = 'PED-'.now()->format('Ymd').'-'.str_pad((Pedido::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT);

        while (Pedido::where('numero', $numero)->exists()) {
            $numero = 'PED-'.now()->format('Ymd').'-'.str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        return $numero;
    }
}

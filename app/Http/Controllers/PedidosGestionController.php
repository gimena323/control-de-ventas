<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\RecordInventoryMovement;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoVariante;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PedidosGestionController extends Controller
{
    public function __construct(private RecordInventoryMovement $recordInventoryMovement) {}

    public function index(Request $request): Response
    {
        $pedidos = Pedido::query()
            ->with(['user:id,name,nombres,apellido_paterno,apellido_materno,email,telefono', 'detalles'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('numero', 'like', "%{$search}%")
                        ->orWhere('estado', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('nombres', 'like', "%{$search}%")
                            ->orWhere('apellido_paterno', 'like', "%{$search}%")
                            ->orWhere('apellido_materno', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('detalles', fn ($dq) => $dq->where('producto', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('PedidosAdmin/index', [
            'pedidos' => $pedidos->through(fn (Pedido $pedido) => $this->formatPedido($pedido)),
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Pedido $pedido): Response
    {
        $pedido->load(['user:id,name,nombres,apellido_paterno,apellido_materno,email,telefono', 'detalles']);

        return Inertia::render('PedidosAdmin/show', [
            'pedido' => $this->formatPedido($pedido),
        ]);
    }

    public function updateEstado(Request $request, Pedido $pedido): RedirectResponse
    {
        $validated = $request->validate([
            'estado' => ['required', 'string', 'in:'.implode(',', Pedido::ESTADOS)],
        ]);

        DB::transaction(function () use ($pedido, $validated): void {
            if ($this->debeRegistrarSalida($pedido, $validated['estado'])) {
                $this->registrarSalidasDelPedido($pedido);
            }

            $pedido->update(['estado' => $validated['estado']]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pedido actualizado a "'.$validated['estado'].'".']);

        return back();
    }

    public function confirmar(Pedido $pedido): RedirectResponse
    {
        if ($pedido->estado !== Pedido::ESTADO_PENDIENTE) {
            return back()->withErrors(['pedido' => 'Solo se pueden confirmar pedidos en estado Pendiente.']);
        }

        DB::transaction(function () use ($pedido): void {
            $this->registrarSalidasDelPedido($pedido);
            $pedido->update(['estado' => Pedido::ESTADO_CONFIRMADO]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pedido confirmado correctamente.']);

        return back();
    }

    public function preparar(Pedido $pedido): RedirectResponse
    {
        if (! in_array($pedido->estado, [Pedido::ESTADO_PENDIENTE, Pedido::ESTADO_CONFIRMADO], true)) {
            return back()->withErrors(['pedido' => 'Solo se pueden preparar pedidos Pendientes o Confirmados.']);
        }

        DB::transaction(function () use ($pedido): void {
            if ($pedido->estado === Pedido::ESTADO_PENDIENTE) {
                $this->registrarSalidasDelPedido($pedido);
            }

            $pedido->update(['estado' => Pedido::ESTADO_EN_PREPARACION]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pedido en preparación.']);

        return back();
    }

    public function entregar(Pedido $pedido): RedirectResponse
    {
        if (in_array($pedido->estado, [Pedido::ESTADO_ENTREGADO, Pedido::ESTADO_CANCELADO], true)) {
            return back()->withErrors(['pedido' => 'Este pedido ya no puede marcarse como entregado.']);
        }

        DB::transaction(function () use ($pedido): void {
            if ($this->debeRegistrarSalida($pedido, Pedido::ESTADO_ENTREGADO)) {
                $this->registrarSalidasDelPedido($pedido);
            }

            $pedido->update(['estado' => Pedido::ESTADO_ENTREGADO]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pedido marcado como entregado.']);

        return back();
    }

    public function cancelar(Pedido $pedido): RedirectResponse
    {
        if (in_array($pedido->estado, [Pedido::ESTADO_ENTREGADO, Pedido::ESTADO_CANCELADO], true)) {
            return back()->withErrors(['pedido' => 'Este pedido ya no puede cancelarse.']);
        }

        DB::transaction(function () use ($pedido): void {
            $pedido->loadMissing('venta.detalles.variante');

            if ($pedido->venta) {
                $this->anularVentaDelPedido($pedido);
            } else {
                $teniaSalida = $pedido->estado !== Pedido::ESTADO_PENDIENTE;

                if ($teniaSalida) {
                    $this->restaurarVariantesDelPedido($pedido);
                }

                $this->recordInventoryMovement->forgetOrigin('pedido', $pedido->id);
            }

            $pedido->update(['estado' => Pedido::ESTADO_CANCELADO]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pedido cancelado y stock revertido.']);

        return back();
    }

    private function formatPedido(Pedido $pedido): array
    {
        $cliente = $pedido->user;

        return [
            'id' => $pedido->id,
            'numero' => $pedido->numero,
            'fecha' => $pedido->created_at->toISOString(),
            'estado' => $pedido->estado,
            'total' => (float) $pedido->total,
            'metodo_pago' => $pedido->metodo_pago,
            'comprobante_pago' => $pedido->comprobante_pago,
            'cliente' => [
                'id' => $cliente?->id,
                'nombre' => $cliente?->name,
                'email' => $cliente?->email,
                'telefono' => $cliente?->telefono,
            ],
            'productos' => $pedido->detalles->map(fn ($detalle) => [
                'producto_id' => $detalle->producto_id,
                'nombre' => $detalle->producto,
                'precio' => (float) $detalle->precio,
                'cantidad' => $detalle->cantidad,
                'subtotal' => (float) $detalle->subtotal,
                'color' => $detalle->color,
                'talla' => $detalle->talla,
                'imagen' => $detalle->imagen,
            ])->values(),
        ];
    }

    private function registrarSalidasDelPedido(Pedido $pedido): void
    {
        $pedido->loadMissing('detalles');

        foreach ($pedido->detalles as $detalle) {
            $producto = Producto::find($detalle->producto_id);

            if (! $producto) {
                continue;
            }

            $variante = ProductoVariante::query()
                ->with(['talla:id,nombre', 'color:id,nombre'])
                ->where('producto_id', $producto->id)
                ->whereHas('talla', fn ($query) => $query->where('nombre', $detalle->talla))
                ->whereHas('color', fn ($query) => $query->where('nombre', $detalle->color))
                ->lockForUpdate()
                ->first();

            if ($producto->variantes()->exists() && ! $variante) {
                throw ValidationException::withMessages([
                    'pedido' => "No se encontró la combinación de talla y color para «{$producto->nombre}».",
                ]);
            }

            if ($variante && (! $variante->disponible || $variante->stock < $detalle->cantidad)) {
                throw ValidationException::withMessages([
                    'pedido' => "Stock insuficiente para «{$producto->nombre}» en talla {$detalle->talla} y color {$detalle->color}. Disponible: {$variante->stock}.",
                ]);
            }

            $this->recordInventoryMovement->salida(
                $producto,
                (int) $detalle->cantidad,
                'pedido',
                $pedido->id,
                auth()->id(),
                "Pedido #{$pedido->numero} confirmado",
            );

            if ($variante) {
                $variante->decrement('stock', (int) $detalle->cantidad);
                $variante->update(['disponible' => $variante->fresh()->stock > 0]);
            }
        }
    }

    private function debeRegistrarSalida(Pedido $pedido, string $nuevoEstado): bool
    {
        return $pedido->estado === Pedido::ESTADO_PENDIENTE
            && ! in_array($nuevoEstado, [Pedido::ESTADO_PENDIENTE, Pedido::ESTADO_CANCELADO], true);
    }

    private function restaurarVariantesDelPedido(Pedido $pedido): void
    {
        $pedido->loadMissing('detalles');

        foreach ($pedido->detalles as $detalle) {
            $variante = ProductoVariante::query()
                ->where('producto_id', $detalle->producto_id)
                ->whereHas('talla', fn ($query) => $query->where('nombre', $detalle->talla))
                ->whereHas('color', fn ($query) => $query->where('nombre', $detalle->color))
                ->lockForUpdate()
                ->first();

            if ($variante) {
                $variante->increment('stock', (int) $detalle->cantidad);
                $variante->update(['disponible' => true]);
            }
        }
    }

    private function anularVentaDelPedido(Pedido $pedido): void
    {
        $venta = $pedido->venta;

        if (! $venta || $venta->isAnulada()) {
            return;
        }

        foreach ($venta->detalles as $detalle) {
            if (! $detalle->producto_id) {
                continue;
            }

            $producto = Producto::query()->find($detalle->producto_id);

            if (! $producto) {
                continue;
            }

            $this->recordInventoryMovement->entrada(
                $producto,
                (int) $detalle->cantidad,
                'anulacion_venta',
                $venta->id,
                auth()->id(),
                "Anulación del pedido #{$pedido->numero}",
            );

            if ($detalle->variante) {
                $detalle->variante->increment('stock', (int) $detalle->cantidad);
                $detalle->variante->update(['disponible' => true]);
            }
        }

        $venta->update([
            'estado' => 'anulada',
            'anulada_en' => now(),
            'anulada_por_user_id' => auth()->id(),
        ]);
    }
}

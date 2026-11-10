<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\RecordInventoryMovement;
use App\Actions\Sales\CreateSaleFromOrder;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VentasController extends Controller
{
    public function __construct(
        private RecordInventoryMovement $recordInventoryMovement,
        private CreateSaleFromOrder $createSaleFromOrder,
    ) {}

    public function index(Request $request): Response
    {
        $ventas = Venta::query()
            ->with([
                'cliente:id,nombre,apellido',
                'user:id,name,nombres,apellido_paterno,apellido_materno',
                'detalles.productoRelacion.ultimaCompraDetalle',
                'detalles.variante.talla:id,nombre',
                'detalles.variante.color:id,nombre,codigo_hex',
            ])
            ->where('estado', 'completada')
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where(function ($scope) use ($request): void {
                $scope->where('user_id', $request->user()->id)
                    ->orWhereHas('pedido');
            }))
            ->when($request->search, function ($query, $search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('numero_venta', 'like', "%{$search}%")
                        ->orWhere('observaciones', 'like', "%{$search}%")
                        ->orWhereHas('cliente', fn ($clientQuery) => $clientQuery->where('nombre', 'like', "%{$search}%")->orWhere('apellido', 'like', "%{$search}%"))
                        ->orWhereHas('detalles', fn ($detailQuery) => $detailQuery->where('producto', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $ventas->through(function (Venta $venta): Venta {
            $costoTotal = $venta->detalles->sum(function ($detalle): float {
                $costoUnitario = (float) ($detalle->productoRelacion?->ultimaCompraDetalle?->precio_unitario ?? 0);

                return $costoUnitario * $detalle->cantidad;
            });

            $venta->setAttribute('ganancia', (float) $venta->total - $costoTotal);

            return $venta;
        });

        return Inertia::render('Ventas/index', [
            'ventas' => $ventas,
            'solicitudes' => $this->solicitudesPendientesList(),
            'clientes' => Cliente::select('id', 'nombre', 'apellido', 'ci_nit')
                ->where('estado', 'activo')
                ->orderBy('nombre')
                ->get(),
            'productos' => $this->availableProducts(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function solicitudesPendientes(): JsonResponse
    {
        $query = Pedido::query()->where('estado', Pedido::ESTADO_PENDIENTE);

        return response()->json([
            'count' => (clone $query)->count(),
            'latest_id' => (int) ((clone $query)->max('id') ?? 0),
        ]);
    }

    public function confirmarSolicitud(Request $request, Pedido $pedido): RedirectResponse
    {
        if ($pedido->estado === Pedido::ESTADO_CANCELADO) {
            return back()->withErrors(['solicitud' => 'Esta solicitud ha sido cancelada por el administrador.']);
        }

        if ($pedido->estado !== Pedido::ESTADO_PENDIENTE || $pedido->venta_id) {
            return back()->withErrors(['solicitud' => 'Esta solicitud ya fue atendida y no puede registrarse nuevamente.']);
        }

        try {
            $venta = DB::transaction(function () use ($pedido): Venta {
                return $this->createSaleFromOrder->execute($pedido);
            }, 3);
        } catch (ValidationException $exception) {
            return back()->withErrors([
                'solicitud' => $this->solicitudErrorMessage($exception),
            ]);
        }

        $venta->loadMissing(['cliente', 'detalles']);

        if ($request->user()?->isAdmin()) {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Solicitud confirmada; la venta se registró y el stock se descontó.']);
        }

        Inertia::flash('venta_registrada', [
            'id' => $venta->id,
            'numero_venta' => $venta->numero_venta,
            'cliente' => $venta->cliente
                ? trim("{$venta->cliente->nombre} {$venta->cliente->apellido}")
                : 'Cliente General',
            'fecha' => $venta->fecha?->toDateString() ?? now()->toDateString(),
            'cantidad_productos' => (int) $venta->detalles->sum('cantidad'),
            'total' => (float) $venta->total,
        ]);

        return back();
    }

    public function cancelarSolicitud(Request $request, Pedido $pedido): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Solo el administrador puede cancelar solicitudes de compra.');
        }

        if ($pedido->estado !== Pedido::ESTADO_PENDIENTE) {
            return back()->withErrors(['solicitud' => 'Solo se pueden cancelar solicitudes pendientes.']);
        }

        $pedido->update(['estado' => Pedido::ESTADO_CANCELADO]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Solicitud de compra cancelada. No se descontó stock.']);

        return back();
    }

    public function show(Venta $venta): Response
    {
        $this->ensureVentaAccesible($venta);
        $venta->load([
            'cliente:id,nombre,apellido,ci_nit,telefono,email',
            'user:id,name,nombres,apellido_paterno,apellido_materno',
            'anuladaPor:id,name,nombres,apellido_paterno,apellido_materno',
            'detalles.productoRelacion:id,nombre',
            'detalles.variante.talla:id,nombre',
            'detalles.variante.color:id,nombre,codigo_hex',
        ]);

        return Inertia::render('Ventas/show', ['venta' => $venta]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cliente_id' => [
                'nullable',
                'integer',
                'min:0',
                Rule::when(
                    filled($request->input('cliente_id')) && (int) $request->input('cliente_id') !== 0,
                    Rule::exists('clientes', 'id')->where('estado', 'activo'),
                ),
            ],
            'cliente_nuevo' => ['nullable', 'array'],
            'cliente_nuevo.nombre' => ['required_without:cliente_id', 'nullable', 'string', 'max:160'],
            'cliente_nuevo.ci_nit' => ['required_without:cliente_id', 'nullable', 'string', 'max:20', 'unique:clientes,ci_nit'],
            'cliente_nuevo.telefono' => ['required_without:cliente_id', 'nullable', 'string', 'max:20'],
            'cliente_nuevo.email' => ['nullable', 'email', 'max:255'],
            'cliente_nuevo.direccion' => ['nullable', 'string', 'max:500'],
            'fecha' => ['required', 'date'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.producto_variante_id' => ['nullable', 'integer', 'distinct', 'exists:producto_variantes,id'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'detalles.*.descuento' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->assertDetallesValidos($validated['detalles']);

        if (! filled($validated['cliente_id'] ?? null)) {
            $clienteId = Cliente::create([
                ...$validated['cliente_nuevo'],
                'apellido' => null,
                'estado' => 'activo',
            ])->id;
        } elseif ((int) $validated['cliente_id'] === 0) {
            $clienteId = Cliente::firstOrCreate(
                ['ci_nit' => 'SN-GENERAL'],
                ['nombre' => 'Cliente General', 'estado' => 'activo'],
            )->id;
        } else {
            $clienteId = (int) $validated['cliente_id'];
        }

        DB::transaction(function () use ($validated, $clienteId): void {
            $venta = Venta::create([
                'cliente_id' => $clienteId,
                'user_id' => Auth::id(),
                'numero_venta' => $this->nextSaleNumber(),
                'total' => 0,
                'fecha' => $validated['fecha'],
                'estado' => 'completada',
            ]);
            $venta->update(['total' => $this->applyDetalles($venta, $validated['detalles'])]);
        }, 3);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Venta registrada y existencias actualizadas.']);

        return to_route('ventas.index');
    }

    public function update(Request $request, Venta $venta): RedirectResponse
    {
        $this->ensureVentaAccesible($venta);

        if ($venta->isAnulada()) {
            $this->reactivarVenta($venta, $request);

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Venta reactivada y actualizada con los nuevos datos.']);

            return to_route('ventas.index');
        }

        $validated = $request->validate([
            'cliente_id' => [
                'required',
                'integer',
                'min:0',
                Rule::when(
                    (int) $request->input('cliente_id') !== 0,
                    Rule::exists('clientes', 'id')->where('estado', 'activo'),
                ),
            ],
            'fecha' => ['required', 'date'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.producto_variante_id' => ['nullable', 'integer', 'distinct', 'exists:producto_variantes,id'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'detalles.*.descuento' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->assertDetallesValidos($validated['detalles']);

        $clienteId = (int) $validated['cliente_id'] === 0
            ? Cliente::firstOrCreate(
                ['ci_nit' => 'SN-GENERAL'],
                ['nombre' => 'Cliente General', 'estado' => 'activo'],
            )->id
            : (int) $validated['cliente_id'];

        DB::transaction(function () use ($validated, $venta, $clienteId): void {
            $venta = Venta::query()->lockForUpdate()->findOrFail($venta->id);

            $this->recordInventoryMovement->forgetOrigin('venta', $venta->id);

            $venta->load('detalles.variante');

            foreach ($venta->detalles as $detalle) {
                if ($detalle->variante) {
                    $detalle->variante->increment('stock', (int) $detalle->cantidad);
                    $detalle->variante->update(['disponible' => true]);
                }
            }

            $venta->detalles()->delete();

            $venta->update([
                'cliente_id' => $clienteId,
                'fecha' => $validated['fecha'],
                'total' => 0,
                'estado' => 'completada',
                'anulada_en' => null,
                'anulada_por_user_id' => null,
            ]);

            $venta->update(['total' => $this->applyDetalles($venta, $validated['detalles'])]);
        }, 3);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Venta actualizada y existencias ajustadas.']);

        return to_route('ventas.index');
    }

    public function anular(Venta $venta): RedirectResponse
    {
        $this->ensureVentaAccesible($venta);

        if ($venta->isAnulada()) {
            return back()->withErrors(['venta' => 'Esta venta ya fue anulada.']);
        }

        DB::transaction(function () use ($venta): void {
            $venta = Venta::query()->lockForUpdate()->findOrFail($venta->id);

            if ($venta->isAnulada()) {
                return;
            }

            $venta->update([
                'estado' => 'anulada',
                'anulada_en' => now(),
                'anulada_por_user_id' => Auth::id(),
            ]);

            $venta->load('detalles.variante');

            foreach ($venta->detalles as $detalle) {
                if (! $detalle->producto_id) {
                    continue;
                }

                $producto = Producto::find($detalle->producto_id);

                if ($producto) {
                    $this->recordInventoryMovement->entrada(
                        $producto,
                        $detalle->cantidad,
                        'anulacion_venta',
                        $venta->id,
                        Auth::id(),
                        "Anulación de venta #{$venta->numero_venta}",
                    );

                    if ($detalle->variante) {
                        $detalle->variante->increment('stock', (int) $detalle->cantidad);
                        $detalle->variante->update(['disponible' => true]);
                    }
                }
            }
        }, 3);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Venta anulada y existencias restauradas.']);

        return to_route('ventas.index');
    }

    public function destroy(Venta $venta): RedirectResponse
    {
        $this->ensureVentaAccesible($venta);

        DB::transaction(function () use ($venta): void {
            if (! $venta->isAnulada()) {
                $venta->load('detalles.variante');
                foreach ($venta->detalles as $detalle) {
                    if ($detalle->variante) {
                        $detalle->variante->increment('stock', (int) $detalle->cantidad);
                        $detalle->variante->update(['disponible' => true]);
                    }
                }
            }

            $this->recordInventoryMovement->forgetOrigin('venta', $venta->id);
            $this->recordInventoryMovement->forgetOrigin('anulacion_venta', $venta->id);
            $venta->detalles()->delete();
            $venta->delete();
        }, 3);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Venta eliminada y existencias revertidas.']);

        return to_route('ventas.index');
    }

    public function anuladas(Request $request): Response
    {
        $ventas = Venta::query()
            ->with(['cliente:id,nombre,apellido', 'anuladaPor:id,name,nombres,apellido_paterno,apellido_materno', 'detalles'])
            ->where('estado', 'anulada')
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where(function ($scope) use ($request): void {
                $scope->where('user_id', $request->user()->id)
                    ->orWhereHas('pedido');
            }))
            ->when($request->search, function ($query, $search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('numero_venta', 'like', "%{$search}%")
                        ->orWhereHas('cliente', fn ($clientQuery) => $clientQuery->where('nombre', 'like', "%{$search}%")->orWhere('apellido', 'like', "%{$search}%"))
                        ->orWhereHas('detalles', fn ($detailQuery) => $detailQuery->where('producto', 'like', "%{$search}%"));
                });
            })
            ->latest('anulada_en')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Ventas/anuladas', [
            'ventas' => $ventas,
            'filters' => $request->only(['search']),
        ]);
    }

    public function productos(): JsonResponse
    {
        return response()->json($this->availableProducts());
    }

    private function solicitudErrorMessage(ValidationException $exception): string
    {
        $message = (string) collect($exception->errors())->flatten()->first();

        if (str_contains($message, 'Stock insuficiente')) {
            return 'No hay suficiente stock disponible para completar esta venta.';
        }

        if (str_contains($message, 'no pertenecen') || str_contains($message, 'No se encontró')) {
            return 'Verifique los datos de la venta antes de continuar.';
        }

        return 'No se pudo registrar la venta. Intente nuevamente.';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function solicitudesPendientesList(): Collection
    {
        return Pedido::query()
            ->with(['user:id,name,nombres,apellido_paterno,apellido_materno,email,telefono', 'detalles'])
            ->where('estado', Pedido::ESTADO_PENDIENTE)
            ->latest('created_at')
            ->get()
            ->map(fn (Pedido $pedido): array => $this->formatSolicitud($pedido))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSolicitud(Pedido $pedido): array
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
            'productos' => $pedido->detalles->map(fn ($detalle): array => [
                'producto_id' => $detalle->producto_id,
                'nombre' => $detalle->producto,
                'precio' => (float) $detalle->precio,
                'cantidad' => $detalle->cantidad,
                'subtotal' => (float) $detalle->subtotal,
                'descuento' => (float) max(0, ((float) $detalle->precio * $detalle->cantidad) - (float) $detalle->subtotal),
                'color' => $detalle->color,
                'talla' => $detalle->talla,
                'imagen' => $detalle->imagen,
            ])->values(),
        ];
    }

    private function availableProducts(): mixed
    {
        return Producto::query()
            ->with([
                'inventario',
                'categoria:id,nombre',
                'marca:id,nombre',
                'talla:id,nombre',
                'color:id,nombre,codigo_hex',
                'variantes' => fn ($query) => $query
                    ->select(['id', 'producto_id', 'talla_id', 'color_id', 'stock', 'disponible'])
                    ->with(['talla:id,nombre', 'color:id,nombre,codigo_hex'])
                    ->orderBy('talla_id')
                    ->orderBy('color_id'),
            ])
            ->whereHas('inventario', fn ($query) => $query->where('stock_actual', '>', 0)->where('disponible', true))
            ->orderBy('nombre')
            ->get()
            ->map(fn (Producto $producto): array => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'precio_venta' => $producto->precio_venta,
                'imagen' => $producto->imagen,
                'categoria' => $producto->categoria,
                'marca' => $producto->marca,
                'talla' => $producto->talla,
                'color' => $producto->color,
                'stock' => $producto->inventario?->stock_actual ?? 0,
                'variantes' => $producto->variantes->map(fn (ProductoVariante $variante): array => [
                    'id' => $variante->id,
                    'talla_id' => $variante->talla_id,
                    'color_id' => $variante->color_id,
                    'stock' => $variante->stock,
                    'disponible' => $variante->disponible,
                    'talla' => $variante->talla,
                    'color' => $variante->color,
                ])->values(),
            ]);
    }

    private function nextSaleNumber(): string
    {
        return 'VNT-'.now()->format('Ymd').'-'.str_pad((Venta::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT);
    }

    private function reactivarVenta(Venta $venta, Request $request): void
    {
        $validated = $request->validate([
            'cliente_id' => [
                'required',
                'integer',
                'min:0',
                Rule::when(
                    (int) $request->input('cliente_id') !== 0,
                    Rule::exists('clientes', 'id')->where('estado', 'activo'),
                ),
            ],
            'fecha' => ['required', 'date'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.producto_variante_id' => ['nullable', 'integer', 'distinct', 'exists:producto_variantes,id'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'detalles.*.descuento' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->assertDetallesValidos($validated['detalles']);

        $clienteId = (int) $validated['cliente_id'] === 0
            ? Cliente::firstOrCreate(
                ['ci_nit' => 'SN-GENERAL'],
                ['nombre' => 'Cliente General', 'estado' => 'activo'],
            )->id
            : (int) $validated['cliente_id'];

        DB::transaction(function () use ($validated, $venta, $clienteId): void {
            $venta = Venta::query()->lockForUpdate()->findOrFail($venta->id);

            $this->recordInventoryMovement->forgetOrigin('anulacion_venta', $venta->id);
            $this->recordInventoryMovement->forgetOrigin('venta', $venta->id);

            $venta->load('detalles.variante');

            foreach ($venta->detalles as $detalle) {
                if ($detalle->variante) {
                    $detalle->variante->increment('stock', (int) $detalle->cantidad);
                    $detalle->variante->update(['disponible' => true]);
                }
            }

            $venta->detalles()->delete();

            $venta->update([
                'cliente_id' => $clienteId,
                'user_id' => Auth::id(),
                'fecha' => $validated['fecha'],
                'total' => 0,
                'estado' => 'completada',
                'anulada_en' => null,
                'anulada_por_user_id' => null,
            ]);

            $venta->update(['total' => $this->applyDetalles($venta, $validated['detalles'])]);
        }, 3);
    }

    private function assertDetallesValidos(array $detalles): void
    {
        $combinaciones = [];
        foreach ($detalles as $index => $detalle) {
            $varianteId = $detalle['producto_variante_id'] ?? null;
            $productoTieneVariantes = ProductoVariante::query()
                ->where('producto_id', $detalle['producto_id'])
                ->exists();

            if ($productoTieneVariantes && ! $varianteId) {
                throw ValidationException::withMessages([
                    "detalles.{$index}.producto_variante_id" => 'Seleccione la talla y el color que se venderán.',
                ]);
            }

            if ($varianteId && ! ProductoVariante::query()
                ->whereKey($varianteId)
                ->where('producto_id', $detalle['producto_id'])
                ->exists()) {
                throw ValidationException::withMessages([
                    "detalles.{$index}.producto_variante_id" => 'La talla y el color seleccionados no pertenecen al producto.',
                ]);
            }

            $combinacion = $detalle['producto_id'].'-'.($varianteId ?? 'principal');
            if (isset($combinaciones[$combinacion])) {
                $campo = $varianteId ? 'producto_variante_id' : 'producto_id';
                throw ValidationException::withMessages([
                    "detalles.{$index}.{$campo}" => 'Esta combinación de producto, talla y color ya fue agregada.',
                ]);
            }
            $combinaciones[$combinacion] = true;
        }
    }

    private function applyDetalles(Venta $venta, array $detalles): float
    {
        $total = 0.0;

        foreach ($detalles as $detalle) {
            $producto = Producto::findOrFail($detalle['producto_id']);
            $variante = filled($detalle['producto_variante_id'] ?? null)
                ? ProductoVariante::query()
                    ->with(['talla:id,nombre', 'color:id,nombre'])
                    ->lockForUpdate()
                    ->findOrFail($detalle['producto_variante_id'])
                : null;

            if ($variante && (! $variante->disponible || $variante->stock < $detalle['cantidad'])) {
                throw ValidationException::withMessages([
                    'detalles' => "Stock insuficiente para «{$producto->nombre}» en talla {$variante->talla?->nombre} y color {$variante->color?->nombre}. Disponible: {$variante->stock}.",
                ]);
            }
            $subtotal = max(0, ($detalle['cantidad'] * $detalle['precio_unitario']) - ($detalle['descuento'] ?? 0));

            $venta->detalles()->create([
                'producto_id' => $producto->id,
                'producto_variante_id' => $variante?->id,
                'producto' => $producto->nombre,
                'talla' => $variante?->talla?->nombre ?? $producto->talla?->nombre,
                'color' => $variante?->color?->nombre ?? $producto->color?->nombre,
                'cantidad' => $detalle['cantidad'],
                'precio_unitario' => $detalle['precio_unitario'],
                'descuento' => $detalle['descuento'] ?? 0,
                'subtotal' => $subtotal,
            ]);

            $this->recordInventoryMovement->salida(
                $producto,
                (int) $detalle['cantidad'],
                'venta',
                $venta->id,
                Auth::id(),
                "Venta #{$venta->numero_venta}",
            );

            if ($variante) {
                $variante->decrement('stock', (int) $detalle['cantidad']);
                $variante->update(['disponible' => $variante->fresh()->stock > 0]);
            }

            $total += $subtotal;
        }

        return $total;
    }

    private function ensureVentaAccesible(Venta $venta): void
    {
        $user = Auth::user();

        $isOnlineSale = $venta->pedido()->exists();

        if ($user && ! $user->isAdmin() && ! $isOnlineSale && (int) $venta->user_id !== (int) $user->id) {
            abort(403, 'No tienes permiso para ver esta venta.');
        }
    }
}

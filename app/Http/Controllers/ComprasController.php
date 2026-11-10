<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\RecordInventoryMovement;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ComprasController extends Controller
{
    public function __construct(private RecordInventoryMovement $recordInventoryMovement) {}

    public function index(Request $request): Response
    {
        $compras = Compra::query()
            ->where('estado', '!=', 'anulada')
            ->with([
                'proveedor:id,nombre',
                'user:id,name,nombres,apellido_paterno,apellido_materno',
                'anuladaPor:id,name,nombres,apellido_paterno,apellido_materno',
                'detalles.productoRelacion.talla:id,nombre',
                'detalles.productoRelacion.color:id,nombre,codigo_hex',
                'detalles.variante.talla:id,nombre',
                'detalles.variante.color:id,nombre,codigo_hex',
            ])
            ->when($request->search, function ($query, $search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('numero_compra', 'like', "%{$search}%")
                        ->orWhereHas('proveedor', fn ($providerQuery) => $providerQuery->where('nombre', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Compras/index', [
            'compras' => $compras,
            'proveedores' => Proveedor::select('id', 'nombre')->orderBy('nombre')->get(),
            'productos' => Producto::query()
                ->select(['id', 'nombre', 'descripcion', 'imagen', 'categoria_id', 'marca_id', 'talla_id', 'color_id'])
                ->with([
                    'categoria:id,nombre',
                    'marca:id,nombre',
                    'talla:id,nombre',
                    'color:id,nombre',
                    'variantes' => fn ($query) => $query
                        ->select(['id', 'producto_id', 'talla_id', 'color_id', 'stock', 'disponible'])
                        ->with(['talla:id,nombre', 'color:id,nombre,codigo_hex'])
                        ->orderBy('talla_id')
                        ->orderBy('color_id'),
                ])
                ->where('estado', 'activo')
                ->orderBy('nombre')
                ->get(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCompra($request);

        DB::transaction(function () use ($validated, $request): void {
            $compra = Compra::create([
                'proveedor_id' => $validated['proveedor_id'],
                'user_id' => $request->user()->id,
                'numero_compra' => $this->nextPurchaseNumber(),
                'total' => 0,
                'fecha' => $validated['fecha'],
            ]);

            $total = $this->createDetailsAndEntries($compra, $validated['detalles'], $request->user()->id);
            $compra->update(['total' => $total]);
        }, 3);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Compra registrada y existencias actualizadas.']);

        return to_route('compras.index');
    }

    public function show(Compra $compra): Response
    {
        $compra->load(['proveedor', 'user', 'detalles.productoRelacion']);

        return Inertia::render('Compras/show', ['compra' => $compra]);
    }

    public function update(Request $request, Compra $compra): RedirectResponse
    {
        if ($compra->isAnulada()) {
            return back()->withErrors(['compra' => 'Una compra anulada no puede ser modificada.']);
        }

        $validated = $this->validateCompra($request, $compra);

        DB::transaction(function () use ($validated, $request, $compra): void {
            $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);

            if ($compra->isAnulada()) {
                throw ValidationException::withMessages([
                    'compra' => 'Una compra anulada no puede ser modificada.',
                ]);
            }

            $detallesPorProducto = collect($validated['detalles'])->groupBy('producto_id');
            $productoIds = $compra->detalles()
                ->pluck('producto_id')
                ->merge($detallesPorProducto->keys())
                ->filter()
                ->unique()
                ->sort()
                ->values();
            $productos = Producto::query()->whereKey($productoIds)->get()->keyBy('id');

            foreach ($productoIds as $productoId) {
                $producto = $productos->get($productoId);

                if (! $producto) {
                    continue;
                }

                $this->recordInventoryMovement->setEntradaOrigin(
                    $producto,
                    (int) $detallesPorProducto->get($productoId, collect())->sum('cantidad'),
                    'compra',
                    $compra->id,
                    $request->user()->id,
                    "Compra #{$compra->numero_compra}",
                );
            }

            $this->synchronizeVariantStock($compra, $validated['detalles']);
            $compra->detalles()->delete();
            $compra->update([
                'proveedor_id' => $validated['proveedor_id'],
                'fecha' => $validated['fecha'],
            ]);

            $total = $this->createDetails($compra, $validated['detalles']);
            $compra->update(['total' => $total]);
        }, 3);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Compra e inventario actualizados correctamente.']);

        return to_route('compras.index');
    }

    public function anular(Request $request, Compra $compra): RedirectResponse
    {
        if ($compra->isAnulada()) {
            return back()->withErrors(['compra' => 'Esta compra ya fue anulada.']);
        }

        try {
            DB::transaction(function () use ($compra, $request): void {
                $compra = Compra::query()->lockForUpdate()->findOrFail($compra->id);

                if ($compra->isAnulada()) {
                    return;
                }

                $compra->load(['detalles.productoRelacion', 'detalles.variante']);

                foreach ($compra->detalles as $detalle) {
                    $producto = $detalle->productoRelacion;

                    if (! $producto) {
                        continue;
                    }

                    $this->recordInventoryMovement->anularEntrada(
                        $producto,
                        (int) $detalle->cantidad,
                        'anulacion_compra',
                        $compra->id,
                        $request->user()->id,
                        "Anulación de compra #{$compra->numero_compra}",
                    );

                    if ($detalle->variante) {
                        $variante = ProductoVariante::query()->lockForUpdate()->find($detalle->variante->id);

                        if ($variante && $variante->stock < $detalle->cantidad) {
                            throw ValidationException::withMessages([
                                'compra' => 'No se puede anular esta compra porque parte de sus variantes ya fue vendida.',
                            ]);
                        }

                        if ($variante) {
                            $variante->decrement('stock', (int) $detalle->cantidad);
                            $variante->update(['disponible' => $variante->fresh()->stock > 0]);
                        }
                    }
                }
                $compra->update([
                    'estado' => 'anulada',
                    'anulada_en' => now(),
                    'anulada_por_user_id' => $request->user()->id,
                ]);
            }, 3);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? 'No se pudo anular la compra.';

            Inertia::flash('toast', ['type' => 'warning', 'message' => $message]);

            return to_route('compras.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Compra anulada y existencias revertidas.']);

        return to_route('compras.index');
    }

    /** @return array{proveedor_id: int, fecha: string, detalles: array<int, array{producto_id: int, producto_variante_id?: int|null, cantidad: int, precio_unitario: float|int|string}>} */
    private function validateCompra(Request $request, ?Compra $compra = null): array
    {
        $validated = $request->validate([
            'proveedor_id' => [
                'required',
                Rule::exists('proveedores', 'id')->where(function ($query) use ($compra): void {
                    $query->where(function ($query) use ($compra): void {
                        $query->whereNull('deleted_at');

                        if ($compra) {
                            $query->orWhere('id', $compra->proveedor_id);
                        }
                    });
                }),
            ],
            'fecha' => ['required', 'date'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'exists:productos,id'],
            'detalles.*.producto_variante_id' => ['nullable', 'integer', 'exists:producto_variantes,id'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        $combinaciones = [];
        foreach ($validated['detalles'] as $index => $detalle) {
            $varianteId = $detalle['producto_variante_id'] ?? null;

            if ($varianteId) {
                $pertenece = ProductoVariante::query()
                    ->whereKey($varianteId)
                    ->where('producto_id', $detalle['producto_id'])
                    ->exists();

                if (! $pertenece) {
                    throw ValidationException::withMessages([
                        "detalles.{$index}.producto_variante_id" => 'La talla y el color seleccionados no pertenecen al producto.',
                    ]);
                }
            }

            $clave = $detalle['producto_id'].'-'.($varianteId ?? 'principal');
            if (isset($combinaciones[$clave])) {
                $campo = $varianteId ? 'producto_variante_id' : 'producto_id';
                throw ValidationException::withMessages([
                    "detalles.{$index}.{$campo}" => 'Esta combinación de producto, talla y color ya fue agregada.',
                ]);
            }
            $combinaciones[$clave] = true;
        }

        return $validated;
    }

    /**
     * @param  array<int, array{producto_id: int, cantidad: int, precio_unitario: float|int|string}>  $detalles
     */
    private function createDetailsAndEntries(Compra $compra, array $detalles, int $userId): float
    {
        $productos = Producto::query()
            ->whereKey(collect($detalles)->pluck('producto_id'))
            ->with(['talla:id,nombre', 'color:id,nombre'])
            ->get()
            ->keyBy('id');
        $total = 0.0;
        $variantes = $this->loadVariants($detalles);

        foreach ($detalles as $detalle) {
            $producto = $productos->get($detalle['producto_id']);

            if (! $producto) {
                continue;
            }

            $variante = $variantes->get($detalle['producto_variante_id'] ?? 0);

            $cantidad = (int) $detalle['cantidad'];
            $precioUnitario = (float) $detalle['precio_unitario'];
            $subtotal = $cantidad * $precioUnitario;

            $compra->detalles()->create([
                'producto_id' => $producto->id,
                'producto_variante_id' => $variante?->id,
                'producto' => $producto->nombre,
                'talla' => $variante?->talla?->nombre ?? $producto->talla?->nombre,
                'color' => $variante?->color?->nombre ?? $producto->color?->nombre,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotal,
            ]);

            if ($variante) {
                $variante->increment('stock', $cantidad);
                $variante->update(['disponible' => true]);
            }

            $this->recordInventoryMovement->entrada(
                $producto,
                $cantidad,
                'compra',
                $compra->id,
                $userId,
                "Compra #{$compra->numero_compra}",
            );

            $total += $subtotal;
        }

        return $total;
    }

    /**
     * @param  array<int, array{producto_id: int, cantidad: int, precio_unitario: float|int|string}>  $detalles
     */
    private function createDetails(Compra $compra, array $detalles): float
    {
        $productos = Producto::query()
            ->whereKey(collect($detalles)->pluck('producto_id'))
            ->with(['talla:id,nombre', 'color:id,nombre'])
            ->get()
            ->keyBy('id');
        $total = 0.0;
        $variantes = $this->loadVariants($detalles);

        foreach ($detalles as $detalle) {
            $producto = $productos->get($detalle['producto_id']);

            if (! $producto) {
                continue;
            }

            $cantidad = (int) $detalle['cantidad'];
            $precioUnitario = (float) $detalle['precio_unitario'];
            $subtotal = $cantidad * $precioUnitario;
            $variante = $variantes->get($detalle['producto_variante_id'] ?? 0);

            $compra->detalles()->create([
                'producto_id' => $producto->id,
                'producto_variante_id' => $variante?->id,
                'producto' => $producto->nombre,
                'talla' => $variante?->talla?->nombre ?? $producto->talla?->nombre,
                'color' => $variante?->color?->nombre ?? $producto->color?->nombre,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotal,
            ]);

            $total += $subtotal;
        }

        return $total;
    }

    private function loadVariants(array $detalles)
    {
        return ProductoVariante::query()
            ->whereKey(collect($detalles)->pluck('producto_variante_id')->filter())
            ->with(['talla:id,nombre', 'color:id,nombre'])
            ->get()
            ->keyBy('id');
    }

    private function synchronizeVariantStock(Compra $compra, array $detalles): void
    {
        $anteriores = $compra->detalles()
            ->whereNotNull('producto_variante_id')
            ->selectRaw('producto_variante_id, SUM(cantidad) as cantidad')
            ->groupBy('producto_variante_id')
            ->pluck('cantidad', 'producto_variante_id');
        $nuevos = collect($detalles)
            ->filter(fn (array $detalle): bool => ! empty($detalle['producto_variante_id']))
            ->groupBy('producto_variante_id')
            ->map(fn ($filas): int => (int) $filas->sum('cantidad'));

        foreach ($anteriores->keys()->merge($nuevos->keys())->unique() as $varianteId) {
            $variante = ProductoVariante::query()->lockForUpdate()->find($varianteId);
            if (! $variante) {
                continue;
            }

            $diferencia = (int) $nuevos->get($varianteId, 0) - (int) $anteriores->get($varianteId, 0);
            if ($diferencia < 0 && $variante->stock < abs($diferencia)) {
                throw ValidationException::withMessages([
                    'detalles' => 'No se puede reducir esta variante porque parte de sus existencias ya fue utilizada.',
                ]);
            }

            if ($diferencia > 0) {
                $variante->increment('stock', $diferencia);
            } elseif ($diferencia < 0) {
                $variante->decrement('stock', abs($diferencia));
            }
            $variante->update(['disponible' => $variante->fresh()->stock > 0]);
        }
    }

    private function nextPurchaseNumber(): string
    {
        return 'CMP-'.now()->format('Ymd').'-'.str_pad((Compra::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT);
    }
}

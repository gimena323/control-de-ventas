<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\SyncProductVariants;
use App\Models\Categoria;
use App\Models\Color;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Talla;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductosController extends Controller
{
    public function index(Request $request): Response
    {
        $productos = Producto::query()
            ->with(['categoria', 'marca', 'talla', 'color', 'inventario'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%")
                        ->orWhere('genero', 'like', "%{$search}%")
                        ->orWhereHas('categoria', fn ($q) => $q->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('marca', fn ($q) => $q->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('talla', fn ($q) => $q->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('color', fn ($q) => $q->where('nombre', 'like', "%{$search}%"));
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Productos/index', [
            'productos' => $productos,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Marca::orderBy('nombre')->get(),
            'tallas' => Talla::orderBy('nombre')->get(),
            'colores' => Color::orderBy('nombre')->get(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Productos/Create', [
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Marca::orderBy('nombre')->get(),
            'tallas' => Talla::orderBy('nombre')->get(),
            'colores' => Color::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request, SyncProductVariants $syncProductVariants): RedirectResponse
    {
        $validated = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'marca_id' => 'required|exists:marcas,id',
            'tallas' => 'required|array|min:1',
            'tallas.*' => 'exists:tallas,id',
            'colores' => 'required|array|min:1',
            'colores.*' => 'exists:colores,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'precio_venta' => 'required|numeric|min:0',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'imagenes' => 'nullable|array|max:10',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'imagenes_colores' => 'nullable|array',
            'imagenes_colores.*' => 'nullable|integer|exists:colores,id',
            'estado' => 'required|string|in:activo,inactivo',
            'genero' => 'sometimes|required|string|in:femenino,masculino',
        ]);

        if ($this->productoDuplicado($validated)) {
            return back()->withErrors(['producto' => 'El producto ya está registrado. No se permiten productos duplicados.'])->withInput();
        }

        $imagenes = $this->uploadedImages($validated);
        $imagenesColores = $validated['imagenes_colores'] ?? [];

        if (isset($validated['imagen'])) {
            array_unshift($imagenesColores, null);
        }

        unset($validated['imagen'], $validated['imagenes'], $validated['imagenes_colores']);

        $tallasIds = $validated['tallas'];
        $coloresIds = array_map('intval', $validated['colores']);
        unset($validated['tallas'], $validated['colores']);

        $validated['talla_id'] = $tallasIds[0];
        $validated['color_id'] = $coloresIds[0];

        $producto = Producto::create($validated);

        $this->storeImages($producto, $imagenes, $imagenesColores, $coloresIds);

        $syncProductVariants->execute($producto, $tallasIds, $coloresIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Prenda creada exitosamente.']);

        return to_route('productos.index');
    }

    public function edit(Producto $producto): Response
    {
        $producto->load(['categoria', 'marca', 'talla', 'color', 'inventario', 'variantes.color', 'variantes.talla', 'imagenes']);

        return Inertia::render('Productos/Edit', [
            'producto' => $producto,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Marca::orderBy('nombre')->get(),
            'tallas' => Talla::orderBy('nombre')->get(),
            'colores' => Color::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Producto $producto, SyncProductVariants $syncProductVariants): RedirectResponse
    {
        $validated = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'marca_id' => 'required|exists:marcas,id',
            'tallas' => 'required|array|min:1',
            'tallas.*' => 'exists:tallas,id',
            'colores' => 'required|array|min:1',
            'colores.*' => 'exists:colores,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'precio_venta' => 'required|numeric|min:0',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'imagenes' => 'nullable|array|max:10',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'imagenes_colores' => 'nullable|array',
            'imagenes_colores.*' => 'nullable|integer|exists:colores,id',
            'imagenes_existentes_colores' => 'nullable|array',
            'imagenes_existentes_colores.*.id' => 'required|integer|exists:producto_imagenes,id',
            'imagenes_existentes_colores.*.color_id' => 'required|integer|exists:colores,id',
            'imagenes_eliminadas' => 'nullable|array',
            'imagenes_eliminadas.*' => 'integer|exists:producto_imagenes,id',
            'estado' => 'required|string|in:activo,inactivo',
            'genero' => 'sometimes|required|string|in:femenino,masculino',
        ]);

        if ($this->productoDuplicado($validated, $producto->id)) {
            return back()->withErrors(['producto' => 'El producto ya está registrado. No se permiten productos duplicados.'])->withInput();
        }

        $imagenes = $this->uploadedImages($validated);
        $imagenesColores = $validated['imagenes_colores'] ?? [];
        $imagenesExistentesColores = $validated['imagenes_existentes_colores'] ?? [];
        $imagenesEliminadas = $validated['imagenes_eliminadas'] ?? [];

        if (isset($validated['imagen'])) {
            array_unshift($imagenesColores, null);
        }

        unset(
            $validated['imagen'],
            $validated['imagenes'],
            $validated['imagenes_colores'],
            $validated['imagenes_existentes_colores'],
            $validated['imagenes_eliminadas'],
        );

        $tallasIds = $validated['tallas'];
        $coloresIds = array_map('intval', $validated['colores']);
        unset($validated['tallas'], $validated['colores']);

        $validated['talla_id'] = $tallasIds[0];
        $validated['color_id'] = $coloresIds[0];

        $producto->update($validated);

        $this->syncLegacyImage($producto);
        $this->deleteImages($producto, $imagenesEliminadas);
        $this->updateImageColors($producto, $imagenesExistentesColores, $coloresIds);
        $this->storeImages($producto, $imagenes, $imagenesColores, $coloresIds);
        $this->refreshPrimaryImage($producto);

        $syncProductVariants->execute($producto, $tallasIds, $coloresIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Prenda actualizada exitosamente.']);

        return to_route('productos.index');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        if ($producto->ventasDetalles()->exists() || $producto->comprasDetalles()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar el producto porque ya tiene ventas o compras registradas.']);

            return to_route('productos.index');
        }

        $producto->loadMissing(['imagenes', 'variantes', 'inventario']);

        $producto->imagenes->pluck('ruta')
            ->push($producto->imagen)
            ->filter()
            ->unique()
            ->each(fn (string $ruta) => Storage::disk('public')->delete($ruta));

        if ($producto->inventario) {
            $producto->inventario->movimientos()->delete();
            $producto->inventario->delete();
        }

        $producto->variantes()->delete();
        $producto->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Prenda eliminada exitosamente.']);

        return to_route('productos.index');
    }

    public function storeCategoria(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre',
        ], $this->mensajesValidacionCatalogo('categoría'));

        Categoria::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría creada exitosamente.']);

        return to_route('productos.index');
    }

    public function updateCategoria(Request $request, Categoria $categoria): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre,'.$categoria->id,
        ], $this->mensajesValidacionCatalogo('categoría'));

        $categoria->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría actualizada exitosamente.']);

        return to_route('productos.index');
    }

    public function destroyCategoria(Categoria $categoria): RedirectResponse
    {
        if ($categoria->productos()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar la categoría porque tiene prendas asociadas.']);

            return to_route('productos.index');
        }

        $categoria->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría eliminada exitosamente.']);

        return to_route('productos.index');
    }

    public function storeMarca(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:marcas,nombre',
        ], $this->mensajesValidacionCatalogo('marca'));

        Marca::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marca creada exitosamente.']);

        return to_route('productos.index');
    }

    public function updateMarca(Request $request, Marca $marca): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:marcas,nombre,'.$marca->id,
        ], $this->mensajesValidacionCatalogo('marca'));

        $marca->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marca actualizada exitosamente.']);

        return to_route('productos.index');
    }

    public function destroyMarca(Marca $marca): RedirectResponse
    {
        if ($marca->productos()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar la marca porque tiene prendas asociadas.']);

            return to_route('productos.index');
        }

        $marca->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marca eliminada exitosamente.']);

        return to_route('productos.index');
    }

    public function storeTalla(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:tallas,nombre',
        ], $this->mensajesValidacionCatalogo('talla'));

        Talla::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Talla creada exitosamente.']);

        return to_route('productos.index');
    }

    public function updateTalla(Request $request, Talla $talla): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:tallas,nombre,'.$talla->id,
        ], $this->mensajesValidacionCatalogo('talla'));

        $talla->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Talla actualizada exitosamente.']);

        return to_route('productos.index');
    }

    public function destroyTalla(Talla $talla): RedirectResponse
    {
        if ($talla->productos()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar la talla porque tiene prendas asociadas.']);

            return to_route('productos.index');
        }

        $talla->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Talla eliminada exitosamente.']);

        return to_route('productos.index');
    }

    public function storeColor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:colores,nombre',
            'codigo_hex' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], $this->mensajesValidacionCatalogo('color'));

        Color::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Color creado exitosamente.']);

        return to_route('productos.index');
    }

    public function updateColor(Request $request, Color $color): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:colores,nombre,'.$color->id,
            'codigo_hex' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], $this->mensajesValidacionCatalogo('color'));

        $color->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Color actualizado exitosamente.']);

        return to_route('productos.index');
    }

    public function destroyColor(Color $color): RedirectResponse
    {
        if ($color->productos()->exists() || $color->variantes()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar el color porque tiene prendas asociadas.']);

            return to_route('productos.index');
        }

        $color->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Color eliminado exitosamente.']);

        return to_route('productos.index');
    }

    private function productoDuplicado(array $datos, ?int $ignorarId = null): bool
    {
        $descripcion = trim($datos['descripcion'] ?? '');
        $descripcion = $descripcion === '' ? null : $descripcion;

        $query = Producto::query()
            ->where('categoria_id', $datos['categoria_id'])
            ->where('marca_id', $datos['marca_id'])
            ->where('nombre', $datos['nombre']);

        if ($descripcion === null) {
            $query->whereNull('descripcion');
        } else {
            $query->where('descripcion', $descripcion);
        }

        if ($ignorarId !== null) {
            $query->where('id', '!=', $ignorarId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, UploadedFile>
     */
    private function uploadedImages(array $validated): array
    {
        $imagenes = $validated['imagenes'] ?? [];

        if (isset($validated['imagen'])) {
            array_unshift($imagenes, $validated['imagen']);
        }

        return $imagenes;
    }

    /**
     * @param  array<int, UploadedFile>  $imagenes
     * @param  array<int, int|null>  $imagenesColores
     * @param  array<int, int>  $coloresPermitidos
     */
    private function storeImages(Producto $producto, array $imagenes, array $imagenesColores = [], array $coloresPermitidos = []): void
    {
        $siguienteOrden = (int) $producto->imagenes()->max('orden') + 1;

        foreach ($imagenes as $index => $imagen) {
            $ruta = $imagen->store('productos', 'public');
            $colorId = isset($imagenesColores[$index]) ? (int) $imagenesColores[$index] : null;

            if ($colorId !== null && ! in_array($colorId, $coloresPermitidos, true)) {
                $colorId = null;
            }

            $producto->imagenes()->create([
                'color_id' => $colorId,
                'ruta' => $ruta,
                'orden' => $siguienteOrden++,
            ]);
        }

        $this->refreshPrimaryImage($producto);
    }

    /**
     * @param  array<int, array{id: int, color_id: int}>  $imagenesColores
     * @param  array<int, int>  $coloresPermitidos
     */
    private function updateImageColors(Producto $producto, array $imagenesColores, array $coloresPermitidos): void
    {
        foreach ($imagenesColores as $imagenColor) {
            $colorId = (int) $imagenColor['color_id'];

            if (! in_array($colorId, $coloresPermitidos, true)) {
                continue;
            }

            $producto->imagenes()
                ->whereKey($imagenColor['id'])
                ->update(['color_id' => $colorId]);
        }
    }

    /** @param array<int, int> $imagenIds */
    private function deleteImages(Producto $producto, array $imagenIds): void
    {
        $imagenes = $producto->imagenes()->whereKey($imagenIds)->get();

        foreach ($imagenes as $imagen) {
            Storage::disk('public')->delete($imagen->ruta);
            $imagen->delete();
        }
    }

    private function syncLegacyImage(Producto $producto): void
    {
        if ($producto->imagen && ! $producto->imagenes()->where('ruta', $producto->imagen)->exists()) {
            $producto->imagenes()->create([
                'ruta' => $producto->imagen,
                'orden' => 0,
            ]);
        }
    }

    private function refreshPrimaryImage(Producto $producto): void
    {
        $imagenes = $producto->imagenes()->orderBy('orden')->orderBy('id')->get();

        foreach ($imagenes->values() as $orden => $imagen) {
            if ($imagen->orden !== $orden) {
                $imagen->update(['orden' => $orden]);
            }
        }

        $producto->update(['imagen' => $imagenes->first()?->ruta]);
    }

    /**
     * @return array<string, string>
     */
    private function mensajesValidacionCatalogo(string $entidad): array
    {
        return [
            'nombre.required' => "El nombre de la {$entidad} es obligatorio.",
            'nombre.string' => "El nombre de la {$entidad} debe ser un texto válido.",
            'nombre.max' => "El nombre de la {$entidad} no debe superar los 255 caracteres.",
            'nombre.unique' => "El nombre de la {$entidad} ya está registrado.",
            'marca_id.exists' => 'La marca seleccionada no es válida.',
            'codigo_hex.required' => 'El código del color es obligatorio.',
            'codigo_hex.size' => 'El código del color debe tener 7 caracteres.',
            'codigo_hex.starts_with' => 'El código del color debe comenzar con #.',
            'codigo_hex.regex' => 'Selecciona un color válido de la paleta.',
        ];
    }
}

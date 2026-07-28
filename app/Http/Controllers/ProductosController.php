<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Color;
use App\Models\Inventario;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Talla;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductosController extends Controller
{
    public function index(Request $request): Response
    {
        $productos = Producto::query()
            ->with(['categoria', 'marca', 'modelo', 'talla', 'color', 'inventario'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%")
                        ->orWhereHas('categoria', fn ($q) => $q->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('marca', fn ($q) => $q->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('modelo', fn ($q) => $q->where('nombre', 'like', "%{$search}%"));
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Productos/index', [
            'productos' => $productos,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Marca::orderBy('nombre')->get(),
            'modelos' => Modelo::with('marca')->orderBy('nombre')->get(),
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
            'modelos' => Modelo::with('marca')->orderBy('nombre')->get(),
            'tallas' => Talla::orderBy('nombre')->get(),
            'colores' => Color::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'marca_id' => 'required|exists:marcas,id',
            'modelo_id' => 'required|exists:modelos,id',
            'talla_id' => 'required|exists:tallas,id',
            'color_id' => 'required|exists:colores,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'precio_venta' => 'required|numeric|min:0',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'estado' => 'required|string|in:activo,inactivo',
            'variantes' => 'nullable|array',
            'variantes.*.color_id' => 'required_with:variantes|exists:colores,id',
            'variantes.*.talla_id' => 'required_with:variantes|exists:tallas,id',
            'variantes.*.stock' => 'required_with:variantes|integer|min:0',
            'variantes.*.disponible' => 'required_with:variantes|boolean',
        ]);

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        $variantesData = $validated['variantes'] ?? [];
        unset($validated['variantes']);

        $producto = Producto::create($validated);

        Inventario::create([
            'producto_id' => $producto->id,
            'stock_actual' => 0,
            'disponible' => true,
        ]);

        foreach ($variantesData as $variante) {
            ProductoVariante::create([
                'producto_id' => $producto->id,
                'color_id' => $variante['color_id'],
                'talla_id' => $variante['talla_id'],
                'stock' => $variante['stock'],
                'disponible' => $variante['disponible'],
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Prenda creada exitosamente.']);

        return to_route('productos.index');
    }

    public function edit(Producto $producto): Response
    {
        $producto->load(['categoria', 'marca', 'modelo', 'talla', 'color', 'inventario', 'variantes.color', 'variantes.talla']);

        return Inertia::render('Productos/Edit', [
            'producto' => $producto,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Marca::orderBy('nombre')->get(),
            'modelos' => Modelo::with('marca')->orderBy('nombre')->get(),
            'tallas' => Talla::orderBy('nombre')->get(),
            'colores' => Color::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $validated = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'marca_id' => 'required|exists:marcas,id',
            'modelo_id' => 'required|exists:modelos,id',
            'talla_id' => 'required|exists:tallas,id',
            'color_id' => 'required|exists:colores,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'precio_venta' => 'required|numeric|min:0',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'estado' => 'required|string|in:activo,inactivo',
            'variantes' => 'nullable|array',
            'variantes.*.id' => 'nullable|integer',
            'variantes.*.color_id' => 'required_with:variantes|exists:colores,id',
            'variantes.*.talla_id' => 'required_with:variantes|exists:tallas,id',
            'variantes.*.stock' => 'required_with:variantes|integer|min:0',
            'variantes.*.disponible' => 'required_with:variantes|boolean',
        ]);

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = $request->file('imagen')->store('productos', 'public');
        } else {
            unset($validated['imagen']);
        }

        $variantesData = $validated['variantes'] ?? [];
        unset($validated['variantes']);

        $producto->update($validated);

        $existingIds = $variantesData->pluck('id')->filter()->values()->all();
        $producto->variantes()->whereNotIn('id', $existingIds)->delete();

        foreach ($variantesData as $variante) {
            if (!empty($variante['id'])) {
                ProductoVariante::where('id', $variante['id'])->update([
                    'color_id' => $variante['color_id'],
                    'talla_id' => $variante['talla_id'],
                    'stock' => $variante['stock'],
                    'disponible' => $variante['disponible'],
                ]);
            } else {
                ProductoVariante::create([
                    'producto_id' => $producto->id,
                    'color_id' => $variante['color_id'],
                    'talla_id' => $variante['talla_id'],
                    'stock' => $variante['stock'],
                    'disponible' => $variante['disponible'],
                ]);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Prenda actualizada exitosamente.']);

        return to_route('productos.index');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        if ($producto->imagen) {
            $path = storage_path('app/public/'.$producto->imagen);
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $producto->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Prenda eliminada exitosamente.']);

        return to_route('productos.index');
    }

    public function storeCategoria(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre',
        ]);

        Categoria::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Categoría creada exitosamente.']);

        return to_route('productos.index');
    }

    public function updateCategoria(Request $request, Categoria $categoria): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias,nombre,'.$categoria->id,
        ]);

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
        ]);

        Marca::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marca creada exitosamente.']);

        return to_route('productos.index');
    }

    public function updateMarca(Request $request, Marca $marca): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:marcas,nombre,'.$marca->id,
        ]);

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

    public function storeModelo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'marca_id' => 'required|exists:marcas,id',
            'nombre' => 'required|string|max:255|unique:modelos,nombre,NULL,id,marca_id,'.$request->marca_id,
        ]);

        Modelo::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Modelo creado exitosamente.']);

        return to_route('productos.index');
    }

    public function updateModelo(Request $request, Modelo $modelo): RedirectResponse
    {
        $validated = $request->validate([
            'marca_id' => 'required|exists:marcas,id',
            'nombre' => 'required|string|max:255|unique:modelos,nombre,'.$modelo->id.',id,marca_id,'.$request->marca_id,
        ]);

        $modelo->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Modelo actualizado exitosamente.']);

        return to_route('productos.index');
    }

    public function destroyModelo(Modelo $modelo): RedirectResponse
    {
        if ($modelo->productos()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar el modelo porque tiene prendas asociadas.']);

            return to_route('productos.index');
        }

        $modelo->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Modelo eliminado exitosamente.']);

        return to_route('productos.index');
    }

    public function storeTalla(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:tallas,nombre',
        ]);

        Talla::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Talla creada exitosamente.']);

        return to_route('productos.index');
    }

    public function updateTalla(Request $request, Talla $talla): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:tallas,nombre,'.$talla->id,
        ]);

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
            'codigo_hex' => 'required|string|size:7|starts_with:#',
        ]);

        Color::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Color creado exitosamente.']);

        return to_route('productos.index');
    }

    public function updateColor(Request $request, Color $color): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:colores,nombre,'.$color->id,
            'codigo_hex' => 'required|string|size:7|starts_with:#',
        ]);

        $color->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Color actualizado exitosamente.']);

        return to_route('productos.index');
    }

    public function destroyColor(Color $color): RedirectResponse
    {
        if ($color->productos()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar el color porque tiene prendas asociadas.']);

            return to_route('productos.index');
        }

        $color->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Color eliminado exitosamente.']);

        return to_route('productos.index');
    }
}

<?php

use App\Actions\Inventory\SyncProductVariants;
use App\Models\Categoria;
use App\Models\Color;
use App\Models\Compra;
use App\Models\Inventario;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\ProductoVariante;
use App\Models\Role;
use App\Models\Talla;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(PermissionsSeeder::class));

test('guarda el genero seleccionado al crear y editar la prenda', function () {
    $datos = datosProductoValidos();
    $datos['genero'] = 'masculino';

    $this->actingAs(productosAdmin())->post(route('productos.store'), $datos)
        ->assertSessionHasNoErrors();

    $producto = Producto::firstOrFail();
    expect($producto->genero)->toBe('masculino');

    $datos['genero'] = 'femenino';
    $this->patch(route('productos.update', $producto), $datos)
        ->assertSessionHasNoErrors();

    expect($producto->fresh()->genero)->toBe('femenino');
});

test('rechaza un genero no admitido para la prenda', function () {
    $datos = datosProductoValidos();
    $datos['genero'] = 'invalido';

    $this->actingAs(productosAdmin())->post(route('productos.store'), $datos)
        ->assertSessionHasErrors('genero');

    expect(Producto::count())->toBe(0);
});

function productosAdmin(): User
{
    $user = User::factory()->create(['role' => Role::ADMIN]);
    $role = Role::where('nombre', Role::ADMIN)->firstOrFail();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function crearCatalogoBase(): array
{
    return [
        'categoria' => Categoria::create(['nombre' => 'Camisas']),
        'marca' => Marca::create(['nombre' => 'Shein']),
        'talla' => Talla::create(['nombre' => 'L']),
        'color' => Color::create(['nombre' => 'Azul Rey', 'codigo_hex' => '#0033CC']),
    ];
}

function datosProductoValidos(): array
{
    $base = crearCatalogoBase();

    return [
        'categoria_id' => $base['categoria']->id,
        'marca_id' => $base['marca']->id,
        'talla_id' => $base['talla']->id,
        'color_id' => $base['color']->id,
        'tallas' => [$base['talla']->id],
        'colores' => [$base['color']->id],
        'nombre' => 'camisas',
        'descripcion' => 'es un camisas para hombre',
        'precio_venta' => 80.00,
        'estado' => 'activo',
    ];
}

test('el admin puede registrar un producto con todos sus datos', function () {
    $user = productosAdmin();
    $datos = datosProductoValidos();

    $response = $this->actingAs($user)->post(route('productos.store'), $datos);

    $response->assertRedirect(route('productos.index'));
    $response->assertSessionHasNoErrors();
    expect(Producto::count())->toBe(1);
    expect(Producto::first()->genero)->toBe('femenino');
    expect(Producto::first()->precio_venta)->toBe('80.00');
    expect(Producto::first()->descripcion)->toBe('es un camisas para hombre');
    expect(Inventario::where('producto_id', Producto::first()->id)->exists())->toBeTrue()
        ->and(Producto::first()->inventario->stock_actual)->toBe(0)
        ->and(Producto::first()->inventario->disponible)->toBeFalse()
        ->and(Producto::first()->variantes->sole()->disponible)->toBeFalse();
});

test('el admin puede registrar una categoria desde productos', function () {
    $user = productosAdmin();

    $response = $this->actingAs($user)->post(route('productos.categorias.store'), [
        'nombre' => 'Vestidos',
    ]);

    $response->assertRedirect(route('productos.index'));
    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('categorias', ['nombre' => 'Vestidos']);
});

test('muestra en español el error de una categoria duplicada', function () {
    $user = productosAdmin();
    Categoria::create(['nombre' => 'Faldas']);

    $response = $this->actingAs($user)->post(route('productos.categorias.store'), [
        'nombre' => 'Faldas',
    ]);

    $response->assertSessionHasErrors([
        'nombre' => 'El nombre de la categoría ya está registrado.',
    ]);
    expect(Categoria::where('nombre', 'Faldas')->count())->toBe(1);
});

test('registra un color seleccionado de la paleta', function () {
    $user = productosAdmin();

    $response = $this->actingAs($user)->post(route('productos.colores.store'), [
        'nombre' => 'Rosa Pastel',
        'codigo_hex' => '#F9A8D4',
    ]);

    $response->assertRedirect(route('productos.index'));
    $response->assertSessionHasNoErrors();
    expect(Color::where('nombre', 'Rosa Pastel')->value('codigo_hex'))->toBe('#F9A8D4');
});

test('el admin puede registrar un producto con imagen', function () {
    Storage::fake('public');
    $user = productosAdmin();
    $datos = datosProductoValidos();
    $datos['imagen'] = UploadedFile::fake()->image('camisa.jpg', 10, 10);

    $response = $this->actingAs($user)->post(route('productos.store'), $datos);

    $response->assertRedirect(route('productos.index'));
    $response->assertSessionHasNoErrors();
    expect(Producto::first()->imagen)->not->toBeNull();
    Storage::disk('public')->assertExists(Producto::first()->imagen);
});

test('el admin puede registrar varias imagenes para una misma prenda', function () {
    Storage::fake('public');
    $datos = datosProductoValidos();
    $datos['imagenes'] = [
        UploadedFile::fake()->image('frente.jpg', 20, 20),
        UploadedFile::fake()->image('espalda.jpg', 20, 20),
        UploadedFile::fake()->image('lado.jpg', 20, 20),
    ];

    $this->actingAs(productosAdmin())->post(route('productos.store'), $datos)
        ->assertRedirect(route('productos.index'))
        ->assertSessionHasNoErrors();

    $producto = Producto::with('imagenes')->firstOrFail();

    expect($producto->imagenes)->toHaveCount(3)
        ->and($producto->imagen)->toBe($producto->imagenes->first()->ruta);

    $producto->imagenes->each(
        fn (ProductoImagen $imagen) => Storage::disk('public')->assertExists($imagen->ruta),
    );
});

test('asigna a cada imagen el color detectado y descarta colores no seleccionados', function () {
    Storage::fake('public');
    $datos = datosProductoValidos();
    $colorSeleccionado = Color::firstOrFail();
    $colorNoSeleccionado = Color::create(['nombre' => 'Verde Oliva', 'codigo_hex' => '#556B2F']);
    $datos['imagenes'] = [
        UploadedFile::fake()->image('frente.jpg', 20, 20),
        UploadedFile::fake()->image('espalda.jpg', 20, 20),
    ];
    $datos['imagenes_colores'] = [$colorSeleccionado->id, $colorNoSeleccionado->id];

    $this->actingAs(productosAdmin())->post(route('productos.store'), $datos)
        ->assertRedirect(route('productos.index'))
        ->assertSessionHasNoErrors();

    $producto = Producto::with('imagenes')->firstOrFail();

    expect($producto->imagenes->pluck('color_id')->all())
        ->toBe([$colorSeleccionado->id, null]);
});

test('actualiza el color de una imagen existente desde la deteccion automatica', function () {
    Storage::fake('public');
    $datos = datosProductoValidos();
    $colorPrincipal = Color::firstOrFail();
    $colorSecundario = Color::create(['nombre' => 'Verde Oliva', 'codigo_hex' => '#556B2F']);
    $datos['colores'] = [$colorPrincipal->id, $colorSecundario->id];
    $datos['imagenes'] = [UploadedFile::fake()->image('frente.jpg', 20, 20)];

    $this->actingAs(productosAdmin())->post(route('productos.store'), $datos)
        ->assertSessionHasNoErrors();

    $producto = Producto::with('imagenes')->firstOrFail();
    $imagen = $producto->imagenes->sole();

    $this->patch(route('productos.update', $producto), [
        ...$datos,
        'imagenes' => [],
        'imagenes_existentes_colores' => [
            ['id' => $imagen->id, 'color_id' => $colorSecundario->id],
        ],
    ])->assertSessionHasNoErrors();

    expect($imagen->fresh()->color_id)->toBe($colorSecundario->id);
});

test('el admin puede eliminar una imagen y agregar otra sin modificar la prenda', function () {
    Storage::fake('public');
    $datos = datosProductoValidos();
    $datos['imagenes'] = [
        UploadedFile::fake()->image('frente.jpg', 20, 20),
        UploadedFile::fake()->image('espalda.jpg', 20, 20),
    ];

    $this->actingAs(productosAdmin())->post(route('productos.store'), $datos);
    $producto = Producto::with('imagenes')->firstOrFail();
    $imagenEliminada = $producto->imagenes->first();

    $this->post(route('productos.update', $producto), [
        ...$datos,
        '_method' => 'PATCH',
        'imagenes' => [UploadedFile::fake()->image('lado.jpg', 20, 20)],
        'imagenes_eliminadas' => [$imagenEliminada->id],
    ])->assertSessionHasNoErrors();

    $producto->refresh()->load('imagenes');

    expect($producto->imagenes)->toHaveCount(2)
        ->and($producto->nombre)->toBe('camisas');
    Storage::disk('public')->assertMissing($imagenEliminada->ruta);
});

test('el admin puede eliminar una prenda que tiene varias imagenes', function () {
    Storage::fake('public');
    $datos = datosProductoValidos();
    $datos['imagenes'] = [
        UploadedFile::fake()->image('frente.jpg', 20, 20),
        UploadedFile::fake()->image('espalda.jpg', 20, 20),
    ];

    $this->actingAs(productosAdmin())->post(route('productos.store'), $datos);
    $producto = Producto::with('imagenes')->firstOrFail();
    $rutas = $producto->imagenes->pluck('ruta');

    $this->delete(route('productos.destroy', $producto))
        ->assertRedirect(route('productos.index'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('productos', ['id' => $producto->id]);
    $this->assertDatabaseMissing('producto_imagenes', ['producto_id' => $producto->id]);
    $rutas->each(fn (string $ruta) => Storage::disk('public')->assertMissing($ruta));
});

test('no permite registrar un producto duplicado por categoria, marca, talla y color', function () {
    $user = productosAdmin();
    $datos = datosProductoValidos();

    $this->actingAs($user)->post(route('productos.store'), $datos);
    $response = $this->actingAs($user)->post(route('productos.store'), $datos);

    $response->assertSessionHasErrors('producto');
    expect(session('errors')->get('producto')[0])->toBe('El producto ya está registrado. No se permiten productos duplicados.');
    expect(Producto::count())->toBe(1);
});

test('permite editar el producto actual sin marcarlo como duplicado de si mismo', function () {
    $user = productosAdmin();
    $datos = datosProductoValidos();
    $this->actingAs($user)->post(route('productos.store'), $datos);
    $producto = Producto::first();

    $response = $this->actingAs($user)->patch(route('productos.update', $producto->id), array_merge($datos, [
        'nombre' => 'camisas actualizadas',
        'precio_venta' => 95.00,
    ]));

    $response->assertSessionHasNoErrors();
    expect($producto->fresh()->nombre)->toBe('camisas actualizadas');
    expect($producto->fresh()->precio_venta)->toBe('95.00');
    expect(Producto::count())->toBe(1);
});

test('editar una prenda conserva el stock y el identificador de sus variantes', function () {
    $user = productosAdmin();
    $datos = datosProductoValidos();
    $this->actingAs($user)->post(route('productos.store'), $datos)->assertSessionHasNoErrors();

    $producto = Producto::firstOrFail();
    $variante = ProductoVariante::where('producto_id', $producto->id)->sole();
    $variante->update(['stock' => 6, 'disponible' => true]);
    $producto->inventario()->update(['stock_actual' => 6, 'disponible' => true]);

    $this->actingAs($user)->patch(route('productos.update', $producto), [
        ...$datos,
        'nombre' => 'camisas actualizadas',
    ])->assertSessionHasNoErrors();

    expect(ProductoVariante::where('producto_id', $producto->id)->sole()->id)->toBe($variante->id)
        ->and($variante->fresh()->stock)->toBe(6)
        ->and($producto->inventario->fresh()->stock_actual)->toBe(6)
        ->and($producto->inventario->fresh()->disponible)->toBeTrue();
});

test('restaura el stock por variante desde una compra registrada', function () {
    $user = productosAdmin();
    $datos = datosProductoValidos();
    $this->actingAs($user)->post(route('productos.store'), $datos)->assertSessionHasNoErrors();

    $producto = Producto::firstOrFail();
    $varianteAnterior = $producto->variantes()->sole();
    $compra = Compra::factory()->create(['estado' => 'completada']);
    $detalle = $compra->detalles()->create([
        'producto_id' => $producto->id,
        'producto_variante_id' => $varianteAnterior->id,
        'producto' => $producto->nombre,
        'talla' => $producto->talla->nombre,
        'color' => $producto->color->nombre,
        'cantidad' => 6,
        'precio_unitario' => 80,
        'subtotal' => 480,
    ]);

    $varianteAnterior->delete();

    app(SyncProductVariants::class)->execute(
        $producto,
        [$producto->talla_id],
        [$producto->color_id],
    );

    $varianteRestaurada = $producto->variantes()->sole();

    expect($varianteRestaurada->stock)->toBe(6)
        ->and($varianteRestaurada->disponible)->toBeTrue()
        ->and($detalle->fresh()->producto_variante_id)->toBe($varianteRestaurada->id)
        ->and($producto->inventario->fresh()->stock_actual)->toBe(6);
});

test('marca como no disponibles las variantes que no tienen stock', function () {
    $base = crearCatalogoBase();
    $producto = Producto::create([
        'categoria_id' => $base['categoria']->id,
        'marca_id' => $base['marca']->id,
        'talla_id' => $base['talla']->id,
        'color_id' => $base['color']->id,
        'nombre' => 'Producto sin stock',
        'precio_venta' => 100,
        'estado' => 'activo',
        'genero' => 'femenino',
    ]);
    $variante = ProductoVariante::create([
        'producto_id' => $producto->id,
        'talla_id' => $base['talla']->id,
        'color_id' => $base['color']->id,
        'stock' => 0,
        'disponible' => true,
    ]);

    app(SyncProductVariants::class)->execute(
        $producto,
        [$variante->talla_id],
        [$variante->color_id],
    );

    expect($variante->fresh()->disponible)->toBeFalse()
        ->and($producto->inventario->fresh()->disponible)->toBeFalse();
});

test('no permite editar un producto hacia una combinacion que ya pertenece a otro producto', function () {
    $user = productosAdmin();
    $base = crearCatalogoBase();
    $otraTalla = Talla::create(['nombre' => 'XXL']);

    $primer = Producto::create([
        'categoria_id' => $base['categoria']->id,
        'marca_id' => $base['marca']->id,
        'talla_id' => $base['talla']->id,
        'color_id' => $base['color']->id,
        'nombre' => 'camisas azul',
        'precio_venta' => 80.00,
        'estado' => 'activo',
        'genero' => 'masculino',
    ]);

    $segundo = Producto::create([
        'categoria_id' => $base['categoria']->id,
        'marca_id' => $base['marca']->id,
        'talla_id' => $otraTalla->id,
        'color_id' => $base['color']->id,
        'nombre' => 'camisas azul M',
        'precio_venta' => 80.00,
        'estado' => 'activo',
        'genero' => 'masculino',
    ]);

    $response = $this->actingAs($user)->patch(route('productos.update', $segundo->id), [
        'categoria_id' => $base['categoria']->id,
        'marca_id' => $base['marca']->id,
        'talla_id' => $base['talla']->id,
        'color_id' => $base['color']->id,
        'tallas' => [$base['talla']->id],
        'colores' => [$base['color']->id],
        'nombre' => 'camisas azul',
        'precio_venta' => 80.00,
        'estado' => 'activo',
        'genero' => 'masculino',
    ]);

    $response->assertSessionHasErrors('producto');
    expect($segundo->fresh()->talla_id)->toBe($otraTalla->id);
    expect(Producto::count())->toBe(2);
});

test('la busqueda filtra por talla y color', function () {
    $user = productosAdmin();
    $base = crearCatalogoBase();
    $otraTalla = Talla::create(['nombre' => 'XS']);
    $otroColor = Color::create(['nombre' => 'Negro', 'codigo_hex' => '#000000']);

    Producto::create([
        'categoria_id' => $base['categoria']->id,
        'marca_id' => $base['marca']->id,
        'talla_id' => $base['talla']->id,
        'color_id' => $base['color']->id,
        'nombre' => 'jean azul',
        'precio_venta' => 100.00,
        'estado' => 'activo',
        'genero' => 'femenino',
    ]);

    Producto::create([
        'categoria_id' => $base['categoria']->id,
        'marca_id' => $base['marca']->id,
        'talla_id' => $otraTalla->id,
        'color_id' => $otroColor->id,
        'nombre' => 'polera negra',
        'precio_venta' => 60.00,
        'estado' => 'activo',
        'genero' => 'femenino',
    ]);

    $negroResponse = $this->actingAs($user)->get(route('productos.index', ['search' => 'Negro']));
    $negroResponse->assertOk();
    $negroResponse
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Productos/index')
            ->has('productos', 1)
            ->where('productos.0.nombre', 'polera negra'));

    $negroResponse
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Productos/index')
            ->has('productos', 1)
            ->where('productos.0.nombre', 'polera negra'));

    $this->actingAs($user)->get(route('productos.index', ['search' => 'XS']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Productos/index')
            ->has('productos', 1)
            ->where('productos.0.nombre', 'polera negra'));
});

test('no elimina un color utilizado solamente por una variante', function () {
    $user = productosAdmin();
    $base = crearCatalogoBase();
    $colorVariante = Color::create(['nombre' => 'Lila', 'codigo_hex' => '#C8A2C8']);
    $producto = Producto::create([
        'categoria_id' => $base['categoria']->id,
        'marca_id' => $base['marca']->id,
        'talla_id' => $base['talla']->id,
        'color_id' => $base['color']->id,
        'nombre' => 'prenda con variante lila',
        'precio_venta' => 80,
        'estado' => 'activo',
        'genero' => 'femenino',
    ]);

    $producto->variantes()->create([
        'talla_id' => $base['talla']->id,
        'color_id' => $colorVariante->id,
        'stock' => 0,
        'disponible' => false,
    ]);

    $this->actingAs($user)->delete(route('productos.colores.destroy', $colorVariante))
        ->assertRedirect(route('productos.index'));

    expect($colorVariante->fresh())->not->toBeNull()
        ->and($producto->fresh())->not->toBeNull();
});

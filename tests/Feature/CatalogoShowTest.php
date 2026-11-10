<?php

use App\Models\Categoria;
use App\Models\Color;
use App\Models\Inventario;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\ProductoVariante;
use App\Models\Talla;
use Inertia\Testing\AssertableInertia as Assert;

function crearProductoCatalogo(array $datos = []): array
{
    $categoria = Categoria::create(['nombre' => 'Camisas']);
    $marca = Marca::create(['nombre' => 'Shein']);
    $talla = Talla::create(['nombre' => 'L']);
    $color = Color::create(['nombre' => 'Azul Rey', 'codigo_hex' => '#4169E1']);

    $producto = Producto::create([
        'categoria_id' => $categoria->id,
        'marca_id' => $marca->id,
        'talla_id' => $talla->id,
        'color_id' => $color->id,
        'nombre' => 'camisas',
        'precio_venta' => 80.00,
        'estado' => 'activo',
        'genero' => 'masculino',
    ]);

    Inventario::create([
        'producto_id' => $producto->id,
        'stock_actual' => $datos['stock'] ?? 29,
        'disponible' => true,
    ]);

    return [
        'producto' => $producto,
        'talla' => $talla,
        'color' => $color,
    ];
}

test('la pagina de detalle muestra las tallas estandar y habilita la del producto', function () {
    $base = crearProductoCatalogo();

    $this->get(route('catalogo.show', $base['producto']->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogo/show')
            ->has('producto.variantes', 1)
            ->where('producto.variantes.0.color_id', $base['color']->id)
            ->where('producto.variantes.0.talla_id', $base['talla']->id)
            ->where('producto.variantes.0.stock', 29)
            ->where('producto.variantes.0.disponible', true)
            ->has('producto.tallas', 3)
            ->where('producto.tallas.0.talla', 'M')
            ->where('producto.tallas.0.disponible', false)
            ->where('producto.tallas.1.talla', 'L')
            ->where('producto.tallas.1.stock_total', 29)
            ->where('producto.tallas.1.disponible', true)
            ->where('producto.tallas.2.talla', 'XL')
            ->where('producto.tallas.2.disponible', false)
            ->has('producto.colores', 1)
            ->where('producto.colores.0.color_nombre', 'Azul Rey')
            ->where('producto.colores.0.color_hex', '#4169E1')
            ->where('producto.colores.0.stock_total', 29)
            ->where('producto.colores.0.disponible', true));
});

test('la pagina de detalle comparte el stock total entre las opciones de talla y color', function () {
    $base = crearProductoCatalogo(['stock' => 10]);
    $tallaM = Talla::where('nombre', 'M')->firstOrFail();
    $tallaS = Talla::create(['nombre' => 'S']);

    ProductoVariante::create([
        'producto_id' => $base['producto']->id,
        'color_id' => $base['color']->id,
        'talla_id' => $tallaM->id,
        'stock' => 0,
        'disponible' => true,
    ]);
    ProductoVariante::create([
        'producto_id' => $base['producto']->id,
        'color_id' => $base['color']->id,
        'talla_id' => $tallaS->id,
        'stock' => 7,
        'disponible' => true,
    ]);
    ProductoVariante::create([
        'producto_id' => $base['producto']->id,
        'color_id' => $base['color']->id,
        'talla_id' => $base['talla']->id,
        'stock' => 3,
        'disponible' => true,
    ]);

    $this->get(route('catalogo.show', $base['producto']->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogo/show')
            ->has('producto.variantes', 3)
            ->where('producto.variantes.0.talla', 'M')
            ->where('producto.variantes.0.stock', 10)
            ->where('producto.variantes.0.disponible', true)
            ->where('producto.variantes.1.talla', 'S')
            ->where('producto.variantes.1.stock', 10)
            ->where('producto.variantes.1.disponible', true)
            ->where('producto.variantes.2.talla', 'L')
            ->where('producto.variantes.2.stock', 10)
            ->where('producto.variantes.2.disponible', true)
            ->where('producto.tallas.0.talla', 'S')
            ->where('producto.tallas.0.stock_total', 10)
            ->where('producto.tallas.0.disponible', true)
            ->where('producto.tallas.1.talla', 'M')
            ->where('producto.tallas.1.stock_total', 10)
            ->where('producto.tallas.1.disponible', true)
            ->where('producto.tallas.2.talla', 'L')
            ->where('producto.tallas.2.stock_total', 10)
            ->where('producto.tallas.2.disponible', true)
            ->where('producto.tallas.3.talla', 'XL')
            ->where('producto.tallas.3.disponible', false)
            ->where('producto.colores.0.disponible', true));
});

test('la pagina de detalle entrega todas las imagenes de la prenda para la galeria', function () {
    $base = crearProductoCatalogo();
    ProductoImagen::create([
        'producto_id' => $base['producto']->id,
        'color_id' => $base['color']->id,
        'ruta' => 'productos/frente.jpg',
        'orden' => 0,
    ]);
    ProductoImagen::create([
        'producto_id' => $base['producto']->id,
        'ruta' => 'productos/espalda.jpg',
        'orden' => 1,
    ]);

    $this->get(route('catalogo.show', $base['producto']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogo/show')
            ->has('producto.imagenes', 2)
            ->where('producto.imagenes.0', asset('storage/productos/frente.jpg'))
            ->where('producto.imagenes.1', asset('storage/productos/espalda.jpg'))
            ->where('producto.imagen_colores.0', $base['color']->id)
            ->where('producto.imagen_colores.1', null));
});

test('el catalogo entrega todas las imagenes para el cambio automatico de cada tarjeta', function () {
    $base = crearProductoCatalogo();
    $base['producto']->update(['imagen' => 'productos/principal.jpg']);

    ProductoImagen::create([
        'producto_id' => $base['producto']->id,
        'ruta' => 'productos/principal.jpg',
        'orden' => 0,
    ]);
    ProductoImagen::create([
        'producto_id' => $base['producto']->id,
        'ruta' => 'productos/segunda.jpg',
        'orden' => 1,
    ]);

    $this->get(route('catalogo'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogo/index')
            ->has('productos', 1)
            ->has('productos.0.imagenes', 2)
            ->where('productos.0.imagenes.0', asset('storage/productos/principal.jpg'))
            ->where('productos.0.imagenes.1', asset('storage/productos/segunda.jpg')));
});

test('el catalogo filtra las prendas por genero', function () {
    $base = crearProductoCatalogo();

    $this->get(route('catalogo', ['genero' => 'femenino']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogo/index')
            ->has('productos', 0)
            ->where('filtros.genero', 'femenino'));

    $this->get(route('catalogo', ['genero' => 'masculino']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogo/index')
            ->has('productos', 1)
            ->where('productos.0.id', $base['producto']->id)
            ->where('filtros.genero', 'masculino'));
});

test('entrega los colores en el mismo orden en que fueron registrados', function () {
    $base = crearProductoCatalogo();
    $colorVinotinto = Color::create(['nombre' => 'Vinotinto', 'codigo_hex' => '#722F37']);
    $colorBlanco = Color::create(['nombre' => 'Blanco', 'codigo_hex' => '#FFFFFF']);

    foreach ([$base['color'], $colorVinotinto, $colorBlanco] as $color) {
        ProductoVariante::create([
            'producto_id' => $base['producto']->id,
            'color_id' => $color->id,
            'talla_id' => $base['talla']->id,
            'stock' => 0,
            'disponible' => true,
        ]);
    }

    $this->get(route('catalogo.show', $base['producto']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogo/show')
            ->has('producto.colores', 3)
            ->where('producto.colores.0.color_nombre', 'Azul Rey')
            ->where('producto.colores.1.color_nombre', 'Vinotinto')
            ->where('producto.colores.2.color_nombre', 'Blanco'));
});

test('muestra disponible segun inventario aunque no permita comprar sin existencias', function () {
    $base = crearProductoCatalogo(['stock' => 0]);

    $this->get(route('catalogo.show', $base['producto']->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogo/show')
            ->where('producto.stock', 0)
            ->where('producto.disponible', true)
            ->where('producto.comprable', false)
            ->where('producto.variantes.0.disponible', false)
            ->where('producto.tallas.1.talla', 'L')
            ->where('producto.tallas.1.disponible', false));
});

test('el catalogo no muestra productos sin existencias', function () {
    $base = crearProductoCatalogo(['stock' => 0]);
    $base['producto']->inventario->update(['disponible' => true]);

    $this->get(route('catalogo'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogo/index')
            ->has('productos', 0));

    $base['producto']->inventario->update(['stock_actual' => 1, 'disponible' => true]);

    $this->get(route('catalogo'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogo/index')
            ->has('productos', 1)
            ->where('productos.0.id', $base['producto']->id)
            ->where('productos.0.disponible', true)
            ->where('productos.0.stock', 1));
});

test('el catalogo no muestra accesorios ni shorts en la lista de categorias', function () {
    Categoria::create(['nombre' => 'Accesorios']);
    Categoria::create(['nombre' => 'Shorts']);
    Categoria::create(['nombre' => 'Vestidos']);

    $this->get(route('catalogo'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogo/index')
            ->has('categorias', 1)
            ->where('categorias.0.nombre', 'Vestidos'));
});

test('ignora una talla antigua metro y nunca la ofrece en el catalogo', function () {
    $base = crearProductoCatalogo();
    $tallaMetro = Talla::create(['nombre' => 'METRO']);
    $colorArena = Color::create(['nombre' => 'Arena', 'codigo_hex' => '#C4A882']);

    ProductoVariante::create([
        'producto_id' => $base['producto']->id,
        'color_id' => $colorArena->id,
        'talla_id' => $tallaMetro->id,
        'stock' => 50,
        'disponible' => true,
    ]);

    $this->get(route('catalogo.show', $base['producto']->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalogo/show')
            ->has('producto.variantes', 1)
            ->has('producto.tallas', 3)
            ->where('producto.tallas.1.talla', 'L')
            ->where('producto.tallas.1.stock_total', 29)
            ->where('producto.tallas.1.disponible', true)
            ->has('producto.colores', 1)
            ->where('producto.colores.0.color_nombre', 'Azul Rey')
            ->where('producto.colores.0.disponible', true));
});

test('el catalogo no muestra la burbuja flotante de whatsapp', function () {
    $catalogo = file_get_contents(resource_path('js/pages/Catalogo/index.vue'));

    expect($catalogo)->not->toContain('aria-label="Contactar por WhatsApp"');
});

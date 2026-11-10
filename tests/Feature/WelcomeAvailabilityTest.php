<?php

use App\Models\Categoria;
use App\Models\Color;
use App\Models\Inventario;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\Talla;
use Inertia\Testing\AssertableInertia as Assert;

test('la página principal muestra las marcas registradas dinámicamente', function () {
    Marca::create(['nombre' => 'Shein']);
    Marca::create(['nombre' => 'Zara']);

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('marcas', 2)
            ->where('marcas.0.nombre', 'Shein')
            ->where('marcas.1.nombre', 'Zara'));
});

test('la página principal entrega las imágenes ordenadas de cada producto reciente', function () {
    $categoria = Categoria::create(['nombre' => 'Vestidos']);
    $marca = Marca::create(['nombre' => 'Shein']);
    $talla = Talla::firstOrCreate(['nombre' => 'M']);
    $color = Color::firstOrCreate(['codigo_hex' => '#FF0000'], ['nombre' => 'Rojo']);
    $producto = Producto::create([
        'categoria_id' => $categoria->id,
        'marca_id' => $marca->id,
        'talla_id' => $talla->id,
        'color_id' => $color->id,
        'nombre' => 'Vestido floral',
        'precio_venta' => 180,
        'imagen' => 'productos/frente.jpg',
        'estado' => 'activo',
        'genero' => 'femenino',
    ]);

    Inventario::create([
        'producto_id' => $producto->id,
        'stock_actual' => 3,
        'disponible' => true,
    ]);

    ProductoImagen::create([
        'producto_id' => $producto->id,
        'ruta' => 'productos/frente.jpg',
        'orden' => 0,
    ]);
    ProductoImagen::create([
        'producto_id' => $producto->id,
        'ruta' => 'productos/espalda.jpg',
        'orden' => 1,
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('productosRecientes', 1)
            ->where('productosRecientes.0.imagenes', [
                asset('storage/productos/frente.jpg'),
                asset('storage/productos/espalda.jpg'),
            ]));
});

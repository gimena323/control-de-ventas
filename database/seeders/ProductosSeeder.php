<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Color;
use App\Models\Marca;
use App\Models\Modelo;
use App\Models\Talla;
use Illuminate\Database\Seeder;

class ProductosSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Poleras', 'Camisas', 'Blusas', 'Jeans', 'Pantalones',
            'Shorts', 'Vestidos', 'Faldas', 'Chaquetas', 'Abrigos',
            'Sudaderas', 'Ropa deportiva', 'Ropa interior', 'Accesorios',
        ] as $nombre) {
            Categoria::create(['nombre' => $nombre]);
        }

        $marcas = [
            'Emma Fashion' => ['Exclusive', 'Básico', 'Premium', 'Trend', 'Classic'],
            'Scraper' => ['Urban', 'Street', 'Oversize', 'Basic', 'Casual'],
            'Tattoo' => ['Classic', 'Premium', 'Slim Fit', 'Comfort', 'Fashion'],
            'Galaxy' => ['Galaxy Sport', 'Galaxy Trend', 'Galaxy Urban', 'Galaxy Premium', 'Galaxy Elegant'],
            'Adidas' => ['Originals', 'Performance', 'Essentials', 'Sportswear'],
            'Nike' => ['Air', 'Dri-FIT', 'Club', 'Sportswear'],
            'Puma' => ['Classic', 'Sport', 'Essentials', 'Active'],
            'Zara' => ['Casual', 'Elegant', 'Office', 'Summer Collection'],
            'H&M' => ['Casual', 'Modern', 'Basic', 'Street'],
            "Levi's" => ['Original', 'Classic', 'Modern', 'Premium'],
            'Guess' => ['Classic', 'Premium', 'Trendy', 'Night'],
            'Tommy Hilfiger' => ['Classic', 'Sport', 'Premium', 'Essential'],
            'Calvin Klein' => ['Classic', 'Modern', 'Sport', 'Premium'],
            'American Eagle' => ['Classic', 'Casual', 'Street', 'Denim'],
            'Forever 21' => ['Trend', 'Basic', 'Casual', 'Party'],
            'Bershka' => ['Urban', 'Street', 'Basic', 'Trend'],
            'Pull&Bear' => ['Casual', 'Street', 'Basic', 'Summer'],
            'Shein' => ['Trend', 'Basic', 'Elegant', 'Party'],
            'New Balance' => ['Classic', 'Sport', 'Essentials', 'Active'],
            'Reebok' => ['Classic', 'Sport', 'Essentials', 'Fitness'],
        ];

        foreach ($marcas as $marcaNombre => $modelos) {
            $marca = Marca::create(['nombre' => $marcaNombre]);

            foreach ($modelos as $modeloNombre) {
                Modelo::create([
                    'marca_id' => $marca->id,
                    'nombre' => $modeloNombre,
                ]);
            }
        }

        foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'Talla Única'] as $nombre) {
            Talla::create(['nombre' => $nombre]);
        }

        $colores = [
            // Blancos, cremas y neutros claros
            ['nombre' => 'Blanco', 'codigo_hex' => '#FFFFFF'],
            ['nombre' => 'Marfil', 'codigo_hex' => '#FFFFF0'],
            ['nombre' => 'Crema', 'codigo_hex' => '#FFFDD0'],
            ['nombre' => 'Beige', 'codigo_hex' => '#F5F5DC'],
            ['nombre' => 'Arena', 'codigo_hex' => '#C4A882'],
            ['nombre' => 'Camel', 'codigo_hex' => '#C19A6B'],
            ['nombre' => 'Caqui', 'codigo_hex' => '#C3B091'],
            ['nombre' => 'Gris Claro', 'codigo_hex' => '#D3D3D3'],
            ['nombre' => 'Plateado', 'codigo_hex' => '#C0C0C0'],

            // Grises
            ['nombre' => 'Gris', 'codigo_hex' => '#808080'],
            ['nombre' => 'Gris Oscuro', 'codigo_hex' => '#4F4F4F'],
            ['nombre' => 'Gris Marengo', 'codigo_hex' => '#3B3B3B'],
            ['nombre' => 'Gris Pizarra', 'codigo_hex' => '#708090'],
            ['nombre' => 'Gris Perla', 'codigo_hex' => '#E5E4E2'],

            // Negros
            ['nombre' => 'Negro', 'codigo_hex' => '#000000'],
            ['nombre' => 'Negro Azabache', 'codigo_hex' => '#0B0B0B'],
            ['nombre' => 'Carbón', 'codigo_hex' => '#2C2C2C'],

            // Marrones / Cafés
            ['nombre' => 'Café Claro', 'codigo_hex' => '#8B6914'],
            ['nombre' => 'Café', 'codigo_hex' => '#6F4E37'],
            ['nombre' => 'Café Oscuro', 'codigo_hex' => '#4A2C2A'],
            ['nombre' => 'Marrón', 'codigo_hex' => '#A52A2A'],
            ['nombre' => 'Marrón Claro', 'codigo_hex' => '#C4A882'],
            ['nombre' => 'Chocolate', 'codigo_hex' => '#7B3F00'],
            ['nombre' => 'Terracota', 'codigo_hex' => '#CC4E2C'],
            ['nombre' => 'Óxido', 'codigo_hex' => '#B7410E'],

            // Rosas
            ['nombre' => 'Rosa Bebé', 'codigo_hex' => '#F4C2C2'],
            ['nombre' => 'Rosa Pastel', 'codigo_hex' => '#FFD1DC'],
            ['nombre' => 'Palo Rosa', 'codigo_hex' => '#DEA5A4'],
            ['nombre' => 'Rosado', 'codigo_hex' => '#FF69B4'],
            ['nombre' => 'Rosa Intenso', 'codigo_hex' => '#FF1493'],
            ['nombre' => 'Fucsia', 'codigo_hex' => '#FF00FF'],
            ['nombre' => 'Magenta', 'codigo_hex' => '#CA1F7B'],
            ['nombre' => 'Coral', 'codigo_hex' => '#FF7F50'],

            // Rojos
            ['nombre' => 'Salmón', 'codigo_hex' => '#FA8072'],
            ['nombre' => 'Rojo', 'codigo_hex' => '#FF0000'],
            ['nombre' => 'Rojo Carmesí', 'codigo_hex' => '#DC143C'],
            ['nombre' => 'Rojo Ladrillo', 'codigo_hex' => '#B22222'],
            ['nombre' => 'Vinotinto', 'codigo_hex' => '#722F37'],
            ['nombre' => 'Bordó', 'codigo_hex' => '#800020'],
            ['nombre' => 'Granate', 'codigo_hex' => '#6B0F1A'],

            // Naranjas
            ['nombre' => 'Durazno', 'codigo_hex' => '#FFDAB9'],
            ['nombre' => 'Naranja', 'codigo_hex' => '#FFA500'],
            ['nombre' => 'Naranja Quemado', 'codigo_hex' => '#CC5500'],

            // Amarillos
            ['nombre' => 'Amarillo Pastel', 'codigo_hex' => '#FFFAA0'],
            ['nombre' => 'Amarillo', 'codigo_hex' => '#FFFF00'],
            ['nombre' => 'Amarillo Pollo', 'codigo_hex' => '#FFD700'],
            ['nombre' => 'Mostaza', 'codigo_hex' => '#FFDB58'],
            ['nombre' => 'Dorado', 'codigo_hex' => '#FFD700'],
            ['nombre' => 'Aceite', 'codigo_hex' => '#B8860B'],

            // Verdes claros
            ['nombre' => 'Verde Claro', 'codigo_hex' => '#90EE90'],
            ['nombre' => 'Verde Agua', 'codigo_hex' => '#A0D6B4'],
            ['nombre' => 'Verde Menta', 'codigo_hex' => '#98FF98'],
            ['nombre' => 'Verde Salvia', 'codigo_hex' => '#87AE73'],
            ['nombre' => 'Verde Lima', 'codigo_hex' => '#32CD32'],

            // Verdes medios / oscuros
            ['nombre' => 'Verde', 'codigo_hex' => '#008000'],
            ['nombre' => 'Verde Olivo', 'codigo_hex' => '#556B2F'],
            ['nombre' => 'Verde Militar', 'codigo_hex' => '#4B5320'],
            ['nombre' => 'Verde Esmeralda', 'codigo_hex' => '#50C878'],
            ['nombre' => 'Verde Bosque', 'codigo_hex' => '#228B22'],
            ['nombre' => 'Verde Botella', 'codigo_hex' => '#006400'],
            ['nombre' => 'Verde Pino', 'codigo_hex' => '#013220'],

            // Azules claros / celestes
            ['nombre' => 'Celeste', 'codigo_hex' => '#87CEEB'],
            ['nombre' => 'Azul Cielo', 'codigo_hex' => '#87CEEB'],
            ['nombre' => 'Azul Claro', 'codigo_hex' => '#ADD8E6'],
            ['nombre' => 'Aguamarina', 'codigo_hex' => '#7FFFD4'],
            ['nombre' => 'Turquesa', 'codigo_hex' => '#40E0D0'],

            // Azules medios / oscuros
            ['nombre' => 'Azul', 'codigo_hex' => '#0000FF'],
            ['nombre' => 'Azul Rey', 'codigo_hex' => '#4169E1'],
            ['nombre' => 'Azul Marino', 'codigo_hex' => '#000080'],
            ['nombre' => 'Azul Medianoche', 'codigo_hex' => '#191970'],
            ['nombre' => 'Azul Prusia', 'codigo_hex' => '#003153'],
            ['nombre' => 'Índigo', 'codigo_hex' => '#4B0082'],
            ['nombre' => 'Azul Eléctrico', 'codigo_hex' => '#0066FF'],
            ['nombre' => 'Azul Acero', 'codigo_hex' => '#4682B4'],

            // Púrpuras / Violetas
            ['nombre' => 'Lavanda', 'codigo_hex' => '#E6E6FA'],
            ['nombre' => 'Lila', 'codigo_hex' => '#C8A2C8'],
            ['nombre' => 'Malva', 'codigo_hex' => '#D8BFD8'],
            ['nombre' => 'Morado', 'codigo_hex' => '#800080'],
            ['nombre' => 'Violeta', 'codigo_hex' => '#8B00FF'],
            ['nombre' => 'Púrpura', 'codigo_hex' => '#69359C'],
            ['nombre' => 'Ciruela', 'codigo_hex' => '#843179'],
            ['nombre' => 'Uva', 'codigo_hex' => '#6C2DC7'],
        ];

        foreach ($colores as $color) {
            Color::create($color);
        }
    }
}

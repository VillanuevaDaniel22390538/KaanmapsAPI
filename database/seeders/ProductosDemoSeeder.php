<?php

namespace Database\Seeders;

use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Models\Productor;
use App\Support\CategoriaTree;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Productos DEMO para probar filtros por categoría/subcategoría/tipo. Idempotente:
 * se identifican por (ID_Productor, Nombre_Producto). Todos llevan la etiqueta "demo"
 * para poder limpiarlos:
 *   DELETE FROM productos WHERE JSON_CONTAINS(Etiquetas, '"demo"');
 *
 * Se asignan a un productor existente de la misma categoría raíz; si no hay, se crea
 * (una sola vez) un productor "Productor Demo". No usar en producción.
 */
class ProductosDemoSeeder extends Seeder
{
    /** [nodo (nombre de categoría), nombre, precio, unidad, disponible, inventario, etiquetas extra, fecha] */
    private array $demo = [
        ['Pollos y Gallinas', 'Pollo de rancho entero', '120.00', 'kg', true, 40, ['fresco'], '2026-10-20'],
        ['Patos', 'Pato criollo vivo', '260.00', 'pieza', true, 12, [], '2026-10-18'],
        ['Pavos', 'Pavo de engorda (4 kg)', '780.00', 'pieza', false, 0, ['temporada'], '2026-10-01'],
        ['Cerdo Pelón Mexicano', 'Cerdo pelón en canal', '95.00', 'kg', true, 80, ['tradicional'], '2026-10-12'],
        ['Lechones', 'Lechón de 20 días', '950.00', 'pieza', true, 6, [], '2026-10-22'],
        ['Ganado Lechero', 'Leche fresca de vaca', '18.00', 'litro', true, 200, ['fresco'], '2026-10-25'],
        ['Becerros', 'Becerro destetado', '8500.00', 'pieza', true, 3, [], '2026-09-30'],
        ['Camarón y Mariscos', 'Camarón de granja', '210.00', 'kg', true, 50, ['fresco', 'organico'], '2026-10-27'],
        ['Pescado de cultivo', 'Tilapia fresca', '85.00', 'kg', true, 70, ['fresco'], '2026-10-26'],
        ['Guayaberas', 'Guayabera bordada a mano', '1450.00', 'pieza', true, 9, ['artesanal'], '2026-10-05'],
        ['Huipiles y Ternos', 'Huipil maya tradicional', '1800.00', 'pieza', true, 5, ['artesanal', 'tradicional'], '2026-10-06'],
        ['Hamacas de Algodón', 'Hamaca de algodón matrimonial', '1100.00', 'pieza', true, 15, ['artesanal'], '2026-10-10'],
        ['Hamacas de Nylon', 'Hamaca de nylon individual', '420.00', 'pieza', true, 30, [], '2026-10-11'],
        ['Sillas Hamaca', 'Silla hamaca tejida', '650.00', 'pieza', false, 0, ['artesanal'], '2026-09-15'],
        ['Sombreros de Palma', 'Sombrero de palma jipi', '350.00', 'pieza', true, 25, ['artesanal'], '2026-10-14'],
        ['Macetas', 'Maceta de barro decorada', '120.00', 'pieza', true, 40, ['artesanal'], '2026-10-16'],
        ['Figuras y Máscaras', 'Máscara tallada en madera de zapote', '780.00', 'pieza', true, 7, ['artesanal'], '2026-10-02'],
        ['Joyería Artesanal', 'Aretes de ámbar y plata', '540.00', 'par', true, 18, ['artesanal'], '2026-10-28'],
        ['Miel de Abeja', 'Miel multifloral (500 g)', '140.00', 'frasco', true, 60, ['organico'], '2026-10-29'],
        ['Meliponicultura', 'Miel de melipona (250 ml)', '320.00', 'frasco', true, 20, ['organico', 'tradicional'], '2026-10-30'],
        ['Cosmética Natural', 'Jabón de miel y propóleo', '85.00', 'pieza', true, 45, ['natural'], '2026-10-23'],
        ['Frutas', 'Papaya maradol', '24.00', 'kg', true, 120, ['fresco'], '2026-10-31'],
        ['Verduras', 'Calabaza criolla', '18.00', 'kg', true, 90, ['organico'], '2026-10-24'],
        ['Chiles', 'Chile habanero fresco', '70.00', 'kg', true, 55, ['fresco'], '2026-10-21'],
        ['Semillas e Injertos', 'Injerto de limón persa', '130.00', 'pieza', false, 0, [], '2026-09-20'],
    ];

    public function run(): void
    {
        $tree = CategoriaTree::current();
        $creados = 0;

        foreach ($this->demo as [$nodo, $nombre, $precio, $unidad, $disponible, $inventario, $tags, $fecha]) {
            $categoria = CategoriaProducto::where('Nombre_Categoria', $nodo)->first();
            if (! $categoria) {
                throw new RuntimeException("No existe la categoría '{$nodo}'. Corre primero CategoriaSeeder.");
            }

            $raizId = $tree->enNivel($categoria->ID_Categoria, 1);
            $productor = $this->productorPara($raizId);

            $producto = Producto::updateOrCreate(
                ['ID_Productor' => $productor->ID_Productor, 'Nombre_Producto' => $nombre],
                [
                    'ID_Categoria' => $categoria->ID_Categoria,
                    'Descripcion' => "Producto de demostración: {$nombre}.",
                    'Precio' => $precio,
                    'Moneda' => 'MXN',
                    'Tipo_Unidad' => $unidad,
                    'Cantidad_Inventario' => $inventario,
                    'Disponible' => $disponible,
                    'URLs_Imagenes' => [],
                    'Etiquetas' => array_values(array_unique(array_merge(['demo'], $tags))),
                ]
            );

            if ($producto->wasRecentlyCreated) {
                // Fecha_Registro solo al crear (no se pisa en reejecuciones)
                $producto->newQuery()->where('ID_Producto', $producto->ID_Producto)->update(['Fecha_Registro' => $fecha]);
                $creados++;
            }
        }

        $this->command?->info("Productos demo creados: {$creados}");
    }

    private function productorPara(?int $raizId): Productor
    {
        if ($raizId) {
            $existente = Productor::where('categoria_id', $raizId)->where('Estado', 'Activo')->orderBy('ID_Productor')->first();
            if ($existente) {
                return $existente;
            }
        }

        return Productor::firstOrCreate(
            ['Nombre_Negocio' => 'Productor Demo'],
            ['Descripcion_Negocio' => 'Productor de demostración para pruebas.', 'Estado' => 'Activo', 'Estado_Verificacion' => 'Pendiente']
        );
    }
}

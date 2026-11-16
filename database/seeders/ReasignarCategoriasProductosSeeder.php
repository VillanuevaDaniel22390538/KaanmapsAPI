<?php

namespace Database\Seeders;

use App\Models\CategoriaProducto;
use App\Support\CategoriaTree;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Mueve los productos existentes del nodo raíz (o de una categoría desactivada) a su
 * subcategoría real, usando UNA sola vez el id local de la app guardado en
 * productos.subcategoria_id (1–18, en el orden de database/seeders/data/categorias_tree.php).
 *
 * Idempotente: solo toca productos cuya categoría actual NO es ya un nodo activo de
 * nivel >= 2. Si la raíz del producto no coincide con la de la subcategoría de la app,
 * el producto se omite y se reporta (no se adivina).
 *
 * Requiere haber corrido CategoriaSeeder antes.
 */
class ReasignarCategoriasProductosSeeder extends Seeder
{
    public function run(): void
    {
        $arbol = require database_path('seeders/data/categorias_tree.php');

        // id local de la app (1..N) => id real de categorias_productos
        $mapa = [];
        $n = 0;
        foreach ($arbol as $raiz) {
            foreach ($raiz['subcategorias'] as $sub) {
                $n++;
                $fila = CategoriaProducto::where('Nombre_Categoria', $sub['nombre'])->first();
                if (! $fila) {
                    throw new RuntimeException("No existe la subcategoría '{$sub['nombre']}'. Corre primero CategoriaSeeder.");
                }
                $mapa[$n] = $fila->ID_Categoria;
            }
        }

        $tree = CategoriaTree::current();
        $movidos = 0;
        $omitidos = [];

        DB::table('productos')
            ->whereNotNull('subcategoria_id')
            ->whereIn('subcategoria_id', array_keys($mapa))
            ->orderBy('ID_Producto')
            ->each(function ($p) use ($mapa, $tree, &$movidos, &$omitidos) {
                $actual = (int) $p->ID_Categoria;

                // Ya está en un nodo específico y activo: no se toca.
                if ($tree->has($actual) && $tree->estaActiva($actual) && $tree->nivel($actual) >= 2) {
                    return;
                }

                $destino = $mapa[(int) $p->subcategoria_id];

                if ($tree->has($actual) && $tree->enNivel($destino, 1) !== $tree->enNivel($actual, 1)) {
                    $omitidos[] = $p->ID_Producto;

                    return;
                }

                DB::table('productos')->where('ID_Producto', $p->ID_Producto)->update(['ID_Categoria' => $destino]);
                $movidos++;
            });

        $this->command?->info("Productos reasignados: {$movidos}");

        if ($omitidos) {
            $this->command?->warn('Omitidos por no coincidir la raíz (revisar a mano): '.implode(', ', $omitidos));
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\CategoriaProducto;
use App\Support\CategoriaTree;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Siembra el árbol Categoría → Subcategoría → Tipo. Idempotente.
 *
 * - Reutiliza filas existentes por Nombre_Categoria (Apicultura, Agricultura,
 *   Ganadería, Artesanías, Joyería Artesanal…): actualiza padre, orden y activo,
 *   conserva su slug, descripción y color.
 * - Crea las que no existan.
 * - Las filas que NO están en el árbol se marcan activo=false. Nunca se borra nada.
 */
class CategoriaSeeder extends Seeder
{
    /** @var array<string, int> slug => id, para detectar colisiones */
    private array $slugsEnUso = [];

    public function run(): void
    {
        $arbol = require database_path('seeders/data/categorias_tree.php');

        DB::transaction(function () use ($arbol) {
            $this->slugsEnUso = CategoriaProducto::query()
                ->whereNotNull('slug')
                ->pluck('ID_Categoria', 'slug')
                ->all();

            $idsEnArbol = [];

            foreach ($arbol as $i => $raiz) {
                $rootRow = $this->upsert($raiz['nombre'], null, $i + 1, null, null);
                $idsEnArbol[] = $rootRow->ID_Categoria;

                foreach ($raiz['subcategorias'] ?? [] as $j => $sub) {
                    $subRow = $this->upsert($sub['nombre'], $rootRow, $j + 1, null, $rootRow->Color_Hex);
                    $idsEnArbol[] = $subRow->ID_Categoria;

                    foreach ($sub['tipos'] ?? [] as $k => $tipo) {
                        $tipoRow = $this->upsert($tipo, $subRow, $k + 1, null, $rootRow->Color_Hex);
                        $idsEnArbol[] = $tipoRow->ID_Categoria;
                    }
                }
            }

            // Lo que no aplica a la app: desactivar, nunca borrar.
            CategoriaProducto::query()
                ->whereNotIn('ID_Categoria', $idsEnArbol)
                ->where('activo', true)
                ->update(['activo' => false]);
        });

        CategoriaTree::flush();

        $this->validarProfundidad();
    }

    private function upsert(string $nombre, ?CategoriaProducto $padre, int $orden, ?string $descripcion, ?string $color): CategoriaProducto
    {
        $existente = CategoriaProducto::where('Nombre_Categoria', $nombre)->first();

        $slug = $existente?->slug ?: $this->slugPara($nombre, $padre);

        $fila = CategoriaProducto::updateOrCreate(
            ['Nombre_Categoria' => $nombre],
            [
                'slug' => $slug,
                'ID_Categoria_Padre' => $padre?->ID_Categoria,
                'orden' => $orden,
                'activo' => true,
            ]
        );

        // Solo se rellenan datos "de catálogo" si faltan; no pisamos lo curado a mano.
        $relleno = [];
        if ($fila->Descripcion === null && $descripcion !== null) {
            $relleno['Descripcion'] = $descripcion;
        }
        if ($fila->Color_Hex === null && $color !== null) {
            $relleno['Color_Hex'] = $color;
        }
        if ($relleno) {
            $fila->update($relleno);
        }

        $this->slugsEnUso[$slug] = $fila->ID_Categoria;

        return $fila;
    }

    /** Slug estable: Str::slug(nombre); si choca con otro nodo, se antepone el slug del padre. */
    private function slugPara(string $nombre, ?CategoriaProducto $padre): string
    {
        $base = Str::slug($nombre) ?: 'categoria';

        if (! isset($this->slugsEnUso[$base])) {
            return $base;
        }

        $conPadre = $padre?->slug ? $padre->slug.'-'.$base : $base.'-2';
        $slug = $conPadre;
        $n = 2;
        while (isset($this->slugsEnUso[$slug])) {
            $slug = $conPadre.'-'.$n++;
        }

        return $slug;
    }

    private function validarProfundidad(): void
    {
        $tree = CategoriaTree::current();

        foreach ($tree->idsDeNivel(4) as $id) {
            throw new RuntimeException("La categoría {$id} excede los 3 niveles permitidos.");
        }
    }
}

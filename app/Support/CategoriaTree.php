<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Árbol de categorías precalculado en memoria.
 *
 * La tabla es chica (decenas de filas), así que se lee UNA vez (cacheada) y de
 * ahí salen nivel, ancestros y descendientes sin consultas adicionales. Resuelve
 * el filtro "incluye descendientes" con un simple whereIn de ids precalculados.
 *
 * Los nodos inactivos también están en el árbol: un producto que apunte a una
 * categoría desactivada sigue resolviendo sus ancestros.
 */
class CategoriaTree
{
    private const CACHE_KEY = 'kaanmaps.categorias.tree.v1';

    /** @var array<int, array{id:int, padre_id:?int, nombre:string, slug:?string, activo:bool}> */
    private array $nodes = [];

    /** @var array<int, list<int>> */
    private array $children = [];

    /** @var array<int, int> */
    private array $levels = [];

    /**
     * @param  array<int, array{id:int, padre_id:?int, nombre:string, slug:?string, activo:bool}>  $rows
     */
    public function __construct(array $rows)
    {
        foreach ($rows as $row) {
            $this->nodes[$row['id']] = $row;
        }

        foreach ($this->nodes as $id => $node) {
            $padre = $node['padre_id'];
            if ($padre !== null && isset($this->nodes[$padre])) {
                $this->children[$padre][] = $id;
            }
        }
    }

    /** Instancia compartida por request (se resuelve desde el contenedor). */
    public static function current(): self
    {
        return app(self::class);
    }

    public static function build(): self
    {
        $rows = Cache::rememberForever(self::CACHE_KEY, function () {
            return DB::table('categorias_productos')
                ->orderBy('ID_Categoria')
                ->get(['ID_Categoria', 'ID_Categoria_Padre', 'Nombre_Categoria', 'slug', 'activo'])
                ->map(fn ($r) => [
                    'id' => (int) $r->ID_Categoria,
                    'padre_id' => $r->ID_Categoria_Padre !== null ? (int) $r->ID_Categoria_Padre : null,
                    'nombre' => $r->Nombre_Categoria,
                    'slug' => $r->slug,
                    'activo' => (bool) $r->activo,
                ])
                ->all();
        });

        return new self($rows);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        app()->forgetInstance(self::class);
    }

    public function has(int $id): bool
    {
        return isset($this->nodes[$id]);
    }

    public function padreId(int $id): ?int
    {
        return $this->nodes[$id]['padre_id'] ?? null;
    }

    /** Nivel por profundidad: raíz = 1. Devuelve null si el id no existe. */
    public function nivel(int $id): ?int
    {
        if (! isset($this->nodes[$id])) {
            return null;
        }

        if (isset($this->levels[$id])) {
            return $this->levels[$id];
        }

        $nivel = 1;
        $actual = $id;
        $visitados = [$id => true];

        while (($padre = $this->nodes[$actual]['padre_id']) !== null && isset($this->nodes[$padre])) {
            if (isset($visitados[$padre])) {
                break; // ciclo defensivo
            }
            $visitados[$padre] = true;
            $nivel++;
            $actual = $padre;
        }

        return $this->levels[$id] = $nivel;
    }

    /**
     * Ruta desde la raíz hasta el nodo, p. ej. [raiz, subcategoria, tipo].
     *
     * @return list<int>
     */
    public function ruta(int $id): array
    {
        if (! isset($this->nodes[$id])) {
            return [];
        }

        $ruta = [$id];
        $visitados = [$id => true];
        $actual = $id;

        while (($padre = $this->nodes[$actual]['padre_id']) !== null && isset($this->nodes[$padre]) && ! isset($visitados[$padre])) {
            array_unshift($ruta, $padre);
            $visitados[$padre] = true;
            $actual = $padre;
        }

        return $ruta;
    }

    /** Id del ancestro (o el propio nodo) en el nivel indicado; null si no aplica. */
    public function enNivel(int $id, int $nivel): ?int
    {
        return $this->ruta($id)[$nivel - 1] ?? null;
    }

    /**
     * Ids del nodo y todos sus descendientes.
     *
     * @return list<int>
     */
    public function idsConDescendientes(int $id): array
    {
        if (! isset($this->nodes[$id])) {
            return [];
        }

        $resultado = [];
        $pila = [$id];

        while ($pila) {
            $actual = array_pop($pila);
            if (isset($resultado[$actual])) {
                continue;
            }
            $resultado[$actual] = true;
            foreach ($this->children[$actual] ?? [] as $hijo) {
                $pila[] = $hijo;
            }
        }

        return array_keys($resultado);
    }

    /**
     * Ids de todos los nodos de un nivel.
     *
     * @return list<int>
     */
    public function idsDeNivel(int $nivel): array
    {
        return array_values(array_filter(
            array_keys($this->nodes),
            fn (int $id) => $this->nivel($id) === $nivel
        ));
    }
}

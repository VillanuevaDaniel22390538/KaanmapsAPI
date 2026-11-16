<?php

namespace App\Repositories\Eloquent;

use App\Models\Producto;
use App\Repositories\Interfaces\ProductoRepositoryInterface;
use App\Support\CategoriaTree;
use Illuminate\Database\Eloquent\Builder;

class ProductoRepository implements ProductoRepositoryInterface
{
    public function getAll(array $filters, int $perPage = 15)
    {
        $query = Producto::query()
            // Solo productos de productores visibles en el catálogo público
            ->whereHas('productor', fn (Builder $q) => $q->catalogo());

        if (! empty($filters['categoria_id'])) {
            // Ids del nodo + todos sus descendientes, precalculados (sin N consultas).
            $ids = CategoriaTree::current()->idsConDescendientes((int) $filters['categoria_id']);
            $ids ? $query->whereIn('ID_Categoria', $ids) : $query->whereRaw('1 = 0');
        }

        if (! empty($filters['productor_id'])) {
            $query->where('ID_Productor', $filters['productor_id']);
        }

        if (array_key_exists('disponible', $filters) && $filters['disponible'] !== null) {
            $query->where('Disponible', $filters['disponible'] ? 1 : 0);
        }

        if (! empty($filters['etiqueta'])) {
            $query->whereJsonContains('Etiquetas', $filters['etiqueta']);
        }

        if (! empty($filters['q'])) {
            // Escape "!" para que % y _ del usuario no actúen como comodines (válido en MySQL/MariaDB y sqlite).
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']).'%';
            $query->where(function (Builder $q) use ($like) {
                $q->whereRaw("Nombre_Producto LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("Descripcion LIKE ? ESCAPE '!'", [$like]);
            });
        }

        match ($filters['sort'] ?? 'recientes') {
            'precio_asc' => $query->orderBy('Precio')->orderBy('ID_Producto'),
            'precio_desc' => $query->orderByDesc('Precio')->orderByDesc('ID_Producto'),
            default => $query->orderByDesc('Fecha_Registro')->orderByDesc('ID_Producto'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    public function getById(int $id)
    {
        return Producto::query()
            ->whereHas('productor', fn (Builder $q) => $q->catalogo())
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return Producto::create($data)->refresh();
    }

    public function update(int $id, array $data)
    {
        $producto = Producto::findOrFail($id);
        $producto->update($data);

        return $producto->refresh();
    }

    public function delete(int $id)
    {
        $producto = Producto::findOrFail($id);

        return $producto->delete();
    }
}

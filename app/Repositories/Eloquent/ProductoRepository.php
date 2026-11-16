<?php

namespace App\Repositories\Eloquent;

use App\Models\Producto;
use App\Repositories\Interfaces\ProductoRepositoryInterface;

class ProductoRepository implements ProductoRepositoryInterface
{
    public function getAll(array $filters, int $limitPerPage = 100)
    {
        $query = Producto::query();

        if (isset($filters['productor_id'])) {
            $query->where('ID_Productor', $filters['productor_id']);
        }
        if (isset($filters['disponible'])) {
            $query->where('Disponible', $filters['disponible']);
        }
        // 1. Filtro por Categoría
        if (isset($filters['idCategoria']) && ! empty($filters['idCategoria'])) {
            $query->where('ID_Categoria', $filters['idCategoria']);
        } elseif (isset($filters['categoria_id']) && ! empty($filters['categoria_id'])) {
            $query->where('ID_Categoria', $filters['categoria_id']);
        }

        // 2. Filtro por Subcategoría (Flutter envía 'idSubcategoria')
        if (isset($filters['subcategoria_id']) && ! empty($filters['subcategoria_id'])) {
            $query->where('ID_Subcategoria', $filters['subcategoria_id']);
        }

        // 3. Filtro por Productor (Para cuando entras al perfil de un negocio y quieres ver solo SUS productos)
        if (isset($filters['idProductor']) && ! empty($filters['idProductor'])) {
            $query->where('ID_Productor', $filters['idProductor']);
        } elseif (isset($filters['productor_id']) && ! empty($filters['productor_id'])) {
            $query->where('ID_Productor', $filters['productor_id']);
        }

        return $query->paginate($limitPerPage);
    }

    public function getById(int $id)
    {
        return Producto::with('productor')->findOrFail($id);
    }

    public function create(array $data)
    {
        return Producto::create($data);
    }

    public function update(int $id, array $data)
    {
        $producto = Producto::findOrFail($id);
        $producto->update($data);

        return $producto;
    }

    public function delete(int $id)
    {
        $producto = Producto::findOrFail($id);

        return $producto->delete();
    }
}

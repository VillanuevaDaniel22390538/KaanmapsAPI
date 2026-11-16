<?php

namespace App\Repositories\Eloquent;

use App\Models\Producto;
use App\Repositories\Interfaces\ProductoRepositoryInterface;

class ProductoRepository implements ProductoRepositoryInterface
{
    public function getAll(array $filters, int $perPage = 15)
    {
        $query = Producto::query();

        if (isset($filters['productor_id'])) {
            $query->where('ID_Productor', $filters['productor_id']);
        }
        if (isset($filters['disponible'])) {
            $query->where('Disponible', $filters['disponible']);
        }

        return $query->paginate($perPage);
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

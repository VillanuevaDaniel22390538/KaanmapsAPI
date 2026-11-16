<?php

namespace App\Repositories\Eloquent;

use App\Models\Productor;
use App\Repositories\Interfaces\ProductorRepositoryInterface;

class ProductorRepository implements ProductorRepositoryInterface
{
    public function getAll(array $filters, int $limitPerPage = 100)
    {
        // Cargamos las relaciones para que la respuesta sea completa
        $query = Productor::with(['usuario', 'ubicaciones', 'productos']);

        // 1. Búsqueda por texto (Nombre del negocio o descripción)
        if (isset($filters['search']) && ! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('Nombre_Negocio', 'LIKE', "%{$search}%")
                    ->orWhere('Descripcion_Negocio', 'LIKE', "%{$search}%");
            });
        }

        // 2. Filtro por Tipo de Actividad
        if (isset($filters['tipo_actividad']) && ! empty($filters['tipo_actividad'])) {
            $query->where('Tipo_Actividad', $filters['tipo_actividad']);
        }

        // 3. Filtro por Estado de Verificación
        if (isset($filters['estado_verificacion']) && ! empty($filters['estado_verificacion'])) {
            $query->where('Estado_Verificacion', $filters['estado_verificacion']);
        }

        // 4. Filtro por Municipio (A través de la relación ubicaciones)
        if (isset($filters['municipio']) && ! empty($filters['municipio'])) {
            $query->whereHas('ubicaciones', function ($q) use ($filters) {
                $q->where('Municipio', 'LIKE', "%{$filters['municipio']}%");
            });
        }

        // 5. Filtro directo por Categoría del Productor
        if (isset($filters['idCategoria']) && ! empty($filters['idCategoria'])) {
            $query->where('ID_Categoria', $filters['idCategoria']);
        }
        // Como respaldo, por si decides enviar categoria_id desde alguna otra pantalla
        elseif (isset($filters['categoria_id']) && ! empty($filters['categoria_id'])) {
            $query->where('ID_Categoria', $filters['categoria_id']);
        }

        return $query->paginate($limitPerPage);
    }

    public function getById(int $id)
    {
        return Productor::with(['usuario', 'ubicaciones', 'productos'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return Productor::create($data);
    }

    public function update(int $id, array $data)
    {
        $productor = Productor::findOrFail($id);
        $productor->update($data);

        return $productor->load(['usuario', 'ubicaciones', 'productos']);
    }

    public function delete(int $id)
    {
        $productor = Productor::findOrFail($id);

        return $productor->delete();
    }
}

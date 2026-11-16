<?php

namespace App\Repositories\Interfaces;

use App\Models\Productor;

interface ProductorRepositoryInterface
{
    public function getAll(array $filters, int $perPage = 15);
    public function getById(int $id, bool $conProductos = false);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);

    /** Reemplaza las imágenes de instalaciones por las rutas dadas (en orden). */
    public function syncImagenes(Productor $productor, array $rutas): void;
}

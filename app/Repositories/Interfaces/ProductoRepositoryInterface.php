<?php

namespace App\Repositories\Interfaces;

interface ProductoRepositoryInterface
{
    public function getAll(array $filters, int $limitPerPage);

    public function getById(int $id);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id);
}

<?php

namespace App\Services;

use App\Repositories\Interfaces\ProductoRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductoService
{
    protected $repository;

    public function __construct(ProductoRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function listarProductos(array $filters, int $limitPerPage)
    {
        return $this->repository->getAll($filters, $limitPerPage);
    }

    public function obtenerProducto(int $id)
    {
        return $this->repository->getById($id);
    }

    public function crearProducto(array $data)
    {
        // Manejo de múltiples imágenes
        if (isset($data['URLs_Imagenes']) && is_array($data['URLs_Imagenes'])) {
            $paths = [];
            foreach ($data['URLs_Imagenes'] as $image) {
                if ($image instanceof UploadedFile) {
                    $path = $image->store('productos', 'public');
                    $paths[] = Storage::disk('public')->url($path);
                }
            }
            $data['URLs_Imagenes'] = json_encode($paths);
        }

        // Convertir etiquetas a JSON
        if (isset($data['Etiquetas']) && is_array($data['Etiquetas'])) {
            $data['Etiquetas'] = json_encode($data['Etiquetas']);
        }

        return $this->repository->create($data);
    }

    public function actualizarProducto(int $id, array $data)
    {
        if (isset($data['URLs_Imagenes']) && is_array($data['URLs_Imagenes'])) {
            $paths = [];
            foreach ($data['URLs_Imagenes'] as $image) {
                if ($image instanceof UploadedFile) {
                    $path = $image->store('productos', 'public');
                    $paths[] = Storage::disk('public')->url($path);
                }
            }
            $data['URLs_Imagenes'] = json_encode($paths);
        }

        if (isset($data['Etiquetas']) && is_array($data['Etiquetas'])) {
            $data['Etiquetas'] = json_encode($data['Etiquetas']);
        }

        return $this->repository->update($id, $data);
    }

    public function eliminarProducto(int $id)
    {
        return $this->repository->delete($id);
    }
}

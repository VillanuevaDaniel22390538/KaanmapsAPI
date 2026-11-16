<?php

namespace App\Services;

use App\Repositories\Interfaces\ProductoRepositoryInterface;
use App\Support\MediaUrl;
use Illuminate\Http\UploadedFile;

class ProductoService
{
    protected $repository;

    public function __construct(ProductoRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function listarProductos(array $filters, int $perPage = 15)
    {
        return $this->repository->getAll($filters, $perPage);
    }

    public function obtenerProducto(int $id)
    {
        return $this->repository->getById($id);
    }

    public function crearProducto(array $data)
    {
        return $this->repository->create($this->prepararDatos($data));
    }

    public function actualizarProducto(int $id, array $data)
    {
        return $this->repository->update($id, $this->prepararDatos($data));
    }

    public function eliminarProducto(int $id)
    {
        return $this->repository->delete($id);
    }

    /**
     * Sube las imágenes y deja en BD SOLO rutas relativas al disco (nunca URLs
     * absolutas). El modelo castea URLs_Imagenes / Etiquetas a array, así que
     * aquí NO se hace json_encode.
     */
    private function prepararDatos(array $data): array
    {
        if (isset($data['URLs_Imagenes']) && is_array($data['URLs_Imagenes'])) {
            $rutas = [];
            foreach ($data['URLs_Imagenes'] as $image) {
                if ($image instanceof UploadedFile) {
                    $rutas[] = $image->store('productos', MediaUrl::disk());
                }
            }
            $data['URLs_Imagenes'] = $rutas;
        }

        return $data;
    }
}

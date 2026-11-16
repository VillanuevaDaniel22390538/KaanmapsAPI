<?php

namespace App\Services;

use App\Repositories\Interfaces\ProductorRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProductorService
{
    protected $repository;

    public function __construct(ProductorRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function listarProductores(array $filters, int $limitPerPage)
    {
        return $this->repository->getAll($filters, $limitPerPage);
    }

    public function obtenerProductor(int $id)
    {
        return $this->repository->getById($id);
    }

    public function crearProductor(array $data)
    {
        // Manejo de la subida de imagen (Logo) con optimización
        if (isset($data['Logo_URL']) && $data['Logo_URL'] instanceof UploadedFile) {
            $data['Logo_URL'] = $this->procesarImagen($data['Logo_URL'], 'logos');
        }

        return $this->repository->create($data);
    }

    public function actualizarProductor(int $id, array $data)
    {
        if (isset($data['Logo_URL']) && $data['Logo_URL'] instanceof UploadedFile) {
            $data['Logo_URL'] = $this->procesarImagen($data['Logo_URL'], 'logos');
        }

        return $this->repository->update($id, $data);
    }

    public function eliminarProductor(int $id)
    {
        return $this->repository->delete($id);
    }

    /**
     * Método privado para procesar y optimizar imágenes.
     */
    private function procesarImagen($file, $carpeta)
    {
        // Inicializar el gestor de imágenes (usando el driver GD)
        $manager = new ImageManager(new Driver);

        // Leer la imagen subida
        $image = $manager->read($file);

        // Redimensionar: Si es más ancha de 800px, la escala proporcionalmente
        $image->scale(width: 800);

        // Generar un nombre único y la ruta destino (Ej: storage_global/app/public/logos/abc123.jpg)
        $filename = uniqid().'.'.$file->getClientOriginalExtension();
        $path = $carpeta.'/'.$filename;

        // Guardar la imagen optimizada en el disco 'public' (que apunta a storage_global)
        Storage::disk('public')->put($path, (string) $image->toJpeg(80)); // Calidad 80%

        // Retornar la URL pública para guardarla en la Base de Datos
        return Storage::disk('public')->url($path);
    }
}

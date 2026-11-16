<?php

namespace App\Services;

use App\Repositories\Interfaces\ProductorRepositoryInterface;
use App\Support\MediaUrl;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductorService
{
    protected $repository;

    public function __construct(ProductorRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function listarProductores(array $filters, int $perPage = 15)
    {
        return $this->repository->getAll($filters, $perPage);
    }

    public function obtenerProductor(int $id)
    {
        return $this->repository->getById($id);
    }

    public function crearProductor(array $data)
    {
        // Manejo de la subida de imagen (Logo) con optimización
        if (isset($data['Logo_URL']) && $data['Logo_URL'] instanceof \Illuminate\Http\UploadedFile) {
            $data['Logo_URL'] = $this->procesarImagen($data['Logo_URL'], 'logos');
        }

        return $this->repository->create($data);
    }

    public function actualizarProductor(int $id, array $data)
    {
        if (isset($data['Logo_URL']) && $data['Logo_URL'] instanceof \Illuminate\Http\UploadedFile) {
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
        $manager = new ImageManager(new Driver());
        
        // Leer la imagen subida
        $image = $manager->read($file);
        
        // Redimensionar: Si es más ancha de 800px, la escala proporcionalmente
        $image->scaleDown(width: 800);
        
        // Generar un nombre único y la ruta destino (Ej: storage_global/app/public/logos/abc123.jpg)
        $filename = uniqid() . '.jpg'; // el contenido se re-codifica a JPEG
        $path = $carpeta . '/' . $filename;
        
        // Guardar la imagen optimizada en el disco configurado (MEDIA_DISK)
        Storage::disk(MediaUrl::disk())->put($path, (string) $image->toJpeg(80)); // Calidad 80%
        
        // En BD va SOLO la ruta relativa; la URL absoluta se construye al responder (MediaUrl)
        return $path;
    }
}
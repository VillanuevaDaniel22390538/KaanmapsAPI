<?php

namespace App\Services;

use App\Models\Productor;
use App\Repositories\Interfaces\ProductorRepositoryInterface;
use App\Support\MediaUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProductorService
{
    public function __construct(protected ProductorRepositoryInterface $repository)
    {
    }

    public function listarProductores(array $filters, int $perPage = 15)
    {
        return $this->repository->getAll($filters, $perPage);
    }

    public function obtenerProductor(int $id, bool $conProductos = false)
    {
        return $this->repository->getById($id, $conProductos);
    }

    public function crearProductor(array $data)
    {
        [$data, $imagenes] = $this->prepararDatos($data);

        $productor = $this->repository->create($data);

        if ($imagenes !== null) {
            $this->repository->syncImagenes($productor, $imagenes);
        }

        return $productor;
    }

    public function actualizarProductor(int $id, array $data)
    {
        [$data, $imagenes] = $this->prepararDatos($data);

        $productor = $this->repository->update($id, $data);

        if ($imagenes !== null) {
            $this->repository->syncImagenes($productor, $imagenes);
        }

        return $productor;
    }

    public function eliminarProductor(int $id)
    {
        return $this->repository->delete($id);
    }

    /**
     * Sube logo e imágenes de instalaciones (solo rutas relativas a BD).
     *
     * @return array{0: array, 1: ?list<string>} [datos para el modelo, rutas de instalaciones o null si no vinieron]
     */
    private function prepararDatos(array $data): array
    {
        if (isset($data['Logo_URL']) && $data['Logo_URL'] instanceof UploadedFile) {
            $data['Logo_URL'] = $this->procesarImagen($data['Logo_URL'], 'logos');
        }

        $imagenes = null;
        if (array_key_exists('imagenes_instalaciones', $data)) {
            $imagenes = [];
            foreach ((array) $data['imagenes_instalaciones'] as $archivo) {
                if ($archivo instanceof UploadedFile) {
                    $imagenes[] = $this->procesarImagen($archivo, 'instalaciones');
                }
            }
            unset($data['imagenes_instalaciones']);
        }

        return [$data, $imagenes];
    }

    /**
     * Optimiza la imagen (máx. 800 px de ancho, JPEG calidad 80) y la guarda en el
     * disco configurado (MEDIA_DISK). Devuelve SOLO la ruta relativa.
     */
    private function procesarImagen(UploadedFile $file, string $carpeta): string
    {
        $image = (new ImageManager(new Driver()))->read($file);
        $image->scaleDown(width: 800);

        $path = $carpeta.'/'.uniqid().'.jpg'; // el contenido se re-codifica a JPEG

        Storage::disk(MediaUrl::disk())->put($path, (string) $image->toJpeg(80));

        return $path;
    }
}

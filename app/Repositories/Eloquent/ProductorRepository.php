<?php

namespace App\Repositories\Eloquent;

use App\Models\Productor;
use App\Repositories\Interfaces\ProductorRepositoryInterface;
use App\Support\CategoriaTree;
use App\Support\Like;
use Illuminate\Database\Eloquent\Builder;

class ProductorRepository implements ProductorRepositoryInterface
{
    /** Relaciones que usa ProductorResource (evita N+1). */
    private const RELACIONES = ['usuario', 'ubicaciones', 'imagenes'];

    public function getAll(array $filters, int $perPage = 15)
    {
        $query = Productor::query()
            ->with(self::RELACIONES)
            ->catalogo();

        if (! empty($filters['q'])) {
            $like = Like::contiene($filters['q']);
            $query->where(function (Builder $q) use ($like) {
                $q->whereRaw("Nombre_Negocio LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("Descripcion_Negocio LIKE ? ESCAPE '!'", [$like]);
            });
        }

        if (! empty($filters['tipo_actividad'])) {
            $query->where('Tipo_Actividad', $filters['tipo_actividad']);
        }

        if (! empty($filters['estado_verificacion'])) {
            $query->where('Estado_Verificacion', $filters['estado_verificacion']);
        }

        if (! empty($filters['municipio'])) {
            $like = Like::contiene($filters['municipio']);
            $query->whereHas('ubicaciones', fn (Builder $q) => $q->whereRaw("Municipio LIKE ? ESCAPE '!'", [$like]));
        }

        if (! empty($filters['categoria_id'])) {
            // Coincide si su categoría principal está en el subárbol O tiene productos en él.
            $ids = CategoriaTree::current()->idsConDescendientes((int) $filters['categoria_id']);

            if ($ids) {
                $query->where(function (Builder $q) use ($ids) {
                    $q->whereIn('categoria_id', $ids)
                        ->orWhereHas('productos', fn (Builder $p) => $p->whereIn('ID_Categoria', $ids));
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query->orderBy('ID_Productor')->paginate($perPage)->withQueryString();
    }

    public function getById(int $id, bool $conProductos = false)
    {
        $relaciones = self::RELACIONES;

        if ($conProductos) {
            $relaciones['productos'] = fn ($q) => $q->orderByDesc('Fecha_Registro')->orderByDesc('ID_Producto');
        }

        return Productor::query()
            ->with($relaciones)
            ->catalogo()
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return Productor::create($data)->refresh()->load(self::RELACIONES);
    }

    public function update(int $id, array $data)
    {
        $productor = Productor::findOrFail($id);
        $productor->update($data);

        return $productor->refresh()->load(self::RELACIONES);
    }

    public function delete(int $id)
    {
        $productor = Productor::findOrFail($id);

        return $productor->delete();
    }

    public function syncImagenes(Productor $productor, array $rutas): void
    {
        $productor->imagenes()->delete();

        foreach (array_values($rutas) as $orden => $ruta) {
            $productor->imagenes()->create(['ruta' => $ruta, 'orden' => $orden]);
        }

        $productor->unsetRelation('imagenes');
        $productor->load('imagenes');
    }
}

<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Producto */
class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ID_Producto,
            'productor_id' => $this->ID_Productor,
            'categoria_id' => $this->ID_Categoria,
            'nombre' => $this->Nombre_Producto,
            'descripcion' => $this->Descripcion,
            // Decimal como string ("3000.00"), moneda ISO 4217.
            'precio' => $this->Precio !== null ? number_format((float) $this->Precio, 2, '.', '') : null,
            'moneda' => $this->Moneda ? strtoupper($this->Moneda) : null,
            'tipo_unidad' => $this->Tipo_Unidad,
            'inventario' => $this->Cantidad_Inventario,
            'disponible' => (bool) $this->Disponible,
            'imagenes' => MediaUrl::resolveMany($this->comoLista($this->URLs_Imagenes)),
            'etiquetas' => array_values($this->comoLista($this->Etiquetas)),
            'fecha_registro' => $this->Fecha_Registro,
        ];
    }

    /** Tolera datos viejos guardados doble codificados (string JSON dentro del JSON). */
    private function comoLista(mixed $valor): array
    {
        if (is_string($valor)) {
            $valor = json_decode($valor, true);
        }

        return is_array($valor) ? $valor : [];
    }
}

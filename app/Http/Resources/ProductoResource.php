<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->ID_Producto,
            'productor_id' => $this->ID_Productor,
            'categoria_id' => $this->ID_Categoria,
            'nombre' => $this->Nombre_Producto,
            'descripcion' => $this->Descripcion,
            'precio' => $this->Precio,
            'moneda' => $this->Moneda,
            'tipo_unidad' => $this->Tipo_Unidad,
            'inventario' => $this->Cantidad_Inventario,
            'disponible' => (bool) $this->Disponible,
            'imagenes' => is_array($this->URLs_Imagenes) ? $this->URLs_Imagenes : (json_decode($this->URLs_Imagenes ?? '[]', true) ?: []),
            'etiquetas' => is_array($this->Etiquetas) ? $this->Etiquetas : (json_decode($this->Etiquetas ?? '[]', true) ?: []),
            'fecha_registro' => $this->Fecha_Registro,
            'categoria_id' => $this->ID_Categoria ?? $this->categoria_id,
            'subcategoria_id' => $this->subcategoria_id, // <-- Nuevo
            'idTipo' => $this->tipos_id ?? [], // <-- Nuevo
        ];
    }
}

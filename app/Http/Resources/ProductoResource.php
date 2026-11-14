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
            'imagenes' => $this->URLs_Imagenes ? json_decode($this->URLs_Imagenes, true) : [],
            'etiquetas' => $this->Etiquetas ? json_decode($this->Etiquetas, true) : [],
            'fecha_registro' => $this->Fecha_Registro,
        ];
    }
}
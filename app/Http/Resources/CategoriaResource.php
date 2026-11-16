<?php

namespace App\Http\Resources;

use App\Support\CategoriaTree;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CategoriaProducto */
class CategoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ID_Categoria,
            'nombre' => $this->Nombre_Categoria,
            'slug' => $this->slug,
            'padre_id' => $this->ID_Categoria_Padre,
            'nivel' => CategoriaTree::current()->nivel($this->ID_Categoria),
            'descripcion' => $this->Descripcion,
            'color_hex' => $this->Color_Hex,
            'icono_url' => $this->URL_Icono,
            'orden' => $this->orden,
        ];
    }
}

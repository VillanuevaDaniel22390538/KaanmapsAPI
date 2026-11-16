<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Productor */
class ProductorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ID_Productor,
            'nombre_negocio' => $this->Nombre_Negocio,
            'descripcion' => $this->Descripcion_Negocio,
            'tipo_actividad' => $this->Tipo_Actividad,
            'slogan' => $this->Slogan,
            'logo' => MediaUrl::resolve($this->Logo_URL),
            'estado_verificacion' => $this->Estado_Verificacion,
            'usuario' => [
                'nombre' => $this->usuario?->Nombre_Usuario,
                'telefono' => $this->usuario?->Telefono,
                'avatar' => MediaUrl::resolve($this->usuario?->Avatar_URL),
            ],
            'ubicaciones' => UbicacionResource::collection($this->whenLoaded('ubicaciones')),
            'productos' => ProductoResource::collection($this->whenLoaded('productos')),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->ID_Productor,
            'nombre_negocio' => $this->Nombre_Negocio,
            'descripcion' => $this->Descripcion_Negocio,
            'tipo_actividad' => $this->Tipo_Actividad,
            'slogan' => $this->Slogan,
            'logo' => $this->Logo_URL,
            'estado_verificacion' => $this->Estado_Verificacion,
            'usuario' => [
                'nombre' => $this->usuario->Nombre_Usuario ?? null,
                'telefono' => $this->usuario->Telefono ?? null,
                'avatar' => $this->usuario->Avatar_URL ?? null,
            ],
            'ubicaciones' => $this->whenLoaded('ubicaciones'),
            'productos' => $this->whenLoaded('productos'),
        ];
    }
}
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
            'categoria_id' => $this->ID_Categoria,
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
            'email' => $this->email_contacto, // <-- Nuevo
            'facebook' => $this->facebook, // <-- Nuevo
            'instagram' => $this->instagram, // <-- Nuevo
            'hora_apertura' => $this->hora_apertura, // <-- Nuevo
            'hora_cierre' => $this->hora_cierre, // <-- Nuevo
            'imagenes_instalaciones' => $this->Imagenes_Instalaciones ?? [], // <-- Nuevo
        ];
    }
}

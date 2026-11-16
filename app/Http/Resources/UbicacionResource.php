<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\UbicacionProductor */
class UbicacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ID_Ubicacion,
            'productor_id' => $this->ID_Productor,
            'latitud' => $this->Latitud !== null ? (float) $this->Latitud : null,
            'longitud' => $this->Longitud !== null ? (float) $this->Longitud : null,
            'direccion' => $this->Direccion,
            'localidad' => $this->Localidad,
            'municipio' => $this->Municipio,
            'estado' => $this->Estado,
            'pais' => $this->Pais,
            'precision_geolocalizacion' => $this->Precision_Geolocalizacion,
            'es_principal' => (bool) $this->Es_Principal,
        ];
    }
}

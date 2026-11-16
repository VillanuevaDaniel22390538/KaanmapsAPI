<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AcceptsSnakeCaseAliases;
use Illuminate\Foundation\Http\FormRequest;

class StoreUbicacionRequest extends FormRequest
{
    use AcceptsSnakeCaseAliases;

    public function authorize(): bool
    {
        return true;
    }

    protected function inputAliases(): array
    {
        return [
            'productor_id' => 'ID_Productor',
            'latitud' => 'Latitud',
            'longitud' => 'Longitud',
            'direccion' => 'Direccion',
            'localidad' => 'Localidad',
            'municipio' => 'Municipio',
            'estado' => 'Estado',
            'pais' => 'Pais',
            'precision_geolocalizacion' => 'Precision_Geolocalizacion',
            'es_principal' => 'Es_Principal',
        ];
    }

    public function rules(): array
    {
        return [
            'ID_Productor' => 'required|exists:productores,ID_Productor',
            'Latitud' => 'required|numeric|between:-90,90',
            'Longitud' => 'required|numeric|between:-180,180',
            'Direccion' => 'nullable|string|max:255',
            'Localidad' => 'nullable|string|max:100',
            'Municipio' => 'nullable|string|max:100',
            'Estado' => 'nullable|string|max:50',
            'Pais' => 'nullable|string|max:50',
            'Precision_Geolocalizacion' => 'nullable|string|max:20',
            'Es_Principal' => 'nullable|boolean',
        ];
    }
}

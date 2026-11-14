<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductorRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array
    {
        return [
            'Nombre_Negocio' => 'sometimes|required|string|max:100',
            'Descripcion_Negocio' => 'nullable|string',
            'Tipo_Actividad' => 'sometimes|required|in:Agricultura,Ganadería,Apicultura,Pesca,Artesanías,Turismo_Rural',
            'Logo_URL' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'Estado_Verificacion' => 'nullable|in:Pendiente,Verificado,Rechazado',
            'Estado' => 'nullable|in:Activo,Inactivo,En_Revision',
            'Slogan' => 'nullable|string|max:200',
        ];
    }
}
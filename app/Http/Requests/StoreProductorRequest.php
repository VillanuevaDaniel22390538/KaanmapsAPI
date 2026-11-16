<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ID_Usuario' => 'required|exists:usuarios,ID_Usuario',
            'Nombre_Negocio' => 'required|string|max:100',
            'ID_Categoria' => 'required|exists:categorias,ID_Categoria',
            'Tipo_Actividad' => 'required|in:Agricultura,Ganadería,Apicultura,Pesca,Artesanías,Turismo_Rural',
            'Logo_URL' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Validación de archivos
        ];
    }
}

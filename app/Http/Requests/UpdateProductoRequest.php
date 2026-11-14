<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'ID_Categoria' => 'sometimes|required|exists:categorias_productos,ID_Categoria',
            'Nombre_Producto' => 'sometimes|required|string|max:100',
            'Descripcion' => 'nullable|string',
            'Precio' => 'nullable|numeric|min:0',
            'Cantidad_Inventario' => 'nullable|integer|min:0',
            'Disponible' => 'nullable|boolean',
            'URLs_Imagenes' => 'nullable|array',
            'URLs_Imagenes.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'Etiquetas' => 'nullable|array',
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ID_Productor' => 'required|exists:productores,ID_Productor',
            'ID_Categoria' => 'required|exists:categorias_productos,ID_Categoria',
            'ID_Subcategoria' => 'nullable|exists:subcategorias,ID_Subcategoria',
            'ID_Subtipo' => 'nullable|exists:subtipos,ID_Subtipo',
            'Nombre_Producto' => 'required|string|max:100',
            'Descripcion' => 'nullable|string',
            'Precio' => 'nullable|numeric|min:0',
            'Moneda' => 'nullable|string|max:3',
            'Tipo_Unidad' => 'nullable|string|max:20',
            'Cantidad_Inventario' => 'nullable|integer|min:0',
            'Disponible' => 'nullable|boolean',
            'URLs_Imagenes' => 'nullable|array',
            'URLs_Imagenes.*' => 'image|mimes:jpeg,png,jpg|max:2048', // Arreglo de imágenes
            'Etiquetas' => 'nullable|array',
            'Etiquetas.*' => 'string|max:50',
        ];
    }
}

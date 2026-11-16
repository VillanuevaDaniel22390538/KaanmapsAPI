<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AcceptsSnakeCaseAliases;
use App\Models\Producto;
use App\Rules\CategoriaAsignable;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoRequest extends FormRequest
{
    use AcceptsSnakeCaseAliases {
        prepareForValidation as protected mapearAliases;
    }

    public function authorize(): bool
    {
        $producto = Producto::find($this->route('id'));

        // Si no existe, deja pasar para que el controlador responda 404.
        return $producto === null || ($this->user()?->can('update', $producto) ?? false);
    }

    protected function inputAliases(): array
    {
        return [
            'categoria_id' => 'ID_Categoria',
            'nombre' => 'Nombre_Producto',
            'descripcion' => 'Descripcion',
            'precio' => 'Precio',
            'moneda' => 'Moneda',
            'tipo_unidad' => 'Tipo_Unidad',
            'inventario' => 'Cantidad_Inventario',
            'disponible' => 'Disponible',
            'imagenes' => 'URLs_Imagenes',
            'etiquetas' => 'Etiquetas',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mapearAliases();

        if (is_string($this->input('Moneda'))) {
            $this->merge(['Moneda' => strtoupper(trim($this->input('Moneda')))]);
        }
    }

    public function rules(): array
    {
        return [
            'ID_Categoria' => ['sometimes', 'required', new CategoriaAsignable],
            'Nombre_Producto' => 'sometimes|required|string|max:100',
            'Descripcion' => 'nullable|string',
            'Precio' => 'nullable|numeric|min:0|max:99999999.99',
            'Moneda' => 'nullable|string|size:3|alpha',
            'Tipo_Unidad' => 'nullable|string|max:20',
            'Cantidad_Inventario' => 'nullable|integer|min:0',
            'Disponible' => 'nullable|boolean',
            'URLs_Imagenes' => 'nullable|array|max:10',
            'URLs_Imagenes.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'Etiquetas' => 'nullable|array|max:20',
            'Etiquetas.*' => 'string|max:50',
        ];
    }
}

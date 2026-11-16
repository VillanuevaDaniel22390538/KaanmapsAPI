<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AcceptsSnakeCaseAliases;
use App\Models\Producto;
use App\Rules\CategoriaAsignable;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    use AcceptsSnakeCaseAliases {
        prepareForValidation as protected mapearAliases;
    }

    public function authorize(): bool
    {
        // Solo el dueño del productor (o el personal de gobierno) puede crear productos.
        return $this->user()?->can('create', [Producto::class, (int) $this->input('ID_Productor')]) ?? false;
    }

    protected function inputAliases(): array
    {
        return [
            'productor_id' => 'ID_Productor',
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
            'ID_Productor' => 'required|exists:productores,ID_Productor',
            'ID_Categoria' => ['required', new CategoriaAsignable],
            'Nombre_Producto' => 'required|string|max:100',
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

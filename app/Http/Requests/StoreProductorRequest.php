<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AcceptsSnakeCaseAliases;
use App\Models\Productor;
use App\Rules\CategoriaAsignable;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductorRequest extends FormRequest
{
    use AcceptsSnakeCaseAliases;

    public const TIPOS_ACTIVIDAD = 'Agricultura,Ganadería,Apicultura,Pesca,Artesanías,Turismo_Rural';

    public function authorize(): bool
    {
        // Cada usuario crea su propio negocio; el personal de gobierno puede crear para otros.
        return $this->user()?->can('create', [Productor::class, (int) $this->input('ID_Usuario')]) ?? false;
    }

    protected function inputAliases(): array
    {
        return [
            'usuario_id' => 'ID_Usuario',
            'nombre_negocio' => 'Nombre_Negocio',
            'descripcion' => 'Descripcion_Negocio',
            'tipo_actividad' => 'Tipo_Actividad',
            'logo' => 'Logo_URL',
            'slogan' => 'Slogan',
            'categoria_principal_id' => 'categoria_id',
            'email' => 'email_contacto',
        ];
    }

    public function rules(): array
    {
        return [
            'ID_Usuario' => 'required|exists:usuarios,ID_Usuario',
            'Nombre_Negocio' => 'required|string|max:100',
            'Descripcion_Negocio' => 'nullable|string',
            // Deprecado: ya no es obligatorio si se envía la categoría principal.
            'Tipo_Actividad' => 'required_without:categoria_id|nullable|in:'.self::TIPOS_ACTIVIDAD,
            'categoria_id' => ['nullable', 'required_without:Tipo_Actividad', new CategoriaAsignable(1)],
            'Logo_URL' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'Slogan' => 'nullable|string|max:200',
            'email_contacto' => 'nullable|email|max:255',
            'facebook' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'telefono_contacto' => 'nullable|string|max:20',
            'horario' => 'nullable|string|max:150',
            'imagenes_instalaciones' => 'nullable|array|max:10',
            'imagenes_instalaciones.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}

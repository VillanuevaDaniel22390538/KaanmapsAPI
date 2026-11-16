<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AcceptsSnakeCaseAliases;
use App\Models\Productor;
use App\Rules\CategoriaAsignable;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductorRequest extends FormRequest
{
    use AcceptsSnakeCaseAliases {
        prepareForValidation as protected mapearAliases;
    }

    public function authorize(): bool
    {
        $productor = Productor::find($this->route('id'));

        // Si no existe, deja pasar para que el controlador responda 404.
        return $productor === null || ($this->user()?->can('update', $productor) ?? false);
    }

    protected function inputAliases(): array
    {
        return [
            'nombre_negocio' => 'Nombre_Negocio',
            'descripcion' => 'Descripcion_Negocio',
            'tipo_actividad' => 'Tipo_Actividad',
            'logo' => 'Logo_URL',
            'slogan' => 'Slogan',
            'estado_verificacion' => 'Estado_Verificacion',
            'estado' => 'Estado',
            'categoria_principal_id' => 'categoria_id',
            'email' => 'email_contacto',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mapearAliases();

        // La verificación y el estado los gestiona el personal de gobierno: si un
        // productor los envía se ignoran (no puede auto-verificarse).
        if (! $this->esPersonal()) {
            $this->getInputSource()->remove('Estado_Verificacion');
            $this->getInputSource()->remove('Estado');
        }
    }

    private function esPersonal(): bool
    {
        return in_array($this->user()?->Tipo_Usuario, ['SuperAdmin', 'Admin_Gobierno', 'Capturista_Gobierno'], true);
    }

    public function rules(): array
    {
        return [
            'Nombre_Negocio' => 'sometimes|required|string|max:100',
            'Descripcion_Negocio' => 'nullable|string',
            'Tipo_Actividad' => 'sometimes|required|in:'.StoreProductorRequest::TIPOS_ACTIVIDAD,
            'categoria_id' => ['nullable', new CategoriaAsignable(1)],
            'Logo_URL' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'Estado_Verificacion' => 'nullable|in:Pendiente,Verificado,Rechazado',
            'Estado' => 'nullable|in:Activo,Inactivo,En_Revision',
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

<?php

namespace App\Http\Resources;

use App\Support\CategoriaTree;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Productor */
class ProductorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ID_Productor,
            'nombre_negocio' => $this->Nombre_Negocio,
            'descripcion' => $this->Descripcion_Negocio,
            // DEPRECADO: usar categoria_principal_id. Se mantiene por compatibilidad.
            'tipo_actividad' => $this->Tipo_Actividad ?? $this->actividadDerivada(),
            'categoria_principal_id' => $this->categoria_id !== null ? (int) $this->categoria_id : null,
            'slogan' => $this->Slogan,
            'logo' => MediaUrl::resolve($this->Logo_URL),
            'estado_verificacion' => $this->Estado_Verificacion,
            // Contacto PÚBLICO del negocio (no los datos personales de la cuenta).
            'telefono_contacto' => $this->telefono_contacto,
            'email' => $this->email_contacto,
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'horario' => $this->horario ?? $this->horarioDerivado(),
            'imagenes_instalaciones' => $this->whenLoaded(
                'imagenes',
                fn () => $this->imagenes->map(fn ($i) => MediaUrl::resolve($i->ruta))->filter()->values()->all(),
                []
            ),
            'fecha_registro' => $this->Fecha_Registro,
            // De la cuenta solo se expone el avatar; ni username ni teléfono personal.
            'usuario' => [
                'avatar' => MediaUrl::resolve($this->usuario?->Avatar_URL),
            ],
            'ubicaciones' => UbicacionResource::collection($this->whenLoaded('ubicaciones')),
            // Solo en el detalle con ?include=productos
            'productos' => ProductoResource::collection($this->whenLoaded('productos')),
        ];
    }

    private function actividadDerivada(): ?string
    {
        if ($this->categoria_id === null) {
            return null;
        }

        $tree = CategoriaTree::current();
        $raiz = $tree->enNivel((int) $this->categoria_id, 1);

        return $raiz ? $tree->nombre($raiz) : null;
    }

    /** Si no hay texto libre pero sí hora_apertura/hora_cierre (columnas deprecadas), arma "HH:MM-HH:MM". */
    private function horarioDerivado(): ?string
    {
        if ($this->hora_apertura && $this->hora_cierre) {
            return substr($this->hora_apertura, 0, 5).'-'.substr($this->hora_cierre, 0, 5);
        }

        return null;
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListProductoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:100',
            'search' => 'nullable|string|max:100', // alias antiguo de q
            'categoria_id' => 'nullable|integer|min:1',
            'tipo_actividad' => 'nullable|string|max:30', // deprecado, se mantiene por compatibilidad
            'estado_verificacion' => 'nullable|in:Pendiente,Verificado,Rechazado',
            'municipio' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
        ];
    }

    public function filtros(): array
    {
        $v = $this->validated();

        return [
            'q' => trim((string) ($v['q'] ?? $v['search'] ?? '')) ?: null,
            'categoria_id' => isset($v['categoria_id']) ? (int) $v['categoria_id'] : null,
            'tipo_actividad' => $v['tipo_actividad'] ?? null,
            'estado_verificacion' => $v['estado_verificacion'] ?? null,
            'municipio' => $v['municipio'] ?? null,
        ];
    }

    /** per_page: por defecto 15, tope 100 (se recorta en vez de rechazar). */
    public function perPage(): int
    {
        $max = (int) config('catalog.per_page_max', 100);
        $default = (int) config('catalog.per_page_default', 15);

        return max(1, min((int) ($this->validated()['per_page'] ?? $default), $max));
    }
}

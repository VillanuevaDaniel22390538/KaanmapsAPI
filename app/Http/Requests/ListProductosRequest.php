<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListProductosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // La query string trae "true"/"false" como texto.
        if ($this->has('disponible') && is_string($this->input('disponible'))) {
            $bool = filter_var($this->input('disponible'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool !== null) {
                $this->merge(['disponible' => $bool]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'categoria_id' => 'nullable|integer|min:1',
            'productor_id' => 'nullable|integer|min:1',
            'disponible' => 'nullable|boolean',
            'etiqueta' => 'nullable|string|max:50',
            'q' => 'nullable|string|max:100',
            'sort' => 'nullable|in:recientes,precio_asc,precio_desc',
            'per_page' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
        ];
    }

    /** Filtros ya normalizados para el repositorio. */
    public function filtros(): array
    {
        $v = $this->validated();

        return [
            'categoria_id' => isset($v['categoria_id']) ? (int) $v['categoria_id'] : null,
            'productor_id' => isset($v['productor_id']) ? (int) $v['productor_id'] : null,
            'disponible' => array_key_exists('disponible', $v) && $v['disponible'] !== null ? (bool) $v['disponible'] : null,
            'etiqueta' => $v['etiqueta'] ?? null,
            'q' => isset($v['q']) ? trim($v['q']) : null,
            'sort' => $v['sort'] ?? 'recientes',
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

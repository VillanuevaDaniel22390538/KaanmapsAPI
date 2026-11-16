<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowProductorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'include' => 'nullable|in:productos',
        ];
    }

    public function incluirProductos(): bool
    {
        return ($this->validated()['include'] ?? null) === 'productos';
    }
}

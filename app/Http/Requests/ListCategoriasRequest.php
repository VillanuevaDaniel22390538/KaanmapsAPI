<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListCategoriasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nivel' => 'nullable|integer|in:1,2,3',
            'padre_id' => 'nullable|integer|min:1',
        ];
    }
}

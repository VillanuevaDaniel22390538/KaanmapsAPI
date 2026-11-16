<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AcceptsSnakeCaseAliases;
use Illuminate\Foundation\Http\FormRequest;

class ListUbicacionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productor_id' => 'required|integer|exists:productores,ID_Productor',
        ];
    }
}

<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class ReorderImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order'   => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'order.required'   => 'L\'ordre des images est requis.',
            'order.array'      => 'L\'ordre doit être un tableau d\'identifiants.',
            'order.*.integer'  => 'Chaque identifiant doit être un entier.',
        ];
    }
}

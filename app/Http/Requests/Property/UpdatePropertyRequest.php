<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'            => ['sometimes', 'string', 'min:10', 'max:200'],
            'description'      => ['sometimes', 'string', 'min:50'],
            'type'             => ['sometimes', 'in:apartment,house,studio,villa,commercial,land'],
            'transaction_type' => ['sometimes', 'in:rent,sale'],
            'price'            => ['sometimes', 'integer', 'min:1'],
            'surface'          => ['nullable', 'integer', 'min:1', 'max:9999'],
            'rooms'            => ['nullable', 'integer', 'min:0', 'max:99'],
            'bathrooms'        => ['nullable', 'integer', 'min:0', 'max:99'],
            'address'          => ['nullable', 'string', 'max:255'],
            'city'             => ['sometimes', 'string', 'max:100'],
            'district'         => ['nullable', 'string', 'max:100'],
            'latitude'         => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'        => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.min'                 => 'Le titre doit comporter au moins 10 caractères.',
            'description.min'           => 'La description doit comporter au moins 50 caractères.',
            'type.in'                   => 'Le type de bien est invalide.',
            'transaction_type.in'       => 'Le type de transaction doit être "rent" ou "sale".',
            'price.integer'             => 'Le prix doit être un entier.',
            'price.min'                 => 'Le prix doit être supérieur à 0.',
        ];
    }
}

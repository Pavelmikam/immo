<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'min:10', 'max:200'],
            'description'      => ['required', 'string', 'min:50'],
            'type'             => ['required', 'in:apartment,house,studio,villa,commercial,land'],
            'transaction_type' => ['required', 'in:rent,sale'],
            'price'            => ['required', 'integer', 'min:1'],
            'surface'          => ['nullable', 'integer', 'min:1', 'max:9999'],
            'rooms'            => ['nullable', 'integer', 'min:0', 'max:99'],
            'bathrooms'        => ['nullable', 'integer', 'min:0', 'max:99'],
            'address'          => ['nullable', 'string', 'max:255'],
            'city'             => ['required', 'string', 'max:100'],
            'district'         => ['nullable', 'string', 'max:100'],
            'latitude'         => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'        => ['nullable', 'numeric', 'between:-180,180'],
            'amenities'        => ['nullable', 'array'],
            'amenities.*'      => ['string', 'max:100'],
            'available_from'   => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'            => 'Le titre est obligatoire.',
            'title.min'                 => 'Le titre doit comporter au moins 10 caractères.',
            'description.required'      => 'La description est obligatoire.',
            'description.min'           => 'La description doit comporter au moins 50 caractères.',
            'type.required'             => 'Le type de bien est obligatoire.',
            'type.in'                   => 'Le type de bien est invalide.',
            'transaction_type.required' => 'Le type de transaction est obligatoire.',
            'transaction_type.in'       => 'Le type de transaction doit être "rent" ou "sale".',
            'price.required'            => 'Le prix est obligatoire.',
            'price.integer'             => 'Le prix doit être un entier.',
            'price.min'                 => 'Le prix doit être supérieur à 0.',
            'city.required'             => 'La ville est obligatoire.',
        ];
    }
}

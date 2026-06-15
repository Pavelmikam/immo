<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('neighborhood') && !$this->has('district')) {
            $this->merge(['district' => $this->neighborhood]);
        }
    }

    public function rules(): array
    {
        $validTypes = implode(',', [
            'apartment', 'house', 'studio', 'villa', 'commercial', 'land',
            'chambre_simple', 'appartement', 'maison', 'mini_cite',
            'local_commercial', 'chambre_etudiante', 'logement_meuble',
        ]);

        return [
            'title'              => ['sometimes', 'string', 'min:10', 'max:200'],
            'description'        => ['sometimes', 'string', 'min:50'],
            'type'               => ['sometimes', "in:{$validTypes}"],
            'transaction_type'   => ['sometimes', 'in:rent,sale'],
            'price'              => ['sometimes', 'integer', 'min:1'],
            'deposit_amount'     => ['nullable', 'integer', 'min:0'],
            'min_rental_months'  => ['nullable', 'integer', 'min:1', 'max:24'],
            'surface'            => ['nullable', 'integer', 'min:1', 'max:9999'],
            'rooms'              => ['nullable', 'integer', 'min:0', 'max:99'],
            'bathrooms'          => ['nullable', 'integer', 'min:0', 'max:99'],
            'floor'              => ['nullable', 'integer', 'min:0', 'max:99'],
            'address'            => ['nullable', 'string', 'max:255'],
            'city'               => ['sometimes', 'string', 'max:100'],
            'district'           => ['nullable', 'string', 'max:100'],
            'latitude'           => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'          => ['nullable', 'numeric', 'between:-180,180'],
            'amenities'          => ['nullable', 'array'],
            'amenities.*'        => ['string', 'max:100'],
            'charges_included'   => ['nullable', 'array'],
            'charges_included.*' => ['string', 'max:100'],
            'accepts_animals'    => ['nullable', 'boolean'],
            'accepts_smokers'    => ['nullable', 'boolean'],
            'accepts_students'   => ['nullable', 'boolean'],
            'available_from'     => ['nullable', 'date'],
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

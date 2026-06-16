<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertySearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Convertit les chaînes vides en null pour éviter les échecs de validation in:...
        $scalar = array_map(
            fn ($v) => is_string($v) && $v === '' ? null : $v,
            $this->except(['amenities'])
        );
        $this->merge($scalar);
    }

    public function rules(): array
    {
        $typeValues = implode(',', [
            // valeurs françaises (frontend)
            'chambre_simple', 'studio', 'appartement', 'maison',
            'mini_cite', 'local_commercial', 'chambre_etudiante', 'logement_meuble',
            // valeurs anglaises (compatibilité)
            'apartment', 'house', 'villa', 'commercial', 'land',
        ]);

        $sortValues      = 'price_asc,price_desc,newest,oldest,popular,relevance';
        $transTypeValues = 'rent,sale';

        return [
            'city'             => ['sometimes', 'string', 'max:100'],
            'type'             => ['sometimes', 'string', 'in:' . $typeValues],
            'transaction_type' => ['sometimes', 'string', 'in:' . $transTypeValues],
            'neighborhood'     => ['sometimes', 'string', 'max:100'],
            'price_min'        => ['sometimes', 'numeric', 'min:0'],
            'price_max'        => ['sometimes', 'numeric', 'min:0', Rule::when($this->filled('price_min'), 'gte:price_min')],
            'surface_min'      => ['sometimes', 'numeric', 'min:0'],
            'surface_max'      => ['sometimes', 'numeric', 'min:0', Rule::when($this->filled('surface_min'), 'gte:surface_min')],
            'rooms_min'        => ['sometimes', 'integer', 'min:1', 'max:20'],
            'amenities'        => ['sometimes', 'array'],
            'amenities.*'      => ['string', 'max:100'],  // aucune restriction — valeurs libres stockées en JSON
            'available_from'   => ['sometimes', 'date'],
            // nullable (not sometimes) so required_with fires even when field is absent
            'latitude'         => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude'        => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'radius_km'        => ['sometimes', 'numeric', 'min:0.5', 'max:50'],
            'sort'             => ['sometimes', 'string', 'in:' . $sortValues],
            'per_page'         => ['sometimes', 'integer', 'min:5', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'price_max.gte'              => 'Le prix maximum doit être supérieur au prix minimum.',
            'surface_max.gte'            => 'La surface maximum doit être supérieure à la surface minimum.',
            'latitude.required_with'     => 'La latitude est requise avec la longitude.',
            'longitude.required_with'    => 'La longitude est requise avec la latitude.',
            'radius_km.max'              => 'Le rayon de recherche ne peut pas dépasser 50 km.',
        ];
    }
}

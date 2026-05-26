<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavedSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $typeValues    = 'apartment,house,studio,villa,commercial,land';
        $amenityValues = 'parking,wifi,pool,gym,security,elevator,garden,balcony,generator,water';

        return [
            'name'                   => ['required', 'string', 'min:2', 'max:100'],
            'notifications_enabled'  => ['nullable', 'boolean'],
            'criteria'               => ['required', 'array'],
            'criteria.city'          => ['sometimes', 'string', 'max:100'],
            'criteria.type'          => ['sometimes', 'string', 'in:' . $typeValues],
            'criteria.price_min'     => ['sometimes', 'numeric', 'min:0'],
            'criteria.price_max'     => ['sometimes', 'numeric', 'min:0'],
            'criteria.surface_min'   => ['sometimes', 'numeric', 'min:0'],
            'criteria.rooms_min'     => ['sometimes', 'integer', 'min:1'],
            'criteria.amenities'     => ['sometimes', 'array'],
            'criteria.amenities.*'   => ['string', 'in:' . $amenityValues],
            'criteria.latitude'      => ['sometimes', 'numeric', 'between:-90,90'],
            'criteria.longitude'     => ['sometimes', 'numeric', 'between:-180,180'],
            'criteria.radius_km'     => ['sometimes', 'numeric', 'min:0.5', 'max:50'],
        ];
    }
}

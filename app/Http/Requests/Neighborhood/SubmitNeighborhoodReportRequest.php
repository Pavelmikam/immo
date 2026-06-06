<?php

namespace App\Http\Requests\Neighborhood;

use Illuminate\Foundation\Http\FormRequest;

class SubmitNeighborhoodReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'criterion'    => ['required', 'string', 'in:eau,electricite,securite,transport,commerces,routes,sante,education'],
            'score'        => ['required', 'integer', 'min:1', 'max:5'],
            'latitude'     => ['required', 'numeric', 'between:-90,90'],
            'longitude'    => ['required', 'numeric', 'between:-180,180'],
            'city'         => ['nullable', 'string', 'max:100'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'comment'      => ['nullable', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'criterion.required' => 'Le critère est obligatoire.',
            'criterion.in'       => 'Le critère sélectionné est invalide.',
            'score.required'     => 'La note est obligatoire.',
            'score.min'          => 'La note doit être comprise entre 1 et 5.',
            'score.max'          => 'La note doit être comprise entre 1 et 5.',
            'latitude.required'  => 'La localisation est obligatoire.',
            'longitude.required' => 'La localisation est obligatoire.',
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason'      => ['required', 'in:contenu_inapproprie,arnaque_suspectee,informations_fausses,photos_trompeuses,prix_abusif,annonce_inexistante,comportement_abusif,autre'],
            'description' => ['nullable', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'La raison du signalement est obligatoire.',
            'reason.in'       => 'La raison sélectionnée est invalide.',
        ];
    }
}

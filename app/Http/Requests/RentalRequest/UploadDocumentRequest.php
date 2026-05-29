<?php

namespace App\Http\Requests\RentalRequest;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = (int) config('app.document_max_size', 10240);

        return [
            'document'    => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', "max:{$maxKb}"],
            'type'        => ['required', 'string', 'in:cni,passeport,certificat_residence,bulletin_salaire,attestation_travail,releve_bancaire,garant_cni,garant_salaire,autre'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'document.required' => 'Le document est obligatoire.',
            'document.mimes'    => 'Le document doit être un PDF ou une image (JPG, PNG).',
            'document.max'      => 'Le document ne doit pas dépasser 10 MB.',
            'type.required'     => 'Le type de document est obligatoire.',
            'type.in'           => 'Le type de document sélectionné est invalide.',
        ];
    }
}

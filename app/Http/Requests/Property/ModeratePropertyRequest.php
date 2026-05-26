<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class ModeratePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject'],
            'reason' => ['required_if:action,reject', 'nullable', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required'    => 'L\'action de modération est requise.',
            'action.in'          => 'L\'action doit être "approve" ou "reject".',
            'reason.required_if' => 'Un motif de refus est obligatoire.',
            'reason.min'         => 'Le motif doit comporter au moins 10 caractères.',
        ];
    }
}

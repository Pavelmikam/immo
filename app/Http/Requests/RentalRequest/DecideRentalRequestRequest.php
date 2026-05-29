<?php

namespace App\Http\Requests\RentalRequest;

use Illuminate\Foundation\Http\FormRequest;

class DecideRentalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action'         => ['required', 'string', 'in:accept,refuse'],
            'owner_response' => ['required_if:action,refuse', 'nullable', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required'            => 'L\'action est obligatoire.',
            'action.in'                  => 'L\'action doit être accept ou refuse.',
            'owner_response.required_if' => 'Un motif est obligatoire en cas de refus.',
            'owner_response.min'         => 'Le motif doit contenir au moins 10 caractères.',
        ];
    }
}

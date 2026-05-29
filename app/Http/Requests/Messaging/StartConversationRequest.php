<?php

namespace App\Http\Requests\Messaging;

use Illuminate\Foundation\Http\FormRequest;

class StartConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rental_request_id' => ['nullable', 'integer', 'exists:rental_requests,id'],
            'initial_message'   => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'initial_message.required' => 'Le message initial est obligatoire.',
            'initial_message.min'      => 'Le message doit contenir au moins 5 caractères.',
            'initial_message.max'      => 'Le message ne doit pas dépasser 2000 caractères.',
        ];
    }
}

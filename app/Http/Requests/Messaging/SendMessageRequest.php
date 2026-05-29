<?php

namespace App\Http\Requests\Messaging;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body'          => ['required', 'string', 'min:1', 'max:2000'],
            'attachments'   => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required'       => 'Le message ne peut pas être vide.',
            'body.max'            => 'Le message ne doit pas dépasser 2000 caractères.',
            'attachments.max'     => 'Vous ne pouvez pas joindre plus de 3 fichiers.',
            'attachments.*.mimes' => 'Les pièces jointes doivent être JPG, PNG, WebP ou PDF.',
            'attachments.*.max'   => 'Chaque pièce jointe ne doit pas dépasser 5 MB.',
        ];
    }
}

<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(\+?[0-9\s\-]{7,20})$/'],
            'city'  => ['nullable', 'string', 'max:100'],
            'bio'   => ['nullable', 'string', 'max:500'],
        ];
    }
}

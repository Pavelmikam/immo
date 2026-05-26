<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'                 => ['required', 'string'],
            'email'                 => ['required', 'email', 'exists:users,email'],
            'password'              => ['required', 'min:8', 'confirmed',
                                        Password::min(8)->mixedCase()->numbers()],
            'password_confirmation' => ['required'],
        ];
    }
}

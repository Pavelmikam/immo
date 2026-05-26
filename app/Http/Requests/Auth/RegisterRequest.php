<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'min:2', 'max:100'],
            'email'                 => ['required', 'email:rfc,dns', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed',
                                        Password::min(8)->mixedCase()->numbers()],
            'password_confirmation' => ['required'],
            'role'                  => ['required', Rule::in(['locataire', 'proprietaire'])],
            'phone'                 => ['nullable', 'string', 'max:20',
                                        'regex:/^(\+?[0-9\s\-]{7,20})$/'],
            'city'                  => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Le nom est obligatoire.',
            'email.required'     => 'L\'adresse email est obligatoire.',
            'email.email'        => 'L\'adresse email n\'est pas valide.',
            'email.unique'       => 'Cette adresse email est déjà utilisée.',
            'password.required'  => 'Le mot de passe est obligatoire.',
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'role.required'      => 'Le rôle est obligatoire.',
            'role.in'            => 'Le rôle doit être locataire ou proprietaire.',
            'phone.regex'        => 'Le numéro de téléphone n\'est pas valide.',
        ];
    }
}

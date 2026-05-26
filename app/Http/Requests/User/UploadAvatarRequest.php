<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSize = (int) config('app.avatar_max_size', 2048);

        return [
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', "max:{$maxSize}"],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->hasFile('avatar') && ! $this->file('avatar')->isValid()) {
            $this->merge([]);
        }
    }
}

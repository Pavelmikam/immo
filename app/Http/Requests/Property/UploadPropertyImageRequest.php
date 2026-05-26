<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class UploadPropertyImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSize = (int) config('app.property_image_max_size', 5120);

        return [
            'image'   => ['required', 'image', 'mimes:jpeg,jpg,png,webp', "max:{$maxSize}"],
            'caption' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Une image est requise.',
            'image.image'    => 'Le fichier doit être une image.',
            'image.mimes'    => 'L\'image doit être au format JPEG, PNG ou WebP.',
            'image.max'      => 'L\'image ne doit pas dépasser ' . config('app.property_image_max_size', 5120) . ' Ko.',
        ];
    }
}

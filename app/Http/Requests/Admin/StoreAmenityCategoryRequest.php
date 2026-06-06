<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAmenityCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category'   => ['required', 'in:property_type,amenity,charge'],
            'value'      => [
                'required', 'string', 'max:100',
                Rule::unique('amenity_categories', 'value')
                    ->where('category', $this->category),
            ],
            'label'      => ['required', 'string', 'max:150'],
            'is_active'  => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}

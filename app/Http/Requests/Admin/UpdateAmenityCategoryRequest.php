<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAmenityCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id       = $this->route('amenity_category')?->id;
        $category = $this->category ?? $this->route('amenity_category')?->category;

        return [
            'category'   => ['sometimes', 'in:property_type,amenity,charge'],
            'value'      => [
                'sometimes', 'string', 'max:100',
                Rule::unique('amenity_categories', 'value')
                    ->where('category', $category)
                    ->ignore($id),
            ],
            'label'      => ['sometimes', 'string', 'max:150'],
            'is_active'  => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
        ];
    }
}

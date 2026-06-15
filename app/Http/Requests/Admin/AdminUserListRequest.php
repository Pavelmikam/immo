<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminUserListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_map(
            fn ($v) => $v === '' ? null : $v,
            $this->all()
        ));
    }

    public function rules(): array
    {
        return [
            'role'      => ['sometimes', 'nullable', 'in:locataire,proprietaire,admin'],
            'is_active' => ['sometimes', 'nullable', 'in:0,1,true,false'],
            'deleted'   => ['sometimes', 'nullable', 'in:0,1,true,false'],
            'search'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort'      => ['sometimes', 'nullable', 'in:newest,oldest,name_asc,name_desc'],
            'per_page'  => ['sometimes', 'nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}

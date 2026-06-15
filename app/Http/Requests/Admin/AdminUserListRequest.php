<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminUserListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role'      => ['sometimes', 'in:locataire,proprietaire,admin'],
            'is_active' => ['sometimes', 'in:0,1,true,false'],
            'deleted'   => ['sometimes', 'in:0,1,true,false'],
            'search'    => ['sometimes', 'string', 'max:100'],
            'sort'      => ['sometimes', 'in:newest,oldest,name_asc,name_desc'],
            'per_page'  => ['sometimes', 'integer', 'min:5', 'max:100'],
        ];
    }
}

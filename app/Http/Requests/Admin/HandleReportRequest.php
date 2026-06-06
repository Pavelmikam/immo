<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HandleReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action'     => ['required', 'in:resolve,reject,in_progress'],
            'admin_note' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required'     => "L'action est obligatoire.",
            'action.in'           => "L'action doit être resolve, reject ou in_progress.",
            'admin_note.required' => 'Une note admin est obligatoire.',
        ];
    }
}

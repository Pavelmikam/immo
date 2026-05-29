<?php

namespace App\Http\Requests\RentalRequest;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visit_scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'visit_scheduled_at.required' => 'La date de visite est obligatoire.',
            'visit_scheduled_at.after'    => 'La date de visite doit être dans le futur.',
        ];
    }
}

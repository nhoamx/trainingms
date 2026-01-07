<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inspector_name' => ['required', 'string', 'max:255'],
            'inspection_date' => ['required', 'date'],
            'extinguisher_weight' => ['nullable', 'string', 'max:50'],
            'checklist_results' => ['required', 'array'],
            'checklist_results.*.date' => ['nullable', 'date'],
            'checklist_results.*.status' => ['nullable', 'string', 'in:ok,issue'],
            'checklist_results.*.result' => ['nullable', 'string', 'max:500'],
            'anomalies_followup' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'inspector_name.required' => 'El nombre del inspector es requerido',
            'inspection_date.required' => 'La fecha de inspección es requerida',
            'inspection_date.date' => 'La fecha de inspección debe ser una fecha válida',
            'checklist_results.required' => 'Debe completar al menos un item del checklist',
            'checklist_results.array' => 'El formato del checklist es inválido',
        ];
    }
}

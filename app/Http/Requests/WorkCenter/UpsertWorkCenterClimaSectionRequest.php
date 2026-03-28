<?php

namespace App\Http\Requests\WorkCenter;

use App\Models\WorkCenter;
use Illuminate\Foundation\Http\FormRequest;

class UpsertWorkCenterClimaSectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var WorkCenter $workCenter */
        $workCenter = $this->route('workCenter');

        return $this->user() !== null
            && $this->user()->hasRole(['admin', 'super-admin'])
            && $this->user()->can('viewWorkCenterDashboard', $workCenter);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'section_key' => ['required', 'in:analysis_department,analysis_position,recommendations,recommendations_factors,report_card_config,foda,conclusions,conclusions_config'],
            'content' => ['nullable', 'string', 'max:50000'],
            'status' => ['required', 'in:draft,published'],
        ];
    }

    public function messages(): array
    {
        return [
            'section_key.required' => 'Debes seleccionar la sección a actualizar.',
            'section_key.in' => 'La sección seleccionada no es válida.',
            'status.required' => 'Debes indicar el estado de la sección.',
            'status.in' => 'El estado debe ser borrador o publicado.',
        ];
    }
}

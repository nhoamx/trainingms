<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

class UpsertOrganizationClimaSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Organization $organization */
        $organization = $this->route('organization');

        return $this->user() !== null
            && $this->user()->hasRole(['admin', 'super-admin'])
            && $this->user()->can('viewOrganizationDashboard', $organization);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'section_key' => ['required', 'in:conclusions_config'],
            'content' => ['nullable', 'string', 'max:100000'],
            'status' => ['required', 'in:draft,published'],
        ];
    }

    public function messages(): array
    {
        return [
            'section_key.required' => 'Debes indicar la sección a actualizar.',
            'section_key.in' => 'La sección no es válida.',
            'content.max' => 'El contenido es demasiado largo.',
            'status.required' => 'Debes indicar el estado.',
        ];
    }
}

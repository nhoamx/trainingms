<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommitteeMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $organization = $this->route('organization');

        return $user && (
            $user->hasRole(['admin', 'super-admin']) ||
            $user->organization_id === $organization->id
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'department_area' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'factor' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido.',
            'department_area.required' => 'El área es requerida.',
            'position.required' => 'El puesto es requerido.',
            'factor.required' => 'El factor es requerido.',
        ];
    }
}

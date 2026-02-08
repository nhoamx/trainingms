<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Corporate data
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,gif', 'max:10240'],
            'actividad_principal' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del corporativo es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'logo.image' => 'El logotipo debe ser una imagen válida.',
            'logo.mimes' => 'El logotipo debe ser un archivo JPEG, PNG o GIF.',
            'logo.max' => 'El logotipo no puede exceder 10MB.',
            'actividad_principal.max' => 'La actividad principal no puede exceder 255 caracteres.',
        ];
    }
}

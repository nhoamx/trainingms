<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkCenterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Básicos
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:headquarters,plant,branch,warehouse,office,other'],

            // Datos Fiscales
            'legal_name' => ['required', 'string', 'max:255'],
            'tax_id' => ['required', 'string', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/'],
            'employer_registration' => ['nullable', 'string', 'max:50'],

            // Ubicación
            'street_address' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'digits:5'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
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
            'name.required' => 'El nombre del centro de trabajo es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'type.required' => 'Debe seleccionar el tipo de centro.',
            'type.in' => 'El tipo de centro seleccionado no es válido.',
            'legal_name.required' => 'La razón social es obligatoria.',
            'tax_id.required' => 'El RFC es obligatorio.',
            'tax_id.regex' => 'El RFC debe ser un formato válido (13 caracteres).',
            'postal_code.digits' => 'El código postal debe tener 5 dígitos.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user belongs to the organization they're trying to update
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
            // General Information
            'name' => ['required', 'string', 'max:255'],
            'razon_social' => ['nullable', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:13'],
            'registro_patronal' => ['nullable', 'string', 'max:50'],
            'actividad_principal' => ['nullable', 'string', 'max:500'],
            'fecha_aplicacion' => ['nullable', 'date'],

            // Address
            'calle_numero' => ['nullable', 'string', 'max:255'],
            'colonia' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'string', 'max:10'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:255'],

            // Workers
            'total_trabajadores' => ['nullable', 'integer', 'min:0'],
            'total_hombres' => ['nullable', 'integer', 'min:0'],
            'total_mujeres' => ['nullable', 'integer', 'min:0'],

            // Sample
            'muestra_aplicada' => ['nullable', 'integer', 'min:0'],
            'muestra_hombres' => ['nullable', 'integer', 'min:0'],
            'muestra_mujeres' => ['nullable', 'integer', 'min:0'],
            'justificacion_muestra' => ['nullable', 'string'],

            // Contact
            'contacto_nombre' => ['nullable', 'string', 'max:255'],
            'contacto_puesto' => ['nullable', 'string', 'max:255'],
            'contacto_email' => ['nullable', 'email', 'max:255'],
            'contacto_movil' => ['nullable', 'string', 'max:20'],

            // Responsible
            'responsable_nombre' => ['nullable', 'string', 'max:255'],
            'responsable_puesto' => ['nullable', 'string', 'max:255'],
            'responsable_email' => ['nullable', 'email', 'max:255'],
            'responsable_movil' => ['nullable', 'string', 'max:20'],

            // Committee
            'comite_integrantes' => ['nullable', 'integer', 'min:0'],
            'comite_hombres' => ['nullable', 'integer', 'min:0'],
            'comite_mujeres' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la organización es requerido.',
            'rfc.max' => 'El RFC no puede tener más de 13 caracteres.',
            'contacto_email.email' => 'El email del contacto debe ser válido.',
            'responsable_email.email' => 'El email del responsable debe ser válido.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,gif', 'max:10240'],
            'folio_organization' => ['nullable', 'numeric'],
            // Identificación
            'razon_social' => ['nullable', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:13'],
            'registro_patronal' => ['nullable', 'string', 'max:50'],
            // Domicilio
            'calle_numero' => ['nullable', 'string', 'max:255'],
            'colonia' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'digits:5'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:255'],
            // Contactos
            'contacto_nombre' => ['nullable', 'string', 'max:255'],
            'contacto_puesto' => ['nullable', 'string', 'max:255'],
            'contacto_email' => ['nullable', 'email', 'max:255'],
            'contacto_movil' => ['nullable', 'string', 'max:30'],
            'responsable_nombre' => ['nullable', 'string', 'max:255'],
            'responsable_puesto' => ['nullable', 'string', 'max:255'],
            'responsable_email' => ['nullable', 'email', 'max:255'],
            'responsable_movil' => ['nullable', 'string', 'max:30'],
            // Actividad
            'actividad_principal' => ['nullable', 'string', 'max:255'],
            // Totales y muestra
            'total_trabajadores' => ['nullable', 'integer', 'min:0'],
            'total_hombres' => ['nullable', 'integer', 'min:0'],
            'total_mujeres' => ['nullable', 'integer', 'min:0'],
            'muestra_aplicada' => ['nullable', 'integer', 'min:0'],
            'muestra_hombres' => ['nullable', 'integer', 'min:0'],
            'muestra_mujeres' => ['nullable', 'integer', 'min:0'],
            // Comité
            'comite_integrantes' => ['nullable', 'integer', 'min:0'],
            'comite_hombres' => ['nullable', 'integer', 'min:0'],
            'comite_mujeres' => ['nullable', 'integer', 'min:0'],
            // Fecha y justificación
            'fecha_aplicacion' => ['nullable', 'date'],
            'justificacion_muestra' => ['nullable', 'string'],
        ];
    }
}

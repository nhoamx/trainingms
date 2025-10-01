<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOMRPdfRequest extends FormRequest
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
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'folio_batch_id' => ['required', 'integer', 'exists:folio_batches,id'],
            'guide_type' => ['required', 'string', 'in:referencia-i,referencia-iii,referencia-v,escala-cisneros'],
            'generate_all' => ['boolean'],
            'folios' => ['array'],
            'folios.*' => ['string', 'size:4'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'organization_id.required' => 'La organización es requerida.',
            'organization_id.exists' => 'La organización no existe.',
            'folio_batch_id.required' => 'El lote de folios es requerido.',
            'folio_batch_id.exists' => 'El lote de folios no existe.',
            'guide_type.required' => 'El tipo de guía es requerido.',
            'guide_type.in' => 'El tipo de guía no es válido.',
            'folios.array' => 'Los folios deben ser un arreglo.',
            'folios.*.size' => 'Cada folio debe tener exactamente 4 dígitos.',
        ];
    }
}

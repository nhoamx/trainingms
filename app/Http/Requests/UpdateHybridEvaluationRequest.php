<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHybridEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Public route - anyone with the folio can submit
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
            'referencia_iii' => ['nullable', 'json'],
            'referencia_i' => ['nullable', 'json'],
            'referencia_iii_conditional' => ['nullable', 'json'],
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
            'referencia_iii.json' => 'Las respuestas de Referencia III deben ser una cadena JSON válida.',
            'referencia_i.json' => 'Las respuestas de Referencia I deben ser una cadena JSON válida.',
            'referencia_iii_conditional.json' => 'Las respuestas condicionales deben ser una cadena JSON válida.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert to arrays if they're JSON strings (from form data)
        if ($this->has('referencia_iii') && is_string($this->referencia_iii)) {
            $this->merge([
                'referencia_iii' => json_decode($this->referencia_iii, true),
            ]);
        }

        if ($this->has('referencia_i') && is_string($this->referencia_i)) {
            $this->merge([
                'referencia_i' => json_decode($this->referencia_i, true),
            ]);
        }

        if ($this->has('referencia_iii_conditional') && is_string($this->referencia_iii_conditional)) {
            $this->merge([
                'referencia_iii_conditional' => json_decode($this->referencia_iii_conditional, true),
            ]);
        }
    }
}

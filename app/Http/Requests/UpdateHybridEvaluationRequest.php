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
            'referencia_iii.json' => 'Las respuestas de Referencia III deben estar en formato JSON válido.',
            'referencia_i.json' => 'Las respuestas de Referencia I deben estar en formato JSON válido.',
            'referencia_iii_conditional.json' => 'Las respuestas condicionales deben estar en formato JSON válido.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If the data is already an array, convert it to JSON string
        if ($this->has('referencia_iii') && is_array($this->referencia_iii)) {
            $this->merge([
                'referencia_iii' => json_encode($this->referencia_iii),
            ]);
        }

        if ($this->has('referencia_i') && is_array($this->referencia_i)) {
            $this->merge([
                'referencia_i' => json_encode($this->referencia_i),
            ]);
        }

        if ($this->has('referencia_iii_conditional') && is_array($this->referencia_iii_conditional)) {
            $this->merge([
                'referencia_iii_conditional' => json_encode($this->referencia_iii_conditional),
            ]);
        }
    }
}

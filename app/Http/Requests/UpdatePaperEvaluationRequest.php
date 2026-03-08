<?php

namespace App\Http\Requests;

use App\Models\PaperEvaluation;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaperEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow administrators to edit evaluations
        return $this->user()?->hasRole(['admin', 'super-admin']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $evaluation = $this->route('paperEvaluation');

        return [
            'evaluee_name' => ['nullable', 'string', 'max:255'],
            'personal_folio' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($evaluation) {
                    if ($value && $evaluation) {
                        $isElevenDigitFormat = strlen((string) $evaluation->folio) === 11 || ! empty($evaluation->work_center_code);
                        $requiredDigits = $isElevenDigitFormat ? 5 : 4;

                        if (! preg_match('/^\d{'.$requiredDigits.'}$/', $value)) {
                            $fail("El folio personal debe tener exactamente {$requiredDigits} dígitos.");

                            return;
                        }

                        $newFolio = PaperEvaluation::generateFolio(
                            $evaluation->evaluation_type_code,
                            $evaluation->organization_code,
                            $value,
                            $isElevenDigitFormat ? $evaluation->work_center_code : null
                        );

                        if (! PaperEvaluation::isFolioAvailable($newFolio, $evaluation->id)) {
                            $fail("El folio {$newFolio} ya está en uso.");
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'evaluee_name.string' => 'El nombre debe ser texto.',
            'evaluee_name.max' => 'El nombre no puede exceder 255 caracteres.',
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'evaluee_name' => 'nombre del evaluado',
            'personal_folio' => 'folio personal',
        ];
    }
}

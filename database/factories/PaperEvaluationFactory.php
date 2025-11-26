<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaperEvaluation>
 */
class PaperEvaluationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $evaluationTypeCodes = ['01', '02', '03', '04'];
        $evaluationTypeCode = fake()->randomElement($evaluationTypeCodes);
        $organizationCode = str_pad(fake()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT);
        $personalFolio = str_pad(fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);
        $folio = $evaluationTypeCode.$organizationCode.$personalFolio;

        return [
            'folio' => $folio,
            'evaluation_type_code' => $evaluationTypeCode,
            'organization_code' => $organizationCode,
            'personal_folio' => $personalFolio,
            'organization_id' => Organization::factory(),
            'evaluation_type' => PaperEvaluation::getEvaluationTypeFromCode($evaluationTypeCode),
            'source' => 'paper',
            'processing_status' => 'completed',
            'pdf_file_path' => fake()->filePath(),
            'processed_at' => now(),
            'demographic_data' => null,
            'referencia_i_answers' => null,
            'referencia_iii_answers' => null,
            'referencia_iii_conditional' => null,
            'cisneros_answers' => null,
            'raw_data' => null,
            'processing_error' => null,
            'retry_count' => 0,
        ];
    }

    /**
     * Indicate the evaluation is for Referencia I (PTSD)
     */
    public function referenciaI(): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluation_type_code' => '01',
            'evaluation_type' => 'referencia_i',
            'referencia_i_answers' => [
                '1' => fake()->randomElement(['SI', 'NO']),
                '2' => fake()->randomElement(['SI', 'NO']),
                '3' => fake()->randomElement(['SI', 'NO']),
            ],
        ]);
    }

    /**
     * Indicate the evaluation is for Referencia III (Workplace)
     */
    public function referenciaIII(): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluation_type_code' => '02',
            'evaluation_type' => 'referencia_iii',
            'referencia_iii_answers' => [
                'referencia_iii' => [
                    '1' => fake()->randomElement(['A', 'B', 'C', 'D', 'E']),
                    '2' => fake()->randomElement(['A', 'B', 'C', 'D', 'E']),
                ],
            ],
        ]);
    }

    /**
     * Indicate the evaluation is for Referencia V (Demographics)
     */
    public function referenciaV(): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluation_type_code' => '03',
            'evaluation_type' => 'referencia_v',
            'demographic_data' => [
                'sexo' => fake()->randomElement(['masculino', 'femenino']),
                'edad' => ['decenas' => '3', 'unidades' => '4'],
                'estado_civil' => fake()->randomElement(['soltero', 'casado', 'divorciado']),
            ],
        ]);
    }

    /**
     * Indicate the evaluation is for Cisneros (Mobbing)
     */
    public function cisneros(): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluation_type_code' => '04',
            'evaluation_type' => 'cisneros',
            'cisneros_answers' => [
                '1' => fake()->randomElement(['SI', 'NO']),
                '2' => fake()->randomElement(['SI', 'NO']),
            ],
        ]);
    }

    /**
     * Indicate the evaluation is online
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'online',
        ]);
    }

    /**
     * Indicate the evaluation failed processing
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'processing_status' => 'failed',
            'processing_error' => fake()->sentence(),
            'retry_count' => fake()->numberBetween(1, 3),
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate the evaluation is pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'processing_status' => 'pending',
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate the evaluation is for Likert (Workplace Climate)
     */
    public function likert(): static
    {
        $questions = [];
        for ($i = 1; $i <= 23; $i++) {
            $questions[(string) $i] = fake()->randomElement(['A', 'B', 'C', 'D']);
        }

        return $this->state(fn (array $attributes) => [
            'evaluation_type_code' => '05',
            'evaluation_type' => 'likert',
            'likert_answers' => [
                'questions' => $questions,
                'genero' => fake()->randomElement(['masculino', 'femenino']),
                'turno' => fake()->randomElement(['matutino', 'vespertino', 'nocturno']),
                'tipo_contrato' => fake()->randomElement(['tiempo_indeterminado', 'por_tiempo_determinado']),
                'puestos' => (string) fake()->numberBetween(1, 24),
                'areas' => (string) fake()->numberBetween(1, 17),
            ],
        ]);
    }
}

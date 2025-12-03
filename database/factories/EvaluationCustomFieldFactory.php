<?php

namespace Database\Factories;

use App\Models\EvaluationCustomField;
use App\Models\PaperEvaluation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EvaluationCustomField>
 */
class EvaluationCustomFieldFactory extends Factory
{
    protected $model = EvaluationCustomField::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $labels = [
            'Supervisor' => 'supervisor',
            'Superintendente' => 'superintendente',
            'Gerente' => 'gerente',
            'Líder de Línea' => 'lider_de_linea',
            'CodigoLinea' => 'codigo_linea',
        ];

        $label = fake()->randomElement(array_keys($labels));

        return [
            'paper_evaluation_id' => PaperEvaluation::factory(),
            'key' => $labels[$label],
            'key_label' => $label,
            'value' => fake()->name(),
        ];
    }

    /**
     * Create a supervisor field
     */
    public function supervisor(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'supervisor',
            'key_label' => 'Supervisor',
            'value' => fake()->name(),
        ]);
    }

    /**
     * Create a gerente field
     */
    public function gerente(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'gerente',
            'key_label' => 'Gerente',
            'value' => fake()->name(),
        ]);
    }

    /**
     * Create a codigo_linea field
     */
    public function codigoLinea(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'codigo_linea',
            'key_label' => 'CodigoLinea',
            'value' => fake()->randomNumber(4, true),
        ]);
    }
}

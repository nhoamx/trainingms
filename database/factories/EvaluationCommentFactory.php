<?php

namespace Database\Factories;

use App\Models\PaperEvaluation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EvaluationComment>
 */
class EvaluationCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $factors = [
            'Condiciones en el ambiente de trabajo',
            'Carga de trabajo',
            'Falta de control sobre el trabajo',
            'Jornada de trabajo',
            'Interferencia en la relación trabajo-familia',
            'Liderazgo',
            'Relaciones en el trabajo',
            'Violencia',
        ];

        return [
            'paper_evaluation_id' => PaperEvaluation::factory(),
            'factor' => fake()->randomElement($factors),
            'comment' => fake()->sentence(10),
        ];
    }
}

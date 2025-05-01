<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Category;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CategoryReportService
{
    /**
     * Obtiene el conteo de respuestas por categoría y tipo de respuesta
     * Implementa el Query #1 de la guía III: Conteo de respuestas por categoría y tipo de respuesta
     *
     * @param string $referenceGuide
     * @return Collection
     */
    public function getCategoryAnswerTypeDistribution(string $referenceGuide = 'III'): Collection
    {
        try {
            // Obtenemos todos los IDs de personal con evaluaciones de la guía III para consistencia
            $guideIIIPersonalIds = Evaluation::where('reference_guide', $referenceGuide)
                ->whereNotNull('personal_id')
                ->pluck('personal_id')
                ->unique()
                ->filter();

            if ($guideIIIPersonalIds->isEmpty()) {
                Log::warning("No se encontraron IDs de personal con evaluaciones de la Guía {$referenceGuide}.");
                return collect();
            }

            // Ejecutamos la consulta que obtiene el conteo de respuestas por categoría y tipo
            $results = Question::join('evaluations', 'questions.evaluation_id', '=', 'evaluations.id')
                ->join('categories', 'questions.category_id', '=', 'categories.id')
                ->where('questions.reference_guide', $referenceGuide)
                ->whereNotNull('questions.answer')
                ->whereIn('questions.personal_id', $guideIIIPersonalIds)
                ->select(
                    'categories.id as category_id',
                    'categories.name as category_name',
                    'questions.answer',
                    DB::raw('COUNT(*) as total_responses')
                )
                ->groupBy('categories.id', 'categories.name', 'questions.answer')
                ->orderBy('categories.name')
                ->orderBy('questions.answer')
                ->get();

            // Procesamos los resultados para estructurarlos adecuadamente
            $categoriesData = [];
            foreach ($results as $result) {
                $categoryId = $result->category_id;
                $answer = $result->answer;

                if (!isset($categoriesData[$categoryId])) {
                    $categoriesData[$categoryId] = [
                        'id' => $categoryId,
                        'name' => $result->category_name,
                        'responses' => [
                            'A' => 0,
                            'B' => 0,
                            'C' => 0,
                            'D' => 0,
                            'E' => 0,
                        ],
                        'total' => 0
                    ];
                }

                // Aseguramos que solo contamos respuestas válidas (A, B, C, D, E)
                if (in_array($answer, ['A', 'B', 'C', 'D', 'E'])) {
                    $categoriesData[$categoryId]['responses'][$answer] = $result->total_responses;
                    $categoriesData[$categoryId]['total'] += $result->total_responses;
                }
            }

            // Calculamos porcentajes para cada tipo de respuesta
            foreach ($categoriesData as $categoryId => &$data) {
                $data['percentages'] = [];
                foreach ($data['responses'] as $answerType => $count) {
                    $percentage = $data['total'] > 0 ? ($count / $data['total']) * 100 : 0;
                    $data['percentages'][$answerType] = round($percentage, 2);
                }
            }

            return collect(array_values($categoriesData));
        } catch (\Exception $e) {
            Log::error("Error al obtener la distribución de respuestas por categoría: " . $e->getMessage());
            return collect();
        }
    }

    /**
     * Obtiene la suma total del valor de respuestas por categoría
     * Implementa el Query #2 de la guía III: Suma total del valor de respuestas por categoría
     *
     * @param string $referenceGuide
     * @return Collection
     */
    public function getCategoryTotalScores(string $referenceGuide = 'III'): Collection
    {
        try {
            // Obtenemos todos los IDs de personal con evaluaciones de la guía III para consistencia
            $guideIIIPersonalIds = Evaluation::where('reference_guide', $referenceGuide)
                ->whereNotNull('personal_id')
                ->pluck('personal_id')
                ->unique()
                ->filter();

            if ($guideIIIPersonalIds->isEmpty()) {
                Log::warning("No se encontraron IDs de personal con evaluaciones de la Guía {$referenceGuide}.");
                return collect();
            }

            // Ejecutar la consulta que obtiene la suma total por categoría
            $results = Question::join('evaluations', 'questions.evaluation_id', '=', 'evaluations.id')
                ->join('categories', 'questions.category_id', '=', 'categories.id')
                ->where('questions.reference_guide', $referenceGuide)
                ->whereNotNull('questions.value')
                ->whereIn('questions.personal_id', $guideIIIPersonalIds)
                ->select(
                    'categories.id as category_id',
                    'categories.name as category_name',
                    DB::raw('SUM(questions.value) as total_score'),
                    DB::raw('COUNT(*) as question_count')
                )
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('total_score')
                ->get();

            // Procesar los resultados para añadir información adicional como promedios
            $processedResults = $results->map(function ($item) {
                $avgScore = $item->question_count > 0 ? $item->total_score / $item->question_count : 0;

                return [
                    'id' => $item->category_id,
                    'name' => $item->category_name,
                    'total_score' => $item->total_score,
                    'question_count' => $item->question_count,
                    'avg_score' => round($avgScore, 2)
                ];
            });

            return $processedResults;
        } catch (\Exception $e) {
            Log::error("Error al obtener la suma total de respuestas por categoría: " . $e->getMessage());
            return collect();
        }
    }
}

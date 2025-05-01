<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Dimension;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DimensionReportService
{
    /**
     * Obtiene el conteo de respuestas por dimensión y tipo de respuesta
     * Implementa el Query #5 de la guía III: Conteo de respuestas por dimensión y tipo de respuesta
     *
     * @param string $referenceGuide
     * @return Collection
     */
    public function getDimensionAnswerTypeDistribution(string $referenceGuide = 'III'): Collection
    {
        try {
            // Obtenemos todos los IDs de personal con evaluaciones de la guía especificada para consistencia
            $guidePersonalIds = Evaluation::where('reference_guide', $referenceGuide)
                ->whereNotNull('personal_id')
                ->pluck('personal_id')
                ->unique()
                ->filter();

            if ($guidePersonalIds->isEmpty()) {
                Log::warning("No se encontraron IDs de personal con evaluaciones de la Guía {$referenceGuide}.");
                return collect();
            }

            // Ejecutamos la consulta que obtiene el conteo de respuestas por dimensión y tipo
            // Relación directa: questions -> dimension_id -> dimensions (según las instrucciones)
            $results = Question::join('evaluations', 'questions.evaluation_id', '=', 'evaluations.id')
                ->join('dimensions', 'questions.dimension_id', '=', 'dimensions.id')
                ->where('questions.reference_guide', $referenceGuide)
                ->whereNotNull('questions.answer')
                ->whereIn('questions.personal_id', $guidePersonalIds)
                ->select(
                    'dimensions.id as dimension_id',
                    'dimensions.name as dimension_name',
                    'questions.answer',
                    DB::raw('COUNT(*) as total_responses')
                )
                ->groupBy('dimensions.id', 'dimensions.name', 'questions.answer')
                ->orderBy('dimensions.name')
                ->orderBy('questions.answer')
                ->get();

            // Procesamos los resultados para agruparlos por dimensión
            $processedData = $this->processDimensionResults($results);

            return $processedData;
        } catch (\Exception $e) {
            Log::error("Error al obtener el conteo de respuestas por dimensión: " . $e->getMessage());
            return collect();
        }
    }

    /**
     * Procesa los resultados de la consulta para estructurarlos por dimensión
     *
     * @param Collection $results
     * @return Collection
     */
    private function processDimensionResults(Collection $results): Collection
    {
        $dimensionData = collect();

        // Agrupamos los resultados por dimensión
        $groupedResults = $results->groupBy('dimension_id');

        foreach ($groupedResults as $dimensionId => $dimensionResponses) {
            $dimensionName = $dimensionResponses->first()->dimension_name;
            
            // Inicializamos contadores para cada tipo de respuesta
            $responsesByType = [
                'A' => 0, // Siempre
                'B' => 0, // Casi siempre
                'C' => 0, // Algunas veces
                'D' => 0, // Casi nunca
                'E' => 0, // Nunca
            ];
            
            // Sumamos las respuestas por tipo
            foreach ($dimensionResponses as $response) {
                if (isset($responsesByType[$response->answer])) {
                    $responsesByType[$response->answer] = $response->total_responses;
                }
            }
            
            // Calculamos el total de respuestas para esta dimensión
            $totalResponses = array_sum($responsesByType);
            
            // Calculamos porcentajes
            $percentages = [];
            foreach ($responsesByType as $type => $count) {
                $percentages[$type] = $totalResponses > 0 ? ($count / $totalResponses) * 100 : 0;
            }
            
            // Añadimos al resultado
            $dimensionData->push([
                'id' => $dimensionId,
                'name' => $dimensionName,
                'responses' => $responsesByType,
                'percentages' => $percentages,
                'total' => $totalResponses
            ]);
        }

        return $dimensionData;
    }

    /**
     * Obtiene la suma total del valor de respuestas por dimensión
     * Implementa el Query #6 de la guía III: Suma total del valor de respuestas por dimensión
     *
     * @param string $referenceGuide
     * @return Collection
     */
    public function getDimensionTotalScores(string $referenceGuide = 'III'): Collection
    {
        try {
            // Obtenemos todos los IDs de personal con evaluaciones de la guía especificada
            $guidePersonalIds = Evaluation::where('reference_guide', $referenceGuide)
                ->whereNotNull('personal_id')
                ->pluck('personal_id')
                ->unique()
                ->filter();

            if ($guidePersonalIds->isEmpty()) {
                Log::warning("No se encontraron IDs de personal con evaluaciones de la Guía {$referenceGuide}.");
                return collect();
            }

            // Ejecutar la consulta que obtiene la suma total por dimensión
            $results = Question::join('evaluations', 'questions.evaluation_id', '=', 'evaluations.id')
                ->join('dimensions', 'questions.dimension_id', '=', 'dimensions.id')
                ->where('questions.reference_guide', $referenceGuide)
                ->whereNotNull('questions.value')
                ->whereIn('questions.personal_id', $guidePersonalIds)
                ->select(
                    'dimensions.id as dimension_id',
                    'dimensions.name as dimension_name',
                    DB::raw('SUM(questions.value) as total_score'),
                    DB::raw('COUNT(*) as question_count')
                )
                ->groupBy('dimensions.id', 'dimensions.name')
                ->orderByDesc('total_score')
                ->get();

            // Procesar los resultados para añadir información adicional como promedios
            $processedResults = $results->map(function ($item) {
                $avgScore = $item->question_count > 0 ? $item->total_score / $item->question_count : 0;
                
                return [
                    'id' => $item->dimension_id,
                    'name' => $item->dimension_name,
                    'total_score' => $item->total_score,
                    'question_count' => $item->question_count,
                    'avg_score' => round($avgScore, 2)
                ];
            });

            return $processedResults;
            
        } catch (\Exception $e) {
            Log::error("Error al obtener la suma total de respuestas por dimensión: " . $e->getMessage());
            return collect();
        }
    }
}

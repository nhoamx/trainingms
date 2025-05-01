<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Domain;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DomainReportService
{
    /**
     * Obtiene el conteo de respuestas por dominio y tipo de respuesta
     * Implementa el Query #3 de la guía III: Conteo de respuestas por dominio y tipo de respuesta
     *
     * @param string $referenceGuide
     * @return Collection
     */
    public function getDomainAnswerTypeDistribution(string $referenceGuide = 'III'): Collection
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

            // Ejecutamos la consulta que obtiene el conteo de respuestas por dominio y tipo
            // La relación es directa: questions -> domain_id -> domains (según las instrucciones)
            $results = Question::join('evaluations', 'questions.evaluation_id', '=', 'evaluations.id')
                ->join('domains', 'questions.domain_id', '=', 'domains.id')
                ->where('questions.reference_guide', $referenceGuide)
                ->whereNotNull('questions.answer')
                ->whereIn('questions.personal_id', $guidePersonalIds)
                ->select(
                    'domains.id as domain_id',
                    'domains.name as domain_name',
                    'questions.answer',
                    DB::raw('COUNT(*) as total_responses')
                )
                ->groupBy('domains.id', 'domains.name', 'questions.answer')
                ->orderBy('domains.name')
                ->orderBy('questions.answer')
                ->get();

            // Procesamos los resultados para agruparlos por dominio
            $processedData = $this->processDomainResults($results);

            return $processedData;
        } catch (\Exception $e) {
            Log::error("Error al obtener el conteo de respuestas por dominio: " . $e->getMessage());
            return collect();
        }
    }

    /**
     * Procesa los resultados de la consulta para estructurarlos por dominio
     *
     * @param Collection $results
     * @return Collection
     */
    private function processDomainResults(Collection $results): Collection
    {
        $domainData = collect();

        // Agrupamos los resultados por dominio
        $groupedResults = $results->groupBy('domain_id');

        foreach ($groupedResults as $domainId => $domainResponses) {
            $domainName = $domainResponses->first()->domain_name;
            
            // Inicializamos contadores para cada tipo de respuesta
            $responsesByType = [
                'A' => 0, // Siempre
                'B' => 0, // Casi siempre
                'C' => 0, // Algunas veces
                'D' => 0, // Casi nunca
                'E' => 0, // Nunca
            ];
            
            // Sumamos las respuestas por tipo
            foreach ($domainResponses as $response) {
                if (isset($responsesByType[$response->answer])) {
                    $responsesByType[$response->answer] = $response->total_responses;
                }
            }
            
            // Calculamos el total de respuestas para este dominio
            $totalResponses = array_sum($responsesByType);
            
            // Calculamos porcentajes
            $percentages = [];
            foreach ($responsesByType as $type => $count) {
                $percentages[$type] = $totalResponses > 0 ? ($count / $totalResponses) * 100 : 0;
            }
            
            // Añadimos al resultado
            $domainData->push([
                'id' => $domainId,
                'name' => $domainName,
                'responses' => $responsesByType,
                'percentages' => $percentages,
                'total' => $totalResponses
            ]);
        }

        return $domainData;
    }

    /**
     * Obtiene la suma total del valor de respuestas por dominio
     * Implementa el Query #4 de la guía III: Suma total del valor de respuestas por dominio
     *
     * @param string $referenceGuide
     * @return Collection
     */
    public function getDomainTotalScores(string $referenceGuide = 'III'): Collection
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

            // Ejecutar la consulta que obtiene la suma total por dominio
            $results = Question::join('evaluations', 'questions.evaluation_id', '=', 'evaluations.id')
                ->join('domains', 'questions.domain_id', '=', 'domains.id')
                ->where('questions.reference_guide', $referenceGuide)
                ->whereNotNull('questions.value')
                ->whereIn('questions.personal_id', $guidePersonalIds)
                ->select(
                    'domains.id as domain_id',
                    'domains.name as domain_name',
                    DB::raw('SUM(questions.value) as total_score'),
                    DB::raw('COUNT(*) as question_count')
                )
                ->groupBy('domains.id', 'domains.name')
                ->orderByDesc('total_score')
                ->get();

            // Procesar los resultados para añadir información adicional como promedios
            $processedResults = $results->map(function ($item) {
                $avgScore = $item->question_count > 0 ? $item->total_score / $item->question_count : 0;
                
                return [
                    'id' => $item->domain_id,
                    'name' => $item->domain_name,
                    'total_score' => $item->total_score,
                    'question_count' => $item->question_count,
                    'avg_score' => round($avgScore, 2)
                ];
            });

            return $processedResults;
            
        } catch (\Exception $e) {
            Log::error("Error al obtener la suma total de respuestas por dominio: " . $e->getMessage());
            return collect();
        }
    }
}
